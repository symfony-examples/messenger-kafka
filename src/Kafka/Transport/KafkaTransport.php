<?php

namespace App\Kafka\Transport;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Component\Messenger\Transport\Receiver\QueueReceiverInterface;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\SetupableTransportInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class KafkaTransport implements TransportInterface, SetupableTransportInterface
{
    private SerializerInterface $serializer;
    private Connection $connection;
    private KafkaReceiver $receiver;
    private KafkaSender $sender;

    public function __construct(Connection $connection, SerializerInterface $serializer = null)
    {
        $this->connection = $connection;
        $this->serializer = $serializer ?? new PhpSerializer();
    }

    public function get(): iterable
    {
        return $this->getReceiver()->get();
    }

    public function ack(Envelope $envelope): void
    {
        // TODO: Implement ack() method.
    }

    public function reject(Envelope $envelope): void
    {
        // TODO: Implement reject() method.
    }

    public function send(Envelope $envelope): Envelope
    {
        return $this->getSender()->send($envelope);
    }

    public function setup(): void
    {
        $this->connection->setup();
    }

    private function getReceiver(): KafkaReceiver
    {
        return $this->receiver ??= new KafkaReceiver($this->connection, $this->serializer);
    }

    private function getSender(): KafkaSender
    {
        return $this->sender ??= new KafkaSender($this->connection, $this->serializer);
    }
}
