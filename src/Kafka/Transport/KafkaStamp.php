<?php

namespace App\Kafka\Transport;

use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;

class KafkaStamp implements NonSendableStampInterface
{
    // todo: add partition
    // todo: add replication factor
    private int $flags;

    private array $attributes;

    public function __construct(int $flags, array $attributes = [])
    {
        $this->flags = $flags;
        $this->attributes = $attributes;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public static function createFromKafkaMessage(\RdKafka\Message $kafkaMessage, self $previousStamp = null, string $retryRoutingKey = null): self
    {
        $attr = $previousStamp->attributes ?? [];

        $attr['headers'] ??= $kafkaMessage->headers;
        //$attr['content_type'] ??= $amqpEnvelope->getContentType();
        //$attr['content_encoding'] ??= $amqpEnvelope->getContentEncoding();
        //$attr['delivery_mode'] ??= $amqpEnvelope->getDeliveryMode();
        //$attr['priority'] ??= $amqpEnvelope->getPriority();
        $attr['timestamp'] ??= $kafkaMessage->timestamp;
        //$attr['app_id'] ??= $amqpEnvelope->getAppId();
        //$attr['message_id'] ??= $amqpEnvelope->getMessageId();
        //$attr['user_id'] ??= $amqpEnvelope->getUserId();
        //$attr['expiration'] ??= $amqpEnvelope->getExpiration();
        //$attr['type'] ??= $amqpEnvelope->getType();
        //$attr['reply_to'] ??= $amqpEnvelope->getReplyTo();
        //$attr['correlation_id'] ??= $amqpEnvelope->getCorrelationId();

        return new self($previousStamp->flags ?? 0, $attr);
    }

    public static function createWithAttributes(array $attributes, self $previousStamp = null): self
    {
        return new self(
            $previousStamp->flags ?? 0,
            array_merge($previousStamp->attributes ?? [], $attributes)
        );
    }
}