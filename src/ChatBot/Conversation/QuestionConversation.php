<?php

declare(strict_types = 1);

namespace App\ChatBot\Conversation;

use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;

final class QuestionConversation extends Conversation
{
    public function run(): void
    {
        $this->askQuestion();
    }

    private function askQuestion(): void
    {
        $question = Question::create('Which animal do you like?')
            ->addButtons(
                [
                    Button::create('cat')->value('cat'),
                    Button::create('dog')->value('dog'),
                ]
            );
        $this->ask(
            $question,
            function (Answer $answer) {
                if ($answer->isInteractiveMessageReply()) {
                    $this->say('you selected: ' . $answer->getValue());
                } else {
                    $this->repeat();
                }
            }
        );
    }
}
