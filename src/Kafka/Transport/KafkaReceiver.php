<?php

namespace App\Kafka\Transport;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Transport\Receiver\QueueReceiverInterface;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class KafkaReceiver implements QueueReceiverInterface
{
    private SerializerInterface $serializer;
    private Connection $connection;

    public function __construct(Connection $connection, SerializerInterface $serializer = null)
    {
        $this->connection = $connection;
        $this->serializer = $serializer ?? new PhpSerializer();
    }

    public function getFromQueues(array $queueNames): iterable
    {
        foreach ($queueNames as $queueName) {
            yield from $this->getEnvelope($queueName);
        }
    }

    public function get(): iterable
    {
        yield from $this->getFromQueues($this->connection->getTopics());
    }

    public function ack(Envelope $envelope): void
    {
        // TODO: Implement ack() method.
    }

    public function reject(Envelope $envelope): void
    {
        // TODO: Implement reject() method.
    }

    private function getEnvelope(string $topic): iterable
    {
        try {
            $kafkaMessage = $this->connection->get();
        } catch (\RdKafka\Exception $exception) {
            throw new TransportException($exception->getMessage(), 0, $exception);
        }

        if (null === $kafkaMessage) {
            return;
        }

        if (0 !== $kafkaMessage->err) {
            // todo: manage exceptions
            return;
        }

        $body = $kafkaMessage->payload;

        try {
            // todo: multiple serializer (string, json, v2+json) https://kafka.apache.org/23/javadoc/org/apache/kafka/common/serialization/package-frame.html
            $envelope = $this->serializer->decode([
                'body' => $body,
                'headers' => $kafkaMessage->headers,
            ]);
        } catch (MessageDecodingFailedException $exception) {
            // invalid message of some type
            //$this->rejectAmqpEnvelope($amqpEnvelope, $queueName);

            throw $exception;
        }

        yield $envelope->with(new KafkaReceivedStamp($kafkaMessage, $topic));
    }
}
