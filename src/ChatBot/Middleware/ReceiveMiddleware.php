<?php

//declare(strict_types = 1);
namespace App\ChatBot\Middleware;

use DateTimeImmutable;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\Interfaces\Middleware\Received;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;

final class ReceiveMiddleware implements Received
{
    public function received(IncomingMessage $message, $next, BotMan $bot)
    {
        $message->setText($message->getText());
        $bot->typesAndWaits(2); // Attends 2 secondes

        return $next($message);
    }
}
