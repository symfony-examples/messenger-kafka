<?php

namespace App\Kafka\Transport;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
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

        /** @var DelayStamp|null $delayStamp */
        $delayStamp = $envelope->last(DelayStamp::class);
        $delay = $delayStamp ? $delayStamp->getDelay() : 0;

        /** @var KafkaStamp|null $kafkaStamp */
        $kafkaStamp = $envelope->last(KafkaStamp::class);

        if (isset($encodedMessage['headers']['Content-Type'])) {
            $contentType = $encodedMessage['headers']['Content-Type'];
            unset($encodedMessage['headers']['Content-Type']);

            if (!$kafkaStamp || !isset($kafkaStamp->getAttributes()['content_type'])) {
                $kafkaStamp = KafkaStamp::createWithAttributes(['content_type' => $contentType], $kafkaStamp);
            }
        }

        $kafkaReceivedStamp = $envelope->last(KafkaReceivedStamp::class);

        if ($kafkaReceivedStamp instanceof KafkaReceivedStamp) {
            $kafkaStamp = KafkaStamp::createFromKafkaMessage(
                $kafkaReceivedStamp->getKafkaMessage(),
                $kafkaStamp,
                $envelope->last(RedeliveryStamp::class) ? $kafkaReceivedStamp->getTopic() : null
            );
        }

        try {
            $this->connection->publish(
                $encodedMessage['body'],
                $encodedMessage['headers'] ?? [],
                $kafkaStamp
            );
        } catch (\RdKafka\Exception $e) {
            throw new TransportException($e->getMessage(), 0, $e);
        }

        return $envelope;
    }
}
