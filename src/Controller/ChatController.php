<?php

namespace App\Controller;

use stdClass;
use Google\Client as Google_Client;
use Google\Service\YouTube as Google_Service_YouTube;
use BotMan\BotMan\BotMan;
use Tectalic\OpenAi\Manager;
use Tectalic\OpenAi\Models\ChatCompletions\CreateRequest;
use GuzzleHttp\Client;
use BotMan\BotMan\BotManFactory;
use BotMan\Drivers\Web\WebDriver;
use BotMan\BotMan\Cache\SymfonyCache;
use BotMan\BotMan\Drivers\DriverManager;
use App\ChatBot\Middleware\ReceiveMiddleware;
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
    
    #[Route('/chat', name: 'chat_index')]
    public function index(): Response
    {
        return $this->render('chat/index.html.twig');
    }

    #[Route('/chat/message', name: 'chat_message')]
    public function message(SymfonyCache $symfonyCache): Response
    {
        DriverManager::loadDriver(WebDriver::class);
        $botman = BotManFactory::create([], $symfonyCache);
        $botman->middleware->received(new ReceiveMiddleware());

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
        
        // remote APIs
        // --------------------------------

        // Define a function to handle weather requests
        function handleWeatherRequest($botman, $location, $messagePrefix = '', $context) {
            $response = $context->fetchWeatherData($location);
            $botman->reply(sprintf('%sLe temps à %s est : <img src="%s" alt="icon"/>', $messagePrefix, $response->location->name, $response->current->weather_icons[0]));
            $botman->reply(sprintf('La température est de %s degrés et la température ressentie est de %s degrés.', $response->current->temperature, $response->current->feelslike));
            $botman->reply(sprintf('L\'humidité est de %s pourcents et la vitesse du vent est de %s km/h.', $response->current->humidity, $response->current->wind_speed));
        }

        $context = $this; // Assign the current context to a variable

        // Handle weather requests in French
        $botman->hears('prévision météo à {location}', function ($botman, $location) use ($context) {
            handleWeatherRequest($botman, $location, 'Prévision météo : ', $context);
        });

        $botman->hears('météo à {location}', function ($botman, $location) use ($context) {
            handleWeatherRequest($botman, $location, 'Météo : ', $context);
        });

        // Handle weather requests in English
        $botman->hears('weather in {location}', function ($botman, $location) use ($context) {
            handleWeatherRequest($botman, $location, '', $context);
        });

        // attachment
        // --------------------------------
        $botman->hears(
            '(.*)gif {name}',
            function (BotMan $bot, string $name) {
                $bot->reply(
                    OutgoingMessage::create('GIF: ')
                        ->withAttachment($this->fetchGiphyGif($name))
                );
            }
        );

        // data provider: user info
        // --------------------------------
        // Define a function to handle name requests
        function handleNameRequest($botman, $namePrefix, $name) {
            $botman->userStorage()->save(['name' => $name]);
            $botman->reply($namePrefix . $name);
        }

        // Define a function to handle name retrieval requests
        function handleNameRetrievalRequest($botman, $namePrefix) {
            $botman->reply('Your name is ' . $botman->userStorage()->get('name'));
        }

        // Define a function to handle name retrieval requests in French
        function handleNameRetrievalRequestFR($botman, $namePrefix) {
            $botman->reply('Ton nom est ' . $botman->userStorage()->get('name'));
        }

        // User info API
        // --------------------------------

        // Handle name requests in English
        $botman->hears('my name is {name}(.*)', function ($botman, $name) {
            handleNameRequest($botman, 'Hello, ', $name);
        });

        $botman->hears('je m\'appelle {name}(.*)', function ($botman, $name) {
            handleNameRequest($botman, 'Salut, ', $name);
        });

        $botman->hears('say my name(.*)', function ($botman) {
            handleNameRetrievalRequest($botman, '');
        });

        $botman->hears('what\'s my name?(.*)', function ($botman) {
            handleNameRetrievalRequest($botman, '');
        });

        $botman->hears('name(.*)', function ($botman) {
            handleNameRetrievalRequest($botman, '');
        });

        // Handle name requests in French
        $botman->hears('mon nom est {name}(.*)', function ($botman, $name) {
            handleNameRequest($botman, 'Salut, ', $name);
        });

        $botman->hears('dis mon nom(.*)', function ($botman) {
            handleNameRetrievalRequestFR($botman, '');
        });

        $botman->hears('quel est mon nom?(.*)', function ($botman) {
            handleNameRetrievalRequestFR($botman, '');
        });

        $botman->hears('nom(.*)', function ($botman) {
            handleNameRetrievalRequestFR($botman, '');
        });


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
                $conversation = new QuestionConversation();
                $bot->startConversation($conversation);
            }
        );

        // Youtube
        // --------------------------------

        $botman->hears(
            '(.*)youtube {search}',
            function (BotMan $bot, $search) {
                $developerKey = $this->parameterBag->get('YOUTUBE_API_KEY');
                $googleClient = new Google_Client();
                $googleClient->setDeveloperKey($developerKey);
        
                $youtubeService = new Google_Service_YouTube($googleClient);
        
                $searchResponse = $youtubeService->search->listSearch('id,snippet', [
                    'q' => $search,
                    'type' => 'video',
                    'maxResults' => 1
                ]);
        
                if (count($searchResponse->items) === 0) {
                    $bot->reply('Aucune vidéo trouvée.');
                } else {
                    $videoId = $searchResponse->items[0]->id->videoId;
                    $videoUrl = $this->fetchYoutubeVideo($videoId);
        
                    if ($videoUrl) {
                        $bot->reply($videoUrl);
                    }
                }
            }
        );
        
        // Remote API: GNews
        // --------------------------------
        $botman->hears(
            '(.*)actualités {search}',
            function (BotMan $bot, $search) {
                $api_key = $this->parameterBag->get('GNEWS_API_KEY');

                // Set the API endpoint and parameters
                $url = 'https://gnews.io/api/v4/search?q=%s&lang=fr&token=' .$api_key;
                $params = [
                    'lang' => 'fr',
                    'token' => $api_key,
                    'q' => $search,
                ];

                // Make the API request
                $response = json_decode(file_get_contents($url . '?' . http_build_query($params)));

                // Extracting the articles from the response
                $articles = $response->articles;

                // Do something with the articles (e.g. reply with a message)
                foreach ($articles as $article) {
                    $date = date('d-m-Y', strtotime($article->publishedAt));
                    $bot->reply('Actualité : '.'<br>'.'<img style="width:200px;height:150px;" src="'. $article->image . '"/>'.'<br>'.'<a href="' . $article->url . '" target="_blank">' . $article->title . '</a>'.'<br>'.'Publié le : ' . $date . ' | Source : ' . $article->source->name);
                }                    
            }
        );

        $botman->hears(
            '(.*)news {search}',
            function (BotMan $bot, $search) {
                $api_key = $this->parameterBag->get('GNEWS_API_KEY');

                // Set the API endpoint and parameters
                $url = 'https://gnews.io/api/v4/search?q=%s&lang=en&token=' . $api_key;
                $params = [
                    'lang' => 'fr',
                    'token' => $api_key,
                    'q' => $search,
                ];

                // Make the API request
                $response = json_decode(file_get_contents($url . '?' . http_build_query($params)));

                // Extract the articles from the response
                $articles = $response->articles;

                // Do something with the articles (e.g. reply with a message)
                foreach ($articles as $article) {
                    $date = date('m-d-Y', strtotime($article->publishedAt));
                    $bot->reply('News : '.'<br>'.'<img style="width:200px;height:150px;" src="'. $article->image . '"/>'.'<br>'.'<a href="' . $article->url . '" target="_blank">' . $article->title . '</a>'.'<br>'.'Published at : ' . $date . ' | Origin : ' . $article->source->name);
                }
            }
        );

        // fallback, nothing matched, go to openAI
        // --------------------------------
        
       $botman->fallback(function (BotMan $bot) {
        $open_ai_key = $this->parameterBag->get('OPENAI_API_KEY');
        $openaiClient = Manager::build(new Client(), new \Tectalic\OpenAi\Authentication($open_ai_key));

        $response = $openaiClient->chatCompletions()->create(
            new CreateRequest([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                    ['role' => 'user', 'content' => $bot->getMessage()->getText()],
                ],
                'temperature' => 0.7,
                'max_tokens' => 500,
                'frequency_penalty' => 0.3,
                'presence_penalty' => 0.5,
                'n' => 1,
                'stop' => null,
                'best_of' => 1
            ])
        )->toModel();

        if (
            isset($response->choices) &&
            isset($response->choices[0]) &&
            isset($response->choices[0]->message) &&
            isset($response->choices[0]->message->content)
        ) {
            $result = $response->choices[0]->message->content;
            $bot->reply($result);
        } else {
            $bot->reply("Une erreur est survenue dans la réponse d'OpenAI.");
        }
    });

        $botman->listen();

        return new Response();
    }

    #[Route('/chat/frame', name: 'chat_frame')]
    public function chatframe(): Response
    {
        return $this->render('chat/frame.html.twig');
    }

    function fetchWeatherData(string $location): stdClass
    {
        // Récupération de la clé API à partir de la variable d'environnement
        $weather_api_key= $this->parameterBag->get('WEATHER_API_KEY');
        $url = 'http://api.weatherstack.com/current?access_key='.$weather_api_key.'&query='.urlencode($location);

        return json_decode(file_get_contents($url));
    }

    private function fetchGiphyGif(string $name): Image
    {
        // Récupération de la clé API à partir de la variable d'environnement
        $giphy_api_key= $this->parameterBag->get('GIPHY_API_KEY');
        $url = sprintf('https://api.giphy.com/v1/gifs/search?api_key=%s&q=%s&limit=1', $giphy_api_key, urlencode($name));
        $response = json_decode(file_get_contents($url));

        return new Image($response->data[0]->images->downsized_large->url);
    }


    private function fetchYoutubeVideo(string $videoId): ?string
    {
        $developerKey = $this->parameterBag->get('YOUTUBE_API_KEY');
        $url = sprintf('https://www.googleapis.com/youtube/v3/videos?id=%s&key=%s&part=snippet,contentDetails,player', $videoId, $developerKey);
        $response = json_decode(file_get_contents($url));

        if (empty($response->items)) {
            return null;
        }

        $videoUrl = 'https://www.youtube.com/embed/' . $response->items[0]->id;
        $iframe = sprintf('<iframe width="260" height="215" src="%s" frameborder="0" allowfullscreen></iframe>', $videoUrl);

        return $iframe;
    }

}
