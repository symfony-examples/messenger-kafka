<?php

namespace App\Kafka\Transport;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Transport\Sender\SenderInterface;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class KafkaSender implements SenderInterface
{
    private SerializerInterface $serializer;
    private Connection $connection;

    public function __construct(Connection $connection, SerializerInterface $serializer = null)
    {
        $this->connection = $connection;
        $this->serializer = $serializer ?? new PhpSerializer();
    }

    public function send(Envelope $envelope): Envelope
    {
        $encodedMessage = $this->serializer->encode($envelope);

        try {
            $this->connection->publish(
                $encodedMessage['body'],
                $encodedMessage['headers'] ?? []
            );
        } catch (\RdKafka\Exception $e) {
            throw new TransportException($e->getMessage(), 0, $e);
        }

        return $envelope;
    }
}
