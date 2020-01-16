<?php

namespace console\controllers;

use console\models\Post;
use console\models\RssUrbanSource;
use console\models\TermTaxonomy;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Управление парсером фидов RSS
 *
 * Class RssController
 * @package console\controllers
 */
class RssController extends Controller
{
    use SourceArguments;
    
    /**
     * @return int
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\web\NotFoundHttpException
     */
    public final function actionIndex(): int
    {
        $sourceTypeTag = TermTaxonomy::findOrCreate(RssUrbanSource::SOURCE_TYPE);
    
        $period = \Yii::$app->params['period'] ?? RssUrbanSource::MIN_DATE;
        $minDate = strtotime("-$period days");
    
        $urbanSources = $this->fetchSources(RssUrbanSource::SOURCE_TYPE);
        foreach ($urbanSources as $urbanSource) {
            $this->stdout("Источник: {$urbanSource->url} [" . RssUrbanSource::SOURCE_TYPE . "]",
                Console::BOLD, Console::BG_CYAN);
            echo PHP_EOL;
            
            $feed = null;
            try {
                $feed = \Feed::loadRss($urbanSource->url);
            } catch (\FeedException $e) {
                $this->stderr($e->getMessage() . PHP_EOL, Console::BOLD, Console::FG_RED);
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
    
                    $this->stdout("Новый пост: id='{$item->link}' date='{$post->post_date}' "
                        . "\"{$post->post_title}...\"" . PHP_EOL);
                } else {
                    echo "Item:\n";
                    $this->stderr(print_r($item, true), Console::BOLD, Console::FG_RED);
                    foreach ($post->getErrorSummary(true) as $message) {
                        $this->stderr($message . PHP_EOL, Console::BOLD, Console::FG_RED);
                    }
                }
            }
    
            $pause = rand(1, 10);
            $this->stdout("Пауза $pause сек.\n", Console::FG_YELLOW);
            sleep($pause);
        }
    
        $this->stdout("Завершено.\n", Console::FG_YELLOW);
        
        return ExitCode::OK;
    }
}
