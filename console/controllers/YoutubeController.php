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
    public string $logCategory = YoutubeUrbanSource::SOURCE_TYPE;
    
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
        $sourceTag = TermTaxonomy::findOrCreate(YoutubeUrbanSource::SOURCE_TYPE);
        $minDate = YoutubeUrbanSource::getMinDate();
        
        /** @var YoutubeUrbanSource[] $urbanSources */
        $urbanSources = $this->fetchSources(YoutubeUrbanSource::class);
        foreach ($urbanSources as $urbanSource) {            
            $this->logInfo("Источник: $urbanSource->url [" . YoutubeUrbanSource::SOURCE_TYPE . "]",
                Console::BOLD, Console::BG_CYAN);
            
            try {
                $items = $urbanSource->getUpdates();
            } catch (\Exception $exception) {
                $this->logError($exception->getMessage());
                $this->logError(print_r($urbanSource->attributes, true));
    
                continue;
            }
            
            $latestRecord = $urbanSource->latest_record;
            foreach ($items as $item) {
                $videoId = $item['contentDetails']['upload']['videoId'] ?? null;
                
                if (!$videoId) {
                    continue;
                }
    
                try {
                    $videoInfo = $urbanSource->getVideoInfo($videoId);
                } catch (\Exception $exception) {
                    $this->logError($exception->getMessage());
                    $this->logError(print_r($urbanSource->attributes, true));
        
                    continue;
                }
                
                if (!$videoInfo) {                    
                    continue;
                }
    
                $itemPublicationDate = strtotime($videoInfo['publishedAt']);
                if ($itemPublicationDate <= $latestRecord || $itemPublicationDate < $minDate) {
                    break;
                }
    
                $post = new Post();
                $post->setTitle($videoInfo['title']);
                $post->setContent($videoInfo['description']);
                $post->setDate($itemPublicationDate);
                
                $videoLink = YoutubeUrbanSource::getVideoLink($videoId);
                $post->addLink($videoLink);
                
                if ($post->save()) {
                    $post->addTag($sourceTag);
                    $post->addTag($videoInfo['channelTitle']);
                    
                    $urbanSource->updateLatestRecord($itemPublicationDate);
                    
                    $this->logInfo("Новое видео: $videoLink {$post->post_date} \"{$post->post_title}...\"");
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
