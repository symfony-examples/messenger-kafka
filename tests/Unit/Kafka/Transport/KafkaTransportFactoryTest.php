<?php

namespace App\Tests\Unit\Kafka\Transport;

use App\Kafka\Transport\KafkaTransport;
use App\Kafka\Transport\KafkaTransportFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class KafkaTransportFactoryTest extends TestCase
{
    private KafkaTransportFactory $factory;
    private SerializerInterface $serializer;

    protected function setUp(): void
    {
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->factory = new KafkaTransportFactory();
    }

    public function testCreateTransport()
    {
        self::assertInstanceOf(
            KafkaTransport::class,
            $this->factory->createTransport(
                'kafka://',
                ['transport_name' => 'php-unit-transport'],
                $this->serializer
            )
        );
    }

    public function testSupports()
    {
        self::assertTrue($this->factory->supports('kafka://', []));
        self::assertTrue($this->factory->supports('kafka://localhost:9092', []));
        self::assertFalse($this->factory->supports('plaintext://localhost:9092', []));
        self::assertFalse($this->factory->supports('kafka', []));
    }
}
