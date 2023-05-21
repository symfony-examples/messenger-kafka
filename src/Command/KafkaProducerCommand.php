<?php

namespace App\Command;

use App\Messenger\Message\OrderPaidMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(
    name: 'app:kafka:producer',
    description: 'Send record to kafka topic.'
)]
class KafkaProducerCommand extends Command
{
    public const TOPIC_ARGS = 'topic';

    public function __construct(private SerializerInterface $serializer, private MessageBusInterface $bus, string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            // the command help shown when running the command with the "--help" option
            ->setHelp('This command allows you to send message to kafka topic.')
            ->addArgument(
                name: self::TOPIC_ARGS,
                mode: InputArgument::REQUIRED,
                description: 'The topic name.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->bus->dispatch(
            new OrderPaidMessage(reference: '3testRTY', amount: 3000)
        );
        $output->writeln(
            sprintf(
                '<info>Message send it to Kafka topic "%s" with messenger dispatcher</info>', $input->getArgument(self::TOPIC_ARGS)
            )
        );

        return Command::SUCCESS;


        $conf = new \RdKafka\Conf();
        $conf->set('metadata.broker.list', 'kafka:9092');

        $producer = new \RdKafka\Producer($conf);

        $topic = $producer->newTopic($input->getArgument(self::TOPIC_ARGS));

        $message = new OrderPaidMessage(reference: 'QWE', amount: 2100);
        $topic->producev(
            partition: RD_KAFKA_PARTITION_UA,
            msgflags: 0,
            payload: $this->serializer->serialize($message, 'json'),
            headers: ['type' => get_class($message)]
        );
        $producer->poll(0);

        for ($flushRetries = 0; $flushRetries < 5; $flushRetries++) {
            $result = $producer->flush(10000);

            if (RD_KAFKA_RESP_ERR_NO_ERROR === $result) {
                break;
            }
        }

        if (RD_KAFKA_RESP_ERR_NO_ERROR !== $result) {
            $output->writeln('<error>Was unable to flush, messages might be lost!</error>');

            return Command::FAILURE;
        }

        $output->writeln(
            sprintf(
                '<info>Message send it to Kafka topic "%s"</info>', $input->getArgument(self::TOPIC_ARGS)
            )
        );

        return Command::SUCCESS;
    }
}