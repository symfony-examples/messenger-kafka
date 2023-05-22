<?php

namespace App\Kafka\Transport;

use Symfony\Component\Messenger\Exception\LogicException;
use Symfony\Component\Messenger\Exception\TransportException;

class Connection
{
    private const BROKERS_LIST = 'metadata.broker.list';
    private const GROUP_ID = 'group.id';
    private const PRODUCER_MESSAGE_FLAGS_BLOCK = 'producer_message_flags_block';
    private const PRODUCER_PARTITION_ID_ASSIGNMENT = 'producer_partition_id_assignment';
    private const CONSUMER_TOPICS_NAME = 'consumer_topics';
    private const CONSUMER_CONSUME_TIMEOUT_MS = 'consumer_consume_timeout_ms';
    private const PRODUCER_POLL_TIMEOUT_MS = 'producer_poll_timeout_ms';
    private const PRODUCER_FLUSH_TIMEOUT_MS = 'producer_flush_timeout_ms';
    private const PRODUCER_TOPIC_NAME = 'producer_topic';
    private const TRANSPORT_NAME = 'transport_name';
    private const GLOBAL_OPTIONS = [
        self::TRANSPORT_NAME,
        self::CONSUMER_CONSUME_TIMEOUT_MS,
        self::PRODUCER_POLL_TIMEOUT_MS,
        self::PRODUCER_FLUSH_TIMEOUT_MS,
        self::PRODUCER_MESSAGE_FLAGS_BLOCK,
        self::PRODUCER_PARTITION_ID_ASSIGNMENT,
        self::CONSUMER_TOPICS_NAME,
        self::PRODUCER_TOPIC_NAME,
    ];

    public function __construct(
        private readonly array $kafkaConfig,
        private readonly KafkaFactory $kafkaFactory = new KafkaFactory()
    ) {
        if (!\extension_loaded('rdkafka')) {
            throw new LogicException(sprintf('You cannot use the "%s" as the "rdkafka" extension is not installed.', __CLASS__));
        }
    }

    public function setup(): void
    {
        if (!array_key_exists(self::BROKERS_LIST, $this->kafkaConfig)) {
            throw new LogicException(sprintf('The "%s" option is required for the Kafka Messenger transport "%s".', self::BROKERS_LIST, $this->kafkaConfig[self::TRANSPORT_NAME]));
        }

        if (
            !array_key_exists(self::CONSUMER_TOPICS_NAME, $this->kafkaConfig) &&
            !array_key_exists(self::PRODUCER_TOPIC_NAME, $this->kafkaConfig)
        ) {
            throw new LogicException(sprintf('At least one of "%s" or "%s" options is required for the Kafka Messenger transport "%s".', self::CONSUMER_TOPICS_NAME, self::PRODUCER_TOPIC_NAME, $this->kafkaConfig[self::TRANSPORT_NAME]));
        }
    }

    public static function builder(array $options = [], KafkaFactory $kafkaFactory = null): self
    {
        self::optionsValidator($options);

        return new self($options, $kafkaFactory ?? new KafkaFactory());
    }

    public function get(): \RdKafka\Message
    {
        if (!array_key_exists(self::GROUP_ID, $this->kafkaConfig)) {
            throw new LogicException(sprintf('The transport "%s" is not configured to consume messages because "%s" option is missing.', $this->kafkaConfig[self::TRANSPORT_NAME], self::GROUP_ID));
        }

        $consumer = $this->kafkaFactory->createConsumer($this->kafkaConfig);

        try {
            $consumer->subscribe($this->getTopics());

            return $consumer->consume($this->getConsumerConsumeTimeout());
        } catch (\RdKafka\Exception $e) {
            throw new TransportException($e->getMessage(), 0, $e);
        }
    }

    public function publish(string $body, array $headers = []): void
    {
        $producer = $this->kafkaFactory->createProducer($this->kafkaConfig);

        $topic = $producer->newTopic($this->getTopic());
        $topic->producev(
            partition: $this->getPartitionId(), // todo: retrieve from stamp ?
            msgflags: $this->getMessageFlags(),
            payload: $body,
            headers: $headers
        );

        $producer->poll($this->getProducerPollTimeout());
        $producer->flush($this->getProducerFlushTimeout());
    }

    private static function optionsValidator(array $options): void
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
            throw new LogicException(sprintf('Invalid option(s) "%s" passed to the Kafka Messenger transport "%s".', implode('", "', $invalidOptions), $options[self::TRANSPORT_NAME]));
        }
    }

    private static function intOptionValidator(array $options, string $optionKey): void
    {
        if (array_key_exists($optionKey, $options) && !is_int($options[$optionKey])) {
            throw new LogicException(sprintf('The "%s" option type must be integer, %s given in "%s" transport.', $optionKey, gettype($options[$optionKey]), $options[self::TRANSPORT_NAME]));
        }
    }

    private function getTopics(): array
    {
        if (!array_key_exists(self::CONSUMER_TOPICS_NAME, $this->kafkaConfig)) {
            throw new LogicException(sprintf('The transport "%s" is not configured to consume messages because "%s" option is missing.', $this->kafkaConfig[self::TRANSPORT_NAME], self::CONSUMER_TOPICS_NAME));
        }

        if (!is_array($this->kafkaConfig[self::CONSUMER_TOPICS_NAME])) {
            throw new LogicException(sprintf('The "%s" option type must be array, %s given in "%s" transport.', self::CONSUMER_TOPICS_NAME, gettype($this->kafkaConfig[self::CONSUMER_TOPICS_NAME]), $this->kafkaConfig[self::TRANSPORT_NAME]));
        }

        return $this->kafkaConfig[self::CONSUMER_TOPICS_NAME];
    }

    private function getConsumerConsumeTimeout(): int
    {
        if (!array_key_exists(self::CONSUMER_CONSUME_TIMEOUT_MS, $this->kafkaConfig)) {
            return 10000;
        }

        self::intOptionValidator($this->kafkaConfig, self::CONSUMER_CONSUME_TIMEOUT_MS);

        return $this->kafkaConfig[self::CONSUMER_CONSUME_TIMEOUT_MS];
    }

    private function getTopic(): string
    {
        if (!array_key_exists(self::PRODUCER_TOPIC_NAME, $this->kafkaConfig)) {
            throw new LogicException(sprintf('The transport "%s" is not configured to dispatch messages because "%s" option is missing.', $this->kafkaConfig[self::TRANSPORT_NAME], self::PRODUCER_TOPIC_NAME));
        }

        return $this->kafkaConfig[self::PRODUCER_TOPIC_NAME];
    }

    private function getMessageFlags(): int
    {
        if (!array_key_exists(self::PRODUCER_MESSAGE_FLAGS_BLOCK, $this->kafkaConfig)) {
            return 0;
        }

        if (!is_bool($this->kafkaConfig[self::PRODUCER_MESSAGE_FLAGS_BLOCK])) {
            throw new LogicException(sprintf('The "%s" option type must be boolean, %s given in "%s" transport.', self::PRODUCER_MESSAGE_FLAGS_BLOCK, gettype($this->kafkaConfig[self::PRODUCER_MESSAGE_FLAGS_BLOCK]), $this->kafkaConfig[self::TRANSPORT_NAME]));
        }

        return false === $this->kafkaConfig[self::PRODUCER_MESSAGE_FLAGS_BLOCK] ? 0 : RD_KAFKA_MSG_F_BLOCK;
    }

    private function getPartitionId(): int
    {
        if (!array_key_exists(self::PRODUCER_PARTITION_ID_ASSIGNMENT, $this->kafkaConfig)) {
            return RD_KAFKA_PARTITION_UA;
        }

        self::intOptionValidator($this->kafkaConfig, self::PRODUCER_PARTITION_ID_ASSIGNMENT);

        return $this->kafkaConfig[self::PRODUCER_PARTITION_ID_ASSIGNMENT];
    }

    private function getProducerPollTimeout(): int
    {
        if (!array_key_exists(self::PRODUCER_POLL_TIMEOUT_MS, $this->kafkaConfig)) {
            return 0;
        }

        self::intOptionValidator($this->kafkaConfig, self::PRODUCER_POLL_TIMEOUT_MS);

        return $this->kafkaConfig[self::PRODUCER_POLL_TIMEOUT_MS];
    }

    private function getProducerFlushTimeout(): int
    {
        if (!array_key_exists(self::PRODUCER_FLUSH_TIMEOUT_MS, $this->kafkaConfig)) {
            return 10000;
        }

        self::intOptionValidator($this->kafkaConfig, self::PRODUCER_FLUSH_TIMEOUT_MS);

        return $this->kafkaConfig[self::PRODUCER_FLUSH_TIMEOUT_MS];
    }
}
