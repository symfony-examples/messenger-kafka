<?php

namespace App\Tests\Unit\Kafka\Transport;

use App\Kafka\Transport\Connection;
use App\Kafka\Transport\KafkaSender;
use App\Tests\Unit\Fixtures\FakeMessage;
use PHPUnit\Framework\TestCase;
use RdKafka\Exception;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Transport\Serialization\Serializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Serializer as SymfonySerializer;

class KafkaSenderTest extends TestCase
{
    private SerializerInterface $serializer;
    private Connection $connection;
    private KafkaSender $kafkaSender;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->serializer = new Serializer(
            new SymfonySerializer\Serializer([new SymfonySerializer\Normalizer\ObjectNormalizer()], ['json' => new SymfonySerializer\Encoder\JsonEncoder()])
        );
        $this->kafkaSender = new KafkaSender(
            $this->connection,
            $this->serializer
        );
    }

    public function testSend()
    {
        $envelope = new Envelope(new FakeMessage('Hello'));
        $this->connection
            ->expects($this->once())
            ->method('publish')
            ->with(
                '{"message":"Hello"}',
                ['type' => FakeMessage::class, 'Content-Type' => 'application/json']
            );

        self::assertSame($envelope, $this->kafkaSender->send($envelope));
    }

    public function testExceptionConnection()
    {
        $envelope = new Envelope(new FakeMessage('Hello'));
        $this->connection
            ->expects($this->once())
            ->method('publish')
            ->with(
                '{"message":"Hello"}',
                ['type' => FakeMessage::class, 'Content-Type' => 'application/json']
            )
            ->willThrowException(new Exception('Connection exception', 1));

        self::expectException(TransportException::class);
        self::expectExceptionMessage('Connection exception');
        self::expectExceptionCode(0);

        $this->kafkaSender->send($envelope);
    }
}
