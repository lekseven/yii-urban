<?php

namespace console\controllers;

use console\models\Post;
use console\models\RssUrbanSource;
use console\models\TermTaxonomy;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Управление парсером фидов RSS
 *
 * Class RssController
 * @package console\controllers
 */
class RssController extends BaseController
{
    public string $logCategory = RssUrbanSource::SOURCE_TYPE;
    
    /**
     * Получить новые посты
     *
     * @return int
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\web\NotFoundHttpException
     * @throws \ReflectionException
     */
    public final function actionIndex(): int
    {
        $sourceTypeTag = TermTaxonomy::findOrCreate(RssUrbanSource::SOURCE_TYPE);
        $minDate = RssUrbanSource::getMinDate();
    
        /** @var RssUrbanSource[] $urbanSources */
        $urbanSources = $this->fetchSources(RssUrbanSource::class);
        foreach ($urbanSources as $urbanSource) {
            $this->logInfo("Источник: {$urbanSource->url} [" . RssUrbanSource::SOURCE_TYPE . "]",
                Console::BOLD, Console::BG_CYAN);
            
            $feed = null;
            try {
                $feed = \Feed::loadRss($urbanSource->url);
            } catch (\FeedException $e) {
                $this->logError($e->getMessage());
                continue;
            }
            
            if (!$feed) {
                continue;
            }
    
            $latestRecord = $urbanSource->latest_record;
            foreach ($feed->item as $item) {
                $itemPubDate = strtotime($item->pubDate);
                if ($itemPubDate <= $latestRecord || $itemPubDate < $minDate) {
                    break;
                }
    
                $post = new Post();
                $post->setTitle($item->title);
                $post->setContent($item->description ?? $item->{'content:encoded'});
                $post->setDate($itemPubDate);
                $post->addLink($item->link);
                
                if ($post->save()) {
                    $post->addTag($sourceTypeTag);
                    $post->addTag($urbanSource->url);
                    
                    $urbanSource->updateLatestRecord($itemPubDate);
    
                    $this->logInfo("Новый пост: {$item->link} {$post->post_date} \"{$post->post_title}...\"");
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
