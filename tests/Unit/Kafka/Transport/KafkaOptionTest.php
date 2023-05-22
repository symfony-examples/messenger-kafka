<?php

namespace App\Tests\Unit\Kafka\Transport;

use App\Kafka\Transport\KafkaOption;
use PHPUnit\Framework\TestCase;

class KafkaOptionTest extends TestCase
{
    public function testProducer()
    {
        self::assertIsArray(KafkaOption::producer());

        foreach (KafkaOption::producer() as $option) {
            self::assertTrue(in_array($option, ['P', '*']));
        }
    }

    public function testConsumer()
    {
        self::assertIsArray(KafkaOption::consumer());

        foreach (KafkaOption::consumer() as $option) {
            self::assertTrue(in_array($option, ['C', '*']));
        }
    }

    public function testGlobal()
    {
        self::assertIsArray(KafkaOption::global());

        foreach (KafkaOption::global() as $option) {
            self::assertEquals('*', $option);
        }
    }
}
