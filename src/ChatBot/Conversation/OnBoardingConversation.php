<?php

declare(strict_types = 1);

use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;

final class OnBoardingConversation extends Conversation
{
    protected string $firstName;
    protected string $email;

    public function askFirstname()
    {
        $this->ask(
            'Hello! What is your firstname?',
            function (Answer $answer) {
                $this->firstName = $answer->getText();

                $this->say('Nice to meet you ' . $this->firstName);
                $this->askEmail();
            }
        );
    }

    public function askEmail()
    {
        $this->ask(
            'One more thing - what is your email?',
            function (Answer $answer) {
                $this->email = $answer->getText();

                $this->say('Great - that is all we need, ' . $this->firstName);
            }
        );
    }

    public function run()
    {
        $this->askFirstname();
    }
}
