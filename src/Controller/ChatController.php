<?php

namespace App\Controller;

use stdClass;
use App\ChatBot\Middleware\ReceiveMiddleware;
use BotMan\BotMan\BotMan;
use Orhanerday\OpenAi\OpenAi;
use BotMan\BotMan\BotManFactory;
use BotMan\Drivers\Web\WebDriver;
use BotMan\BotMan\Cache\SymfonyCache;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\BotMan\Messages\Attachments\Image;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\ChatBot\Conversation\QuestionConversation;
use App\ChatBot\Conversation\OnBoardingConversation;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ChatController extends AbstractController
{   
    public function __construct(ParameterBagInterface $parameterBag) {
        $this->parameterBag = $parameterBag;
    }
    
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
    public function message(SymfonyCache $symfonyCache): Response
    {
        DriverManager::loadDriver(WebDriver::class);
        $botman = BotManFactory::create([], $symfonyCache);

        $botman->middleware->received(new ReceiveMiddleware());

        $botman->hears(
            '(.*)',
            function (BotMan $bot, string $prompt) {
                $open_ai_key = $this->parameterBag->get('OPENAI_API_KEY');
                $openai = new OpenAI($open_ai_key);
                $response = json_decode($openai->completion([
                    'model' =>'text-davinci-003',
                    'prompt' => $prompt,
                    'temperature' => 0.5,
                    'max_tokens' => 500,
                    'frequency_penalty' => 0.4,
                    'presence_penalty' => 0
                ]), true);

                if(array_key_exists('choices', $response) && array_key_exists(0, $response['choices']) && array_key_exists('text', $response['choices'][0])) {
                    $result = $response['choices'][0]['text'];
                    $bot->reply($result);
                } else {
                    $bot->reply("Error occured in openai response");
                }        
            }
        );
        // basic
        // --------------------------------
        $botman->hears(
            'hi(.*)',
            function (BotMan $bot) {
                $bot->reply('Hello, I am a chatBot!');
            }
        );

        $botman->hears(
            'salut(.*)',
            function (BotMan $bot) {
                $bot->reply('Salut, je suis un chatBot!');
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

        $botman->hears(
            'prévision météo à {location}',
            function (BotMan $bot, string $location) {
                $response = $this->fetchWeatherData($location);
                $bot->reply(sprintf('<img src="%s" alt="icon"/>', $response->current->weather_icons[0]));
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
            'my name is {name}(.*)',
            function (BotMan $bot, string $name) {
                $bot->userStorage()->save(['name' => $name]);
                $bot->reply('Hello, ' . $name);
            }
        );

        $botman->hears(
            'mon nom est {name}(.*)',
            function (BotMan $bot, string $name) {
                $bot->userStorage()->save(['name' => $name]);
                $bot->reply('Salut, ' . $name);
            }
        );

        $botman->hears(
            'je m\'appelle {name}(.*)',
            function (BotMan $bot, string $name) {
                $bot->userStorage()->save(['name' => $name]);
                $bot->reply('Salut, ' . $name);
            }
        );

        $botman->hears(
            'say my name(.*)',
            function (BotMan $bot) {
                $bot->reply('Your name is ' . $bot->userStorage()->get('name'));
            }
        );

        $botman->hears(
            'dis mon nom(.*)',
            function (BotMan $bot) {
                $bot->reply('Ton nom est ' . $bot->userStorage()->get('name'));
            }
        );

        $botman->hears(
            'what\'s my name?(.*)',
            function (BotMan $bot) {
                $bot->reply('Your name is ' . $bot->userStorage()->get('name'));
            }
        );

        $botman->hears(
            'quel est mon nom?(.*)',
            function (BotMan $bot) {
                $bot->reply('Ton nom est ' . $bot->userStorage()->get('name'));
            }
        );

        // User information:
        // botman will provide the user information by passing user object implemented UserInterface
        // --------------------------------
        $botman->hears(
            'name(.*)',
            function (BotMan $bot) {
                $user = $bot->getUser();
                // $bot->reply('First name: ' . $user->getFirstName());
                $bot->reply('Your name is: ' .  $bot->userStorage()->get('name'));
            }
        );

        $botman->hears(
            'nom(.*)',
            function (BotMan $bot) {
                $user = $bot->getUser();
                // $bot->reply('First name: ' . $user->getFirstName());
                $bot->reply('Ton nom est: ' .  $bot->userStorage()->get('name'));
            }
        );

        // conversation
        // --------------------------------
        $botman->hears(
            'survey(.*)',
            function (BotMan $bot) {
                $bot->reply('I am going to start the on-boarding conversation');
                $bot->startConversation(new OnBoardingConversation());
            }
        );

        $botman->hears(
            'help(.*)',
            function (BotMan $bot) {
                $bot->reply('This is the help information.');
            }
        )->skipsConversation();

        $botman->hears(
            'stop(.*)',
            function (BotMan $bot) {
                $bot->reply('I will stop our conversation.');
            }
        )->stopsConversation();

        // question with buttons
        // --------------------------------
        $botman->hears(
            'question(.*)',
            function (BotMan $bot) {
                $bot->startConversation(new QuestionConversation());
            }
        );

        // fallback, nothing matched
        // --------------------------------
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
