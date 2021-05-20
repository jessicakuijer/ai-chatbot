<?php

namespace App\Controller;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Web\WebDriver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChatbotController extends AbstractController
{
    /**
     * @Route("/", name="homepage")
     */
    public function index(): Response
    {
        return $this->render('chat/index.html.twig');
    }

    /**
     * @Route("/message", name="message")
     */
    public function message(): Response
    {
        DriverManager::loadDriver(WebDriver::class);
        $config = [];
        $botman = BotManFactory::create($config);

        $botman->hears(
            'hi',
            function (BotMan $bot) {
                $bot->reply('Hello, I am a Chatbot in Symfony 5!');
            }
        );

        $botman->fallback(
            function (BotMan $bot) {
                $bot->reply('Sorry, I did not understand.');
            }
        );

        $botman->listen();

        return new Response();
    }

    /**
     * @Route("/chatframe", name="chatframe")
     */
    public function chatframe(): Response
    {
        return $this->render('chat/chatframe.html.twig');
    }
}
