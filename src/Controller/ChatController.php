<?php

namespace App\Controller;

use stdClass;
use Anthropic;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use App\Cache\CustomSymfonyCache;
use BotMan\Drivers\Web\WebDriver;
use Google\Client as Google_Client;
use BotMan\BotMan\Cache\SymfonyCache;
use BotMan\BotMan\Drivers\DriverManager;
use App\ChatBot\Middleware\ReceiveMiddleware;
use BotMan\BotMan\Messages\Attachments\Image;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\ChatBot\Conversation\QuestionConversation;
use App\ChatBot\Conversation\OnBoardingConversation;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use Google\Service\YouTube as Google_Service_YouTube;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ChatController extends AbstractController
{   
    private $anthropicClient;
    private $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
        $this->anthropicClient = Anthropic::factory()
            ->withApiKey($this->parameterBag->get('CLAUDE_API_KEY'))
            ->withHttpHeader('anthropic-version', '2023-06-01') // Ajout de l'en-tête de version
            ->make();
    }

    private function waitForClaudeResponse($text, $timeout = 30)
    {
        try {
            $response = $this->anthropicClient->messages()->create([
                'model' => 'claude-3-5-sonnet-20240620',
                'max_tokens' => 500,
                'messages' => [
                    ['role' => 'user', 'content' => $text],
                ],
                'temperature' => 0.7,
            ]);

            if ($response && isset($response->content[0]->text)) {
                return $response->content[0]->text;
            }
        } catch (\Exception $e) {
            error_log('Erreur Claude API: ' . $e->getMessage());
            return "Je suis désolé, mais je rencontre actuellement des difficultés techniques. Erreur : " . $e->getMessage();
        }

        return "Je n'ai pas pu générer une réponse appropriée. Pouvez-vous reformuler votre question ?";
    }

    
    #[Route('/chat', name: 'chat_index')]
    public function index(): Response
    {
        return $this->render('chat/index.html.twig');
    }

    #[Route('/chat/message', name: 'chat_message')]
    public function message(): Response
    {
        DriverManager::loadDriver(WebDriver::class);
        $botman = BotManFactory::create([], new CustomSymfonyCache());
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
            '^(?P<language>actualités|news) (?P<search>.*)$',
            function (BotMan $bot, $language, $search) {
                $api_key = $this->parameterBag->get('GNEWS_API_KEY');
        
                // Set the API endpoint and parameters
                $url = 'https://gnews.io/api/v4/search?q=%s&lang=%s&token=' . $api_key;
                $params = [
                    'lang' => ($language == 'actualités') ? 'fr' : 'en',
                    'token' => $api_key,
                    'q' => $search,
                ];
        
                // Make the API request
                $response = json_decode(file_get_contents($url . '?' . http_build_query($params)));

                // Extract the articles from the response
                $articles = $response->articles;
                
                // Do something with the articles (e.g. reply with source, title, date, image, url)
                foreach ($articles as $article) {
                    $date = date('d-m-Y', strtotime($article->publishedAt));
                    $source_label = ($language == 'actualités') ? 'Source' : 'Origin';
                    $bot->reply(ucfirst($language) . ' : ' .'<br>'.'<img style="width:200px;height:150px;" src="'. $article->image . '"/>'.'<br>'.'<a href="' . $article->url . '" target="_blank">' . $article->title . '</a>'.'<br>'.'Publié le : ' . $date . ' | ' . $source_label . ' : ' . $article->source->name);
                }
            }
        );
        
        

        // fallback, nothing matched, go to Claude Anthropic API
        // --------------------------------
        
        $botman->fallback(function (BotMan $bot) {
            $bot->typesAndWaits(2);
            $result = $this->waitForClaudeResponse($bot->getMessage()->getText(), 30);
            $bot->reply($result);
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
