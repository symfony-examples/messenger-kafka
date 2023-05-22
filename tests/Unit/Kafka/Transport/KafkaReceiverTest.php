<?php

namespace App\Tests\Unit\Kafka\Transport;

use App\Kafka\Transport\Connection;
use App\Kafka\Transport\KafkaReceiver;
use App\Tests\Unit\Fixtures\FakeMessage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Transport\Serialization\Serializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Serializer as SymfonySerializer;

class KafkaReceiverTest extends TestCase
{
    private SerializerInterface $serializer;
    private Connection $connection;
    private KafkaReceiver $kafkaReceiver;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->serializer = new Serializer(
            new SymfonySerializer\Serializer([new SymfonySerializer\Normalizer\ObjectNormalizer()], ['json' => new SymfonySerializer\Encoder\JsonEncoder()])
        );
        $this->kafkaReceiver = new KafkaReceiver(
            $this->connection,
            $this->serializer
        );
    }

    public function testGetDecodedMessage()
    {
        $kafkaMessage = new \RdKafka\Message();
        $kafkaMessage->headers = ['type' => FakeMessage::class];
        $kafkaMessage->payload = '{"message": "Hello"}';
        $kafkaMessage->err = 0;

        $this->connection->method('get')->willReturn($kafkaMessage);

        $envelopes = iterator_to_array($this->kafkaReceiver->get());
        self::assertCount(1, $envelopes);
        self::assertEquals(new FakeMessage('Hello'), $envelopes[0]->getMessage());
    }

    public function testNoMoreMessages()
    {
        $kafkaMessage = new \RdKafka\Message();
        $kafkaMessage->payload = 'No more messages';
        $kafkaMessage->err = RD_KAFKA_RESP_ERR__PARTITION_EOF;

        $this->connection->method('get')->willReturn($kafkaMessage);

        $envelopes = iterator_to_array($this->kafkaReceiver->get());
        self::assertCount(0, $envelopes);
    }

    public function testTimeOut()
    {
        $kafkaMessage = new \RdKafka\Message();
        $kafkaMessage->payload = 'Timeout';
        $kafkaMessage->err = RD_KAFKA_RESP_ERR__TIMED_OUT;

        $this->connection->method('get')->willReturn($kafkaMessage);

        $envelopes = iterator_to_array($this->kafkaReceiver->get());
        self::assertCount(0, $envelopes);
    }

    public function testUnknownTopic()
    {
        $kafkaMessage = new \RdKafka\Message();
        $kafkaMessage->payload = 'Unknown topic';
        $kafkaMessage->err = RD_KAFKA_RESP_ERR__UNKNOWN_TOPIC;

        $this->connection->method('get')->willReturn($kafkaMessage);

        self::expectException(TransportException::class);
        self::expectExceptionMessage('Local: Unknown topic');
        self::expectExceptionCode(RD_KAFKA_RESP_ERR__UNKNOWN_TOPIC);
        $envelopes = iterator_to_array($this->kafkaReceiver->get());
        self::assertCount(0, $envelopes);
    }

    public function testExceptionConnection()
    {
        $kafkaMessage = new \RdKafka\Message();
        $kafkaMessage->payload = 'Unknown topic';
        $kafkaMessage->err = RD_KAFKA_RESP_ERR__UNKNOWN_TOPIC;

        $this->connection->method('get')->willThrowException(
            new \RdKafka\Exception('Connection exception', 1)
        );

        self::expectException(TransportException::class);
        self::expectExceptionMessage('Connection exception');
        self::expectExceptionCode(0);
        $envelopes = iterator_to_array($this->kafkaReceiver->get());
        self::assertCount(0, $envelopes);
    }
}
