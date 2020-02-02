<?php

namespace console\controllers;

use console\models\Post;
use console\models\TermTaxonomy;
use console\models\TwitterUrbanSource;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Управление парсером Twitter
 *
 * Class TwitterController
 * @package console\controllers
 */
class TwitterController extends BaseController
{
    public string $logCategory = TwitterUrbanSource::SOURCE_TYPE;
    
    /**
     * Получить новые твиты
     *
     * @return int
     * @throws \yii\base\Exception
     * @throws \yii\web\NotFoundHttpException
     * @throws \ReflectionException
     */
    public final function actionIndex(): int
    {
        $sourceTag = TermTaxonomy::findOrCreate(TwitterUrbanSource::SOURCE_TYPE);
        $minDate = TwitterUrbanSource::getMinDate();
        
        /** @var TwitterUrbanSource[] $urbanSources */
        $urbanSources = $this->fetchSources(TwitterUrbanSource::class);
        foreach ($urbanSources as $urbanSource) {
            $this->logInfo("Источник: $urbanSource->url [" . TwitterUrbanSource::SOURCE_TYPE . "]",
                Console::BOLD, Console::BG_CYAN);
            
            try {
                $items = $urbanSource->getUpdates();
            } catch (\Exception $e) {
                $this->logError($e->getMessage());
                $this->logError(print_r($urbanSource->attributes, true));
                continue;
            }
            
            $latestRecord = $urbanSource->latest_record;
            foreach ($items as $item) {
                $itemPublicationDate = strtotime($item['created_at']);
                if ($itemPublicationDate <= $latestRecord || $itemPublicationDate < $minDate) {
                    break;
                }
    
                $post = new Post();
                $post->setTitle($item['text']);
                $post->setContent($item['text']);
                $post->setDate($itemPublicationDate);
                
                $tweetLink = TwitterUrbanSource::getTweetLink($item['id_str']);
                $post->addLink($tweetLink);
                
                if ($post->save()) {
                    $post->addTag($sourceTag);
                    $post->addTag($urbanSource->getUserScreenName());
                    
                    $urbanSource->updateLatestRecord($itemPublicationDate);
                    
                    $this->logInfo("Новый твит: $tweetLink {$post->post_date} \"{$post->post_title}...\"");
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
