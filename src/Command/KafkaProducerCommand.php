<?php

namespace App\Command;

use App\Messenger\Message\OrderPaidMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:messenger:producer',
    description: 'Send record to messenger transport.'
)]
class KafkaProducerCommand extends Command
{
    public const REF_ARGS = 'reference';
    public const AMOUNT_ARGS = 'amount';

    public function __construct(private readonly MessageBusInterface $bus, string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            // the command help shown when running the command with the "--help" option
            ->setHelp('This command allows you to send message to messenger transport.')
            ->addArgument(
                name: self::REF_ARGS,
                mode: InputArgument::REQUIRED,
                description: 'The order reference.'
            )
            ->addArgument(
                name: self::AMOUNT_ARGS,
                mode: InputArgument::REQUIRED,
                description: 'The order amount.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->bus->dispatch(
                new OrderPaidMessage(
                    reference: $input->getArgument(self::REF_ARGS),
                    amount: $input->getArgument(self::AMOUNT_ARGS)
                )
            );
        } catch (\Throwable $e) {
            $output->writeln(sprintf(
                '<error>Failed to send OrderPaidMessage to messenger transport with error message : %s</error>',
                $e->getMessage()
            ));

            return Command::FAILURE;
        }

        $output->writeln('<info>Message OrderPaidMessage send it to messenger transport</info>');

        return Command::SUCCESS;
    }
}
