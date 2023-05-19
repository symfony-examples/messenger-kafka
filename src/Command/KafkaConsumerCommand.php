<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(
    name: 'app:kafka:consumer',
    description: 'Consume record from kafka topic.'
)]
class KafkaConsumerCommand extends Command
{
    public const TOPIC_ARGS = 'topic';

    private bool $shouldRun = true;

    public function __construct(private SerializerInterface $serializer, string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            // the command help shown when running the command with the "--help" option
            ->setHelp('This command allows you to consume message from kafka topic.')
            ->addArgument(
                name: self::TOPIC_ARGS,
                mode: InputArgument::REQUIRED,
                description: 'The topic name to consume.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(sprintf('<info>Kafka topic "%s" consumer is running</info>', self::TOPIC_ARGS));

        $conf = new \RdKafka\Conf();

        // Configure the group.id. All consumer with the same group.id will consume different partitions.
        $conf->set('group.id', 'myConsumerGroup');

        // Initial list of Kafka brokers
        $conf->set('metadata.broker.list', 'kafka:9092');

        // 'earliest': start from the beginning
        $conf->set('auto.offset.reset', 'earliest');

        // Emit EOF event when reaching the end of a partition
        $conf->set('enable.partition.eof', 'true');

        $consumer = new \RdKafka\KafkaConsumer($conf);

        // todo: multiple topics in command args
        // Subscribe to topic 'test'
        $consumer->subscribe([$input->getArgument(self::TOPIC_ARGS)]);

        $output->writeln('<comment>Waiting for partition assignment...</comment>');

        while ($this->shouldRun) {
            $message = $consumer->consume(10*1000);

            if (0 < $message->err) {
                switch ($message->err) {
                    case RD_KAFKA_RESP_ERR_NO_ERROR:
                        var_dump($message);
                        break;
                    case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                        $output->writeln('<info>Waiting for messages...</info>');
                        break;
                    case RD_KAFKA_RESP_ERR__TIMED_OUT:
                        $output->writeln('<error>Timed out</error>');
                        break;
                    default:
                        $output->writeln(sprintf('<error>%s</error>', $message->errstr()));
                        throw new \Exception($message->errstr(), $message->err);
                }
            } else {
                // create message
                // dispatch message
                // todo: deserialize headers
                dump($message->headers);
                if (array_key_exists('type', $message->headers)) {
                    if (class_exists($message->headers['type'])) {
                        $model = $this->serializer->deserialize($message->payload, $message->headers['type'], 'json');
                        dump($model);
                    } else {
                        $output->writeln(sprintf(
                            '<error>Message can not be deserialized because class with type %s don\'t exist</error>',
                            $message->headers['type']
                        ));
                    }
                } else {
                    $output->writeln(sprintf(
                        '<error>Message can not be deserialized because headers type %s is missing</error>',
                        $message->headers
                    ));
                }
            }
        }

        return Command::SUCCESS;
    }
}
