<?php

namespace App\Messenger\Handler;

use App\Messenger\Message\OrderPaidMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class OrderPaidHandler
{
    public function __invoke(OrderPaidMessage $message)
    {
        dump($message);
    }
}
