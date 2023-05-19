<?php

namespace App\Kafka\Transport;

use Symfony\Component\Messenger\Exception\InvalidArgumentException;
use Symfony\Component\Messenger\Exception\LogicException;

class Connection
{
    private const ARGUMENTS_AS_INTEGER = [
        'x-delay',
        'x-expires',
        'x-max-length',
        'x-max-length-bytes',
        'x-max-priority',
        'x-message-ttl',
    ];

    // todo: add all available options
    // todo: create ENUM with available options
    private const AVAILABLE_OPTIONS = [
        'protocol',
        'brokers',
        'topics',
        'topic',
    ];

    private const DEFAULT_KAFKA_BROKER = 'localhost:9092';
    private const DEFAULT_KAFKA_PROTOCOL = 'PLAINTEXT';

    private array $kafkaConfig;
    private KafkaFactory $kafkaFactory;

    public function __construct(array $kafkaConfig, KafkaFactory $kafkaFactory = null)
    {
        if (!\extension_loaded('rdkafka')) {
            throw new LogicException(sprintf('You cannot use the "%s" as the "rdkafka" extension is not installed.', __CLASS__));
        }

        $this->kafkaConfig = $kafkaConfig;
        $this->kafkaFactory = $kafkaFactory ?? new KafkaFactory();
    }

    public static function builder(string $dsn, array $options = [], KafkaFactory $kafkaFactory = null): self
    {
        [$protocol, $brokers] = explode('://', $dsn);

        $kafkaOptions = array_replace_recursive([
            'protocol' => !empty($protocol = str_replace(['kafka', '+'], '', $protocol)) ? $protocol : self::DEFAULT_KAFKA_PROTOCOL,
            'brokers' => $brokers ?? self::DEFAULT_KAFKA_BROKER,
        ], $options);

        self::validateOptions($kafkaOptions);

        // todo: configure ssl options
        // todo: configure replication options
        // todo: configure partition factor options
        // todo: configure consumer group id options
        // todo: configure offset store options
        // todo: configure offset reset options
        $kafkaConfig = [];

        $kafkaConfig['brokers'] = $kafkaOptions['brokers'];
        $kafkaConfig['topics'] = $kafkaOptions['topics'];
        $kafkaConfig['topic'] = $kafkaOptions['topic'];

        return new self($kafkaConfig, $kafkaFactory);
    }

    public function get(): ?\RdKafka\Message
    {
        $this->clearWhenDisconnected();

        $consumer = $this->kafkaFactory->createConsumer($this->kafkaConfig);

        $consumer->subscribe($this->kafkaConfig['topics']);

        // todo: add timeout_ms to transport options
        return $consumer->consume(10*1000);
    }

    private function clearWhenDisconnected(): void
    {
        // todo: check if is connected
    }

    public function setup(): void
    {
        // todo: create topics if not exists
    }

    public function getTopic(): string
    {
        return $this->kafkaConfig['topic'];
    }

    public function getTopics(): array
    {
        return $this->kafkaConfig['topics'];
    }

    public function publish(string $body, array $headers = [], KafkaStamp $kafkaStamp = null): void
    {
        $this->clearWhenDisconnected();

        $producer = $this->kafkaFactory->createProducer($this->kafkaConfig);
        // todo: check if topic exist
        $topic = $producer->newTopic($this->getTopic());
        $topic->producev(
            partition: RD_KAFKA_PARTITION_UA,
            msgflags: 0,
            payload: $body,
            headers: $headers
        );
        $producer->poll(0);

        for ($flushRetries = 0; $flushRetries < 5; $flushRetries++) {
            $result = $producer->flush(10000);

            if (RD_KAFKA_RESP_ERR_NO_ERROR === $result) {
                break;
            }
        }
    }

    private static function validateOptions(array $options): void
    {
        if (0 < \count($invalidOptions = array_diff(array_keys($options), self::AVAILABLE_OPTIONS))) {
            throw new LogicException(sprintf('Invalid option(s) "%s" passed to the Kafka Messenger transport.', implode('", "', $invalidOptions)));
        }
    }
}