<?php

namespace console\controllers;

use console\models\Post;
use console\models\TermTaxonomy;
use console\models\YoutubeUrbanSource;
use Google_Exception;
use yii\base\Module;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Управление парсером Youtube
 *
 * Class YoutubeController
 * @package console\controllers
 */
class YoutubeController extends BaseController
{
    const BASE_CHANNEL_URL = 'https://www.youtube.com/channel/';
    
    const BASE_WATCH_URL = 'https://www.youtube.com/watch?v=';
    
    public string $logCategory = YoutubeUrbanSource::SOURCE_TYPE;
    
    public ?\Google_Client $client = null;
    
    public function __construct(string $id, Module $module, array $config = [])
    {
        $appName = \Yii::$app->params[YoutubeUrbanSource::SOURCE_TYPE]['appName'] ?? null;
        if (!$appName) {
            $this->logError('Параметр appName не установлен.');
            return;
        }
    
        $appKey = \Yii::$app->params[YoutubeUrbanSource::SOURCE_TYPE]['appKey'] ?? null;
        if (!$appKey) {
            $this->logError('Параметр appKey не установлен.');
            return;
        }
        
        $this->client = new \Google_Client();
        $this->client->setApplicationName($appName);
        $this->client->setDeveloperKey($appKey);
        
        parent::__construct($id, $module, $config);
    }
    
    /**
     * Получить новые видео
     *
     * @return int
     * @throws \yii\base\Exception
     * @throws \yii\web\NotFoundHttpException
     * @throws \ReflectionException
     */
    public final function actionIndex(): int
    {
        $service = new \Google_Service_YouTube($this->client);
        $sourceTag = TermTaxonomy::findOrCreate(YoutubeUrbanSource::SOURCE_TYPE);
        
        $period = \Yii::$app->params['period'] ?? YoutubeUrbanSource::MIN_DATE;
        $minDate = strtotime("-$period days");
        
        /** @var YoutubeUrbanSource[] $urbanSources */
        $urbanSources = $this->fetchSources(YoutubeUrbanSource::class);
        foreach ($urbanSources as $urbanSource) {
            $channelId = str_replace(self::BASE_CHANNEL_URL, '', $urbanSource->url);
            
            $this->logInfo("Источник: $urbanSource->url [" . YoutubeUrbanSource::SOURCE_TYPE . "]",
                Console::BOLD, Console::BG_CYAN);
            
            try {
                $response = $service->activities->listActivities('contentDetails', [
                    'channelId' => $channelId,
                ]);
            } catch (Google_Exception $exception) {
                $this->logError($exception->getMessage());
                $this->logError(print_r($urbanSource->attributes, true));
    
                continue;
            }
            
            $items = $response['items'];
            $latestRecord = $urbanSource->latest_record;
            foreach ($items as $item) {
                $videoId = $item['contentDetails']['upload']['videoId'] ?? null;
                
                if (!$videoId) {
                    continue;
                }
    
                try {
                    $response = $service->videos->listVideos('snippet', [
                        'id' => $videoId,
                    ]);
                } catch (Google_Exception $exception) {
                    $this->logError($exception->getMessage());
                    $this->logError(print_r($urbanSource->attributes, true));
        
                    continue;
                }
    
                $snippet = $response['items'][0]['snippet'] ?? null;
                
                if (!$snippet) {
                    $this->logError($response);
                    
                    continue;
                }
    
                $itemPublicationDate = strtotime($snippet['publishedAt']);
                if ($itemPublicationDate <= $latestRecord || $itemPublicationDate < $minDate) {
                    break;
                }
    
                $post = new Post();
                $post->setTitle($snippet['title']);
                $post->setContent($snippet['description']);
                $post->setDate($itemPublicationDate);
                $post->addLink(self::BASE_WATCH_URL . $videoId);
                
                if ($post->save()) {
                    $post->addTag($sourceTag);
                    $post->addTag($snippet['channelTitle']);
                    
                    $urbanSource->updateLatestRecord($itemPublicationDate);
                    
                    $this->logInfo("Новое видео: id='{$videoId}' date='{$post->post_date}' \"{$post->post_title}...\"");
                } else {
                    $this->logError("Item:");
                    $this->logError(print_r($item, true));
                    foreach ($post->getErrorSummary(true) as $message) {
                        $this->logError($message);
                    }
                }
            }
            
            $pause = rand(1, 10);
            $this->logInfo("Пауза $pause сек.", Console::FG_YELLOW);
            sleep($pause);
        }
    
        $this->logInfo("Завершено.", Console::FG_YELLOW);
        
        return ExitCode::OK;
    }
}
