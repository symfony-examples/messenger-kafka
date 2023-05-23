<?php

namespace App\Tests\Unit\Kafka\Transport;

use App\Kafka\Transport\Connection;
use App\Kafka\Transport\KafkaFactory;
use App\Tests\Unit\Fixtures\FakeMessage;
use App\Tests\Unit\Fixtures\TestKafkaFactory;
use PHPUnit\Framework\TestCase;
use RdKafka\Exception;
use RdKafka\KafkaConsumer;
use RdKafka\Message;
use RdKafka\Producer;
use RdKafka\ProducerTopic;
use Symfony\Component\Messenger\Exception\LogicException;
use Symfony\Component\Messenger\Exception\RuntimeException;
use Symfony\Component\Messenger\Exception\TransportException;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ConnectionTest extends TestCase
{
    private KafkaConsumer $consumer;
    private Producer $producer;
    private KafkaFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new TestKafkaFactory(
            $this->consumer = $this->createMock(KafkaConsumer::class),
            $this->producer = $this->createMock(Producer::class)
        );
    }

    public function testBuilder()
    {
        self::assertInstanceOf(
            Connection::class,
            Connection::builder(
                [
                    'transport_name' => 'php-unit-transport',
                    'metadata.broker.list' => 'localhost:9092',
                    'group.id' => 'groupId',
                    'producer_topic' => 'groupId',
                    'consumer_topics' => 'groupId',
                ]
            )
        );
    }

    public function testBuilderWithExceptionDiff()
    {
        self::expectException(LogicException::class);
        self::expectExceptionMessage(sprintf(
            'Invalid option(s) "%s" passed to the Kafka Messenger transport "%s".',
            implode('", "', ['metadata_broker_list', 'group_id']),
            'php-unit-transport'
        ));
        self::expectExceptionCode(0);
        Connection::builder(
            [
                'transport_name' => 'php-unit-transport',
                'metadata_broker_list' => 'localhost:9092',
                'group_id' => 'groupId',
                'producer_topic' => 'producer_topic',
                'consumer_topics' => 'consumer_topics',
            ]
        );
    }

    public function testBuilderWithTransportNameMissing()
    {
        self::expectException(RuntimeException::class);
        self::expectExceptionMessage('Transport name must be exist end type of string.');
        self::expectExceptionCode(0);
        Connection::builder(
            [
                'metadata_broker_list' => 'localhost:9092',
                'group_id' => 'groupId',
                'consumer_topics' => 'consumer_topics',
            ]
        );
    }

    public function testBuilderWithTransportNameException()
    {
        self::expectException(RuntimeException::class);
        self::expectExceptionMessage('Transport name must be exist end type of string.');
        self::expectExceptionCode(0);
        Connection::builder(
            [
                'transport_name' => ['php-unit-transport'],
                'metadata_broker_list' => 'localhost:9092',
                'group_id' => 'groupId',
                'consumer_topics' => 'consumer_topics',
            ]
        );
    }

    public function testPublish()
    {
        $connection = Connection::builder(
            [
                'transport_name' => 'php-unit-transport',
                'producer_topic' => 'php-unit-producer-topic',
            ],
            $this->factory
        );

        $this->producer->expects($this->once())
            ->method('newTopic')
            ->with('php-unit-producer-topic')
            ->willReturn($topic = $this->createMock(ProducerTopic::class))
        ;

        $topic->expects($this->once())
            ->method('producev')
            ->with(RD_KAFKA_PARTITION_UA, 0, 'body');

        $this->producer->expects($this->once())->method('poll')->with(0);
        $this->producer->expects($this->once())->method('flush')->with(10000);

        $connection->publish('body', ['type' => FakeMessage::class]);
    }

    public function testPublishWithTopicMissingException()
    {
        $connection = Connection::builder(
            [
                'transport_name' => 'php-unit-transport',
            ],
            $this->factory
        );

        $this->producer->expects($this->never())->method('newTopic');
        $this->producer->expects($this->never())->method('poll');
        $this->producer->expects($this->never())->method('flush');

        self::expectException(LogicException::class);
        self::expectExceptionMessage('The transport "php-unit-transport" is not configured to dispatch messages because "producer_topic" option is missing.');
        self::expectExceptionCode(0);
        $connection->publish('body', ['type' => FakeMessage::class]);
    }

    public function testPublishWithTopicException()
    {
        $connection = Connection::builder(
            [
                'transport_name' => 'php-unit-transport',
                'producer_topic' => ['php-unit-producer-topic'],
            ],
            $this->factory
        );

        $this->producer->expects($this->never())->method('newTopic');
        $this->producer->expects($this->never())->method('poll');
        $this->producer->expects($this->never())->method('flush');

        self::expectException(LogicException::class);
        self::expectExceptionMessage('The "producer_topic" option type must be string, array given in "php-unit-transport" transport.');
        self::expectExceptionCode(0);
        $connection->publish('body', ['type' => FakeMessage::class]);
    }

    public function testPublishWithPartitionAssignmentException()
    {
        $connection = Connection::builder(
            [
                'transport_name' => 'php-unit-transport',
                'producer_topic' => 'php-unit-producer-topic',
                'producer_partition_id_assignment' => 'partition',
            ],
            $this->factory
        );

        $this->producer->expects($this->once())
            ->method('newTopic')
            ->with('php-unit-producer-topic')
            ->willReturn($topic = $this->createMock(ProducerTopic::class))
        ;

        $topic->expects($this->never())->method('producev');
        $this->producer->expects($this->never())->method('poll');
        $this->producer->expects($this->never())->method('flush');

        self::expectException(LogicException::class);
        self::expectExceptionMessage('The "producer_partition_id_assignment" option type must be integer, string given in "php-unit-transport" transport.');
        self::expectExceptionCode(0);
        $connection->publish('body', ['type' => FakeMessage::class]);
    }

    public function testPublishWithMessageFlagsException()
    {
        $connection = Connection::builder(
            [
                'transport_name' => 'php-unit-transport',
                'producer_topic' => 'php-unit-producer-topic',
                'producer_partition_id_assignment' => 1,
                'producer_message_flags_block' => 'flag',
            ],
            $this->factory
        );

        $this->producer->expects($this->once())
            ->method('newTopic')
            ->with('php-unit-producer-topic')
            ->willReturn($topic = $this->createMock(ProducerTopic::class))
        ;

        $topic->expects($this->never())->method('producev');
        $this->producer->expects($this->never())->method('poll');
        $this->producer->expects($this->never())->method('flush');

        self::expectException(LogicException::class);
        self::expectExceptionMessage('The "producer_message_flags_block" option type must be boolean, string given in "php-unit-transport" transport.');
        self::expectExceptionCode(0);
        $connection->publish('body', ['type' => FakeMessage::class]);
    }

    public function testPublishWithPollTimeoutException()
    {
        $connection = Connection::builder(
            [
                'transport_name' => 'php-unit-transport',
                'producer_topic' => 'php-unit-producer-topic',
                'producer_partition_id_assignment' => 1,
                'producer_message_flags_block' => true,
                'producer_poll_timeout_ms' => 'poll',
            ],
            $this->factory
        );

        $this->producer->expects($this->once())
            ->method('newTopic')
            ->with('php-unit-producer-topic')
            ->willReturn($topic = $this->createMock(ProducerTopic::class))
        ;
        $topic->expects($this->once())
            ->method('producev')
            ->with(1, RD_KAFKA_MSG_F_BLOCK, 'body');

        $this->producer->expects($this->never())->method('poll');
        $this->producer->expects($this->never())->method('flush');

        self::expectException(LogicException::class);
        self::expectExceptionMessage('The "producer_poll_timeout_ms" option type must be integer, string given in "php-unit-transport" transport.');
        self::expectExceptionCode(0);
        $connection->publish('body', ['type' => FakeMessage::class]);
    }

    public function testPublishWithFlushTimeoutException()
    {
        $connection = Connection::builder(
            [
                'transport_name' => 'php-unit-transport',
                'producer_topic' => 'php-unit-producer-topic',
                'producer_partition_id_assignment' => 1,
                'producer_message_flags_block' => true,
                'producer_poll_timeout_ms' => 10,
                'producer_flush_timeout_ms' => 'flush',
            ],
            $this->factory
        );

        $this->producer->expects($this->once())
            ->method('newTopic')
            ->with('php-unit-producer-topic')
            ->willReturn($topic = $this->createMock(ProducerTopic::class))
        ;
        $topic->expects($this->once())
            ->method('producev')
            ->with(1, RD_KAFKA_MSG_F_BLOCK, 'body');

        $this->producer->expects($this->once())->method('poll')->with(10);
        $this->producer->expects($this->never())->method('flush');

        self::expectException(LogicException::class);
        self::expectExceptionMessage('The "producer_flush_timeout_ms" option type must be integer, string given in "php-unit-transport" transport.');
        self::expectExceptionCode(0);
        $connection->publish('body', ['type' => FakeMessage::class]);
    }

    public function testPublishWithCustomOptions()
    {
        $connection = Connection::builder(
            [
                'transport_name' => 'php-unit-transport',
                'producer_topic' => 'php-unit-producer-topic',
                'producer_partition_id_assignment' => 1,
                'producer_message_flags_block' => true,
                'producer_poll_timeout_ms' => 10,
                'producer_flush_timeout_ms' => 20000,
            ],
            $this->factory
        );

        $this->producer->expects($this->once())
            ->method('newTopic')
            ->with('php-unit-producer-topic')
            ->willReturn($topic = $this->createMock(ProducerTopic::class))
        ;
        $topic->expects($this->once())
            ->method('producev')
            ->with(1, RD_KAFKA_MSG_F_BLOCK, 'body');

        $this->producer->expects($this->once())->method('poll')->with(10);
        $this->producer->expects($this->once())->method('flush')->with(20000);

        $connection->publish('body', ['type' => FakeMessage::class]);
    }

    public function testGet()
    {
        $connection = Connection::builder(
            [
                'transport_name' => 'php-unit-transport',
                'consumer_topics' => ['php-unit-consumer'],
                'group.id' => 'php-unit-group-id',
            ],
            $this->factory
        );

        $this->consumer->expects($this->once())->method('subscribe')->with(['php-unit-consumer']);
        $this->consumer->expects($this->once())->method('consume')
            ->with(10000)->willReturn(new Message());

        $connection->get();
    }

    public function testGetWithGroupIdMissing()
    {
        $connection = Connection::builder(
            [
                'transport_name' => 'php-unit-transport',
            ],
            $this->factory
        );

        $this->consumer->expects($this->never())->method('subscribe');
        $this->consumer->expects($this->never())->method('consume');

        self::expectException(LogicException::class);
        self::expectExceptionMessage('The transport "php-unit-transport" is not configured to consume messages because "group.id" option is missing.');
        self::expectExceptionCode(0);

        $connection->get();
    }

    public function testGetWithTopicsMissing()
    {
        $connection = Connection::builder(
            [
                'transport_name' => 'php-unit-transport',
                'group.id' => 'php-unit-group-id',
            ],
            $this->factory
        );

        $this->consumer->expects($this->never())->method('subscribe');
        $this->consumer->expects($this->never())->method('consume');

        self::expectException(LogicException::class);
        self::expectExceptionMessage('The transport "php-unit-transport" is not configured to consume messages because "consumer_topics" option is missing.');
        self::expectExceptionCode(0);

        $connection->get();
    }

    public function testGetWithTopicsException()
    {
        $connection = Connection::builder(
            [
                'transport_name' => 'php-unit-transport',
                'group.id' => 'php-unit-group-id',
                'consumer_topics' => 'php-unit-consumer',
            ],
            $this->factory
        );

        $this->consumer->expects($this->never())->method('subscribe');
        $this->consumer->expects($this->never())->method('consume');

        self::expectException(LogicException::class);
        self::expectExceptionMessage('The "consumer_topics" option type must be array, string given in "php-unit-transport" transport.');
        self::expectExceptionCode(0);

        $connection->get();
    }

    public function testGetWithConsumeTimoutException()
    {
        $connection = Connection::builder(
            [
                'transport_name' => 'php-unit-transport',
                'group.id' => 'php-unit-group-id',
                'consumer_topics' => ['php-unit-consumer'],
                'consumer_consume_timeout_ms' => 'flush',
            ],
            $this->factory
        );

        $this->consumer->expects($this->once())->method('subscribe')->with(['php-unit-consumer']);
        $this->consumer->expects($this->never())->method('consume');

        self::expectException(LogicException::class);
        self::expectExceptionMessage('The "consumer_consume_timeout_ms" option type must be integer, string given in "php-unit-transport" transport.');
        self::expectExceptionCode(0);

        $connection->get();
    }

    public function testGetWithConsumeException()
    {
        $connection = Connection::builder(
            [
                'transport_name' => 'php-unit-transport',
                'group.id' => 'php-unit-group-id',
                'consumer_topics' => ['php-unit-consumer'],
                'consumer_consume_timeout_ms' => 20000,
            ],
            $this->factory
        );

        $this->consumer->expects($this->once())->method('subscribe')->with(['php-unit-consumer']);
        $this->consumer->expects($this->once())->method('consume')
            ->with(20000)->willThrowException(new Exception('kafka consume error', 1));

        self::expectException(TransportException::class);
        self::expectExceptionMessage('kafka consume error');
        self::expectExceptionCode(0);

        $connection->get();
    }

    public function testGetWithCustomOptions()
    {
        $connection = Connection::builder(
            [
                'transport_name' => 'php-unit-transport',
                'group.id' => 'php-unit-group-id',
                'consumer_topics' => ['php-unit-consumer'],
                'consumer_consume_timeout_ms' => 20000,
            ],
            $this->factory
        );

        $this->consumer->expects($this->once())->method('subscribe')->with(['php-unit-consumer']);
        $this->consumer->expects($this->once())->method('consume')
            ->with(20000)->willReturn(new Message());

        $connection->get();
    }

    public function testSetupWithBrokerMissing()
    {
        $connection = Connection::builder(
            [
                'transport_name' => 'php-unit-transport',
            ],
            $this->factory
        );

        self::expectException(LogicException::class);
        self::expectExceptionMessage('The "metadata.broker.list" option is required for the Kafka Messenger transport "php-unit-transport".');
        self::expectExceptionCode(0);

        $connection->setup();
    }

    public function testSetupWithOptionsMissing()
    {
        $connection = Connection::builder(
            [
                'transport_name' => 'php-unit-transport',
                'metadata.broker.list' => 'localhost:9092',
            ],
            $this->factory
        );

        self::expectException(LogicException::class);
        self::expectExceptionMessage('At least one of "consumer_topics" or "producer_topic" options is required for the Kafka Messenger transport "php-unit-transport".');
        self::expectExceptionCode(0);

        $connection->setup();
    }
}
