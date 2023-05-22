<?php

namespace App\Messenger\Handler;

use App\Messenger\Message\InvoiceCreatedMessage;
use App\Messenger\Message\OrderPaidMessage;
use DateTime;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class OrderPaidHandler
{
    public function __construct(protected MessageBusInterface $bus)
    {
    }

    public function __invoke(OrderPaidMessage $message): void
    {
        // implement logic here
        // in this example we dispatch the invoice for the order paid
        $this->bus->dispatch(
            new InvoiceCreatedMessage(
                reference: sprintf('i%d%s', (new DateTime('now'))->getTimestamp(), $message->getReference()),
                amount: $message->getAmount(),
                status: 'paid'
            )
        );
    }
}
