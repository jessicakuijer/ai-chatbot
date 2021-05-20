<?php

namespace App\Controller;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Web\WebDriver;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChatController extends AbstractController
{
    /**
     * @Route("/", name="chat_index")
     */
    public function index(): Response
    {
        return $this->render('chat/index.html.twig');
    }

    /**
     * @Route("/chat/message", name="chat_message")
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

        $botman->hears(
            'weather in {location}',
            function (BotMan $bot, string $location) {
                $response = $this->fetchWeatherData($location);
                $bot->reply(sprintf('Weather in %s is great!', $response->location->name));
                $bot->reply(sprintf('<img src="%s" alt="icon"/>', $response->current->weather_icons[0]));
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
     * @Route("/chat/frame", name="chat_frame")
     */
    public function chatframe(): Response
    {
        return $this->render('chat/frame.html.twig');
    }

    private function fetchWeatherData(string $location): stdClass
    {
        $url = 'http://api.weatherstack.com/current?access_key=18895c6bcedd7b4a6194ffd07400025a&query=' . $location;

        return json_decode(file_get_contents($url));
    }
}
