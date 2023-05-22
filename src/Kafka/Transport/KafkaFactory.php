<?php

namespace App\Kafka\Transport;

class KafkaFactory
{
    /** @psalm-param array<string, bool|float|int|string|array<string>> $kafkaConfig */
    public function createConsumer(array $kafkaConfig): \RdKafka\KafkaConsumer
    {
        $conf = new \RdKafka\Conf();

        foreach ($kafkaConfig as $key => $value) {
            if (array_key_exists($key, array_merge(KafkaOption::global(), KafkaOption::consumer()))) {
                if (!is_string($value)) {
                    // todo: warning
                    continue;
                }
                $conf->set($key, $value);
            }
        }

        return new \RdKafka\KafkaConsumer($conf);
    }

    /** @psalm-param array<string, bool|float|int|string|array<string>> $kafkaConfig */
    public function createProducer(array $kafkaConfig): \RdKafka\Producer
    {
        $conf = new \RdKafka\Conf();

        foreach ($kafkaConfig as $key => $value) {
            if (array_key_exists($key, array_merge(KafkaOption::global(), KafkaOption::producer()))) {
                if (!is_string($value)) {
                    // todo: warning
                    continue;
                }
                $conf->set($key, $value);
            }
        }

        return new \RdKafka\Producer($conf);
    }
}
