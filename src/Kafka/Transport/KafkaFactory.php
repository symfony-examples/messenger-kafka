<?php

namespace App\Kafka\Transport;

use RdKafka\Conf;
use RdKafka\KafkaConsumer;
use RdKafka\Producer;

class KafkaFactory
{
    /** @psalm-param array<string, bool|float|int|string|array<string>> $kafkaConfig */
    public function createConsumer(array $kafkaConfig): KafkaConsumer
    {
        $conf = new Conf();

        foreach ($kafkaConfig as $key => $value) {
            if (array_key_exists($key, array_merge(KafkaOption::global(), KafkaOption::consumer()))) {
                if (!is_string($value)) {
                    // todo: warning
                    continue;
                }
                $conf->set($key, $value);
            }
        }

        return new KafkaConsumer($conf);
    }

    /** @psalm-param array<string, bool|float|int|string|array<string>> $kafkaConfig */
    public function createProducer(array $kafkaConfig): Producer
    {
        $conf = new Conf();

        foreach ($kafkaConfig as $key => $value) {
            if (array_key_exists($key, array_merge(KafkaOption::global(), KafkaOption::producer()))) {
                if (!is_string($value)) {
                    // todo: warning
                    continue;
                }
                $conf->set($key, $value);
            }
        }

        return new Producer($conf);
    }
}
