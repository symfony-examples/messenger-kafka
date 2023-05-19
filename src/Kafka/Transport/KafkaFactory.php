<?php

namespace App\Kafka\Transport;

class KafkaFactory
{
    public function createConsumer(array $kafkaConfig): \RdKafka\KafkaConsumer
    {
        $conf = new \RdKafka\Conf();

        // todo: add group id to transport options
        // Configure the group.id. All consumer with the same group.id will consume different partitions.
        $conf->set('group.id', 'myConsumerGroup');

        // Initial list of Kafka brokers
        $conf->set('metadata.broker.list', $kafkaConfig['brokers']);

        // todo: add auto offset reset to transport options
        // 'earliest': start from the beginning
        $conf->set('auto.offset.reset', 'earliest');

        // todo: add enable partition eof to transport options
        // Emit EOF event when reaching the end of a partition
        $conf->set('enable.partition.eof', 'true');

        return new \RdKafka\KafkaConsumer($conf);
    }

    public function createProducer(array $kafkaConfig): \RdKafka\Producer
    {
        $conf = new \RdKafka\Conf();
        $conf->set('metadata.broker.list', $kafkaConfig['brokers']);
        $producer = new \RdKafka\Producer($conf);

        return $producer;
    }
}
