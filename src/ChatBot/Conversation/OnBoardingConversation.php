<?php

declare(strict_types = 1);

namespace App\ChatBot\Conversation;

use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;

final class OnBoardingConversation extends Conversation
{
    protected $firstName;
    protected $email;

    public function askFirstname()
    {
        $this->ask(
            'What is your firstname?',
            function (Answer $answer) {
                $this->firstName = $answer->getText();
                if (strlen(trim($this->firstName)) <= 2) {
                    $this->say('It is not a correct name. ');
                    return $this->repeat('Please provide your real name.');
                }

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
