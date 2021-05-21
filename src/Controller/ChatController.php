<?php

namespace App\Controller;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
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

        // basic
        // --------------------------------
        $botman->hears(
            'hi',
            function (BotMan $bot) {
                $bot->reply('Hello, I am a Chatbot in Symfony 5!');
            }
        );


        // remote API
        // --------------------------------
        $botman->hears(
            'weather in {location}',
            function (BotMan $bot, string $location) {
                $response = $this->fetchWeatherData($location);
                $bot->reply(sprintf('<img src="%s" alt="icon"/>', $response->current->weather_icons[0]));
                $bot->reply(sprintf('Weather in %s is %s!', $response->location->name, $response->current->weather_descriptions[0]));
            }
        );

        // attachment
        // --------------------------------
        $botman->hears(
            '/gif {name}',
            function (BotMan $bot, string $name) {
                $bot->reply(
                    OutgoingMessage::create('this is your gif')
                        ->withAttachment($this->fetchGiphyGif($name))
                );
            }
        );

        // data provider: user info
        // --------------------------------
        $botman->hears(
            'my name is {name}',
            function (BotMan $bot, string $name) {
                $bot->userStorage()->save(['name' => $name]);
                $bot->reply('Hello, ' . $name);
            }
        );


        $botman->hears(
            'say my name',
            function (BotMan $bot) {
                $bot->reply('Your name is ' . $bot->userStorage()->get('name'));
            }
        );


        // fallback, nothing matched
        // --------------------------------
        $botman->fallback(
            function (BotMan $bot) {
                $bot->reply('Sorry, I did not understand.');
                $bot->reply('TRY: Weather in {location}');
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
        //😀 dirty, but simple and fine for me in POC
        $url = 'http://api.weatherstack.com/current?access_key=18895c6bcedd7b4a6194ffd07400025a&query=' . urlencode($location);

        return json_decode(file_get_contents($url));
    }

    private function fetchGiphyGif(string $name): Image
    {
        $url = sprintf('https://api.giphy.com/v1/gifs/search?api_key=zlPPjtJejAAj56KPc5iCjIDqeMsgiD2m&q=%s&limit=1', urlencode($name));
        $response = json_decode(file_get_contents($url));

        return new Image($response->data[0]->images->downsized_large->url);
    }
}
