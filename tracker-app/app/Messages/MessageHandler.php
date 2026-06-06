<?php

declare(strict_types=1);

namespace App\Messages;

abstract class MessageHandler
{
    public abstract function handle(object $message): MessageResult;
}
