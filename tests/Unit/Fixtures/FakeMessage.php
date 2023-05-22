<?php

namespace App\Tests\Unit\Fixtures;

class FakeMessage
{
    public function __construct(public string $message)
    {
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
