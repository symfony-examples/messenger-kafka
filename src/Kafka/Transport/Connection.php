<?php

namespace App\Kafka\Transport;

use Symfony\Component\Messenger\Exception\LogicException;

class Connection
{
    private const GLOBAL_OPTIONS = [
        'consumer_topics',
        'producer_topic',
    ];

    private array $kafkaConfig;
    private KafkaFactory $kafkaFactory;

    public function __construct(array $kafkaConfig, KafkaFactory $kafkaFactory = null)
    {
        if (!\extension_loaded('rdkafka')) {
            throw new LogicException(sprintf(
                'You cannot use the "%s" as the "rdkafka" extension is not installed.', __CLASS__
            ));
        }

        $this->kafkaConfig = $kafkaConfig;
        $this->kafkaFactory = $kafkaFactory ?? new KafkaFactory();
    }

    public static function builder(array $options = [], KafkaFactory $kafkaFactory = null): self
    {
        self::validateOptions($options);

        return new self($options, $kafkaFactory);
    }

    public function get(): ?\RdKafka\Message
    {
        $consumer = $this->kafkaFactory->createConsumer($this->kafkaConfig);

        $consumer->subscribe($this->kafkaConfig['consumer_topics']);

        // todo: add consume timeout_ms to transport options
        return $consumer->consume(10*1000);
    }

    public function getTopic(): string
    {
        return $this->kafkaConfig['producer_topic'];
    }

    public function publish(string $body, array $headers = []): void
    {
        $producer = $this->kafkaFactory->createProducer($this->kafkaConfig);

        $topic = $producer->newTopic($this->getTopic());
        $topic->producev(
            partition: RD_KAFKA_PARTITION_UA,
            msgflags: 0, // todo: add msgflags to configuration choices
            payload: $body,
            headers: $headers
        );

        // todo: add poll timeout_ms to transport options
        $producer->poll(0);
        $producer->flush(10000);
    }

    private static function validateOptions(array $options): void
    {
        if (0 < \count($invalidOptions = array_diff(
                array_keys($options),
                array_merge(
                    self::GLOBAL_OPTIONS,
                    array_keys(
                        array_merge(self::GLOBAL_OPTIONS, KafkaOption::consumer(), KafkaOption::producer())
                    )
                )
            ))
        ) {
            throw new LogicException(sprintf(
                'Invalid option(s) "%s" passed to the Kafka Messenger transport.',
                implode('", "', $invalidOptions))
            );
        }
    }
}
