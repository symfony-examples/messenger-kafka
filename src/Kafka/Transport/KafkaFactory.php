<?php

namespace App\Kafka\Transport;

class KafkaFactory
{
    public function createConsumer(array $kafkaConfig): \RdKafka\KafkaConsumer
    {
        $conf = new \RdKafka\Conf();

        foreach ($kafkaConfig as $key => $value) {
            if (array_key_exists($key, array_merge(KafkaOption::global(), KafkaOption::consumer()))) {
                $conf->set($key, $value);
            }
        }

        return new \RdKafka\KafkaConsumer($conf);
    }

    public function createProducer(array $kafkaConfig): \RdKafka\Producer
    {
        $conf = new \RdKafka\Conf();

        foreach ($kafkaConfig as $key => $value) {
            if (array_key_exists($key, array_merge(KafkaOption::global(), KafkaOption::producer()))) {
                $conf->set($key, $value);
            }
        }

        return new \RdKafka\Producer($conf);
    }
}
