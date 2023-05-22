<?php

namespace App\Tests\Unit\Fixtures;

use App\Kafka\Transport\KafkaFactory;
use RdKafka\KafkaConsumer;
use RdKafka\Producer;

class TestKafkaFactory extends KafkaFactory
{
    public function __construct(public KafkaConsumer $consumer, public Producer $producer)
    {
    }

    public function createConsumer(array $kafkaConfig): KafkaConsumer
    {
        return $this->consumer;
    }

    public function createProducer(array $kafkaConfig): Producer
    {
        return $this->producer;
    }
}
