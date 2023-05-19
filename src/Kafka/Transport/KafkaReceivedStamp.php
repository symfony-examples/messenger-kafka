<?php

namespace App\Kafka\Transport;

use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;

class KafkaReceivedStamp implements NonSendableStampInterface
{
    private \RdKafka\Message $kafkaMessage;
    private string $topic;

    public function __construct(\RdKafka\Message $kafkaMessage, string $topic)
    {
        $this->kafkaMessage = $kafkaMessage;
        $this->topic = $topic;
    }

    public function getKafkaMessage(): \RdKafka\Message
    {
        return $this->kafkaMessage;
    }

    public function getTopic(): string
    {
        return $this->topic;
    }
}
