<?php

declare(strict_types = 1);

use BotMan\BotMan\BotMan;
use BotMan\BotMan\Interfaces\Middleware\Received;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;

final class ReceiveMiddleware implements Received
{
    public function received(IncomingMessage $message, $next, BotMan $bot)
    {
        $userSaid = $message->getText();
        $message->addExtras('timestamp', (new DateTimeImmutable('now'))->format('d-m-Y H:i:s'));
        $message->setText(sprintf('%s <- you said that.', $userSaid));

        return $next($message);
    }
}
