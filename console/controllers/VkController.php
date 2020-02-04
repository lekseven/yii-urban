<?php

namespace console\controllers;

use console\models\Post;
use console\models\TermTaxonomy;
use console\models\VkUrbanSource;
use VK\Client\VKApiClient;
use yii\base\Module;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Управление парсером VK
 *
 * Class VkController
 * @package console\controllers
 */
class VkController extends BaseController
{
    public string $logCategory = VkUrbanSource::SOURCE_TYPE;
    
    /**
     * Получить новые посты
     *
     * @return int
     * @throws \yii\base\Exception
     * @throws \yii\web\NotFoundHttpException
     * @throws \ReflectionException
     */
    public final function actionIndex(): int
    {
        $vkTag = TermTaxonomy::findOrCreate(VkUrbanSource::SOURCE_TYPE);
        $minDate = VkUrbanSource::getMinDate();
        
        /** @var VkUrbanSource[] $urbanSources */
        $urbanSources = $this->fetchSources(VkUrbanSource::class);
        foreach ($urbanSources as $urbanSource) {
            $domain = $urbanSource->getDomain();
            
            $this->logInfo("Источник: {$domain} [" . VkUrbanSource::SOURCE_TYPE . "]",
                Console::BOLD, Console::BG_CYAN);
            
            try {
                $items = $urbanSource->getUpdates();
            } catch (\Exception $exception) {
                $this->logError(print_r($urbanSource->attributes, true));
                $this->logError($exception->getMessage());
                
                continue;
            }
            
            $latestRecord = $urbanSource->latest_record;
            foreach ($items as $item) {
                if (!empty($item['is_pinned']) || !empty($item['marked_as_ads'])) {
                    continue;
                }
    
                if ($item['date'] <= $latestRecord || $item['date'] < $minDate) {
                    break;
                }
    
                $post = new Post();
                $this->fillPostData($post, $item);
                
                if (!$post->post_content) {
                    continue;
                }
                
                $postLink = VkUrbanSource::getPostLink($item['owner_id'], $item['id']);
                $post->addLink($postLink);
                
                if ($post->save()) {
                    $post->addTag($vkTag);
                    $post->addTag($domain);
                    
                    $urbanSource->updateLatestRecord($item['date']);
                    
                    $this->logInfo("Новый пост: $postLink {$post->post_date} \"{$post->post_title}...\"");
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
    
    private function fillPostData(Post $post, array $item): void
    {
        // TODO: нужна более лучшая и полная логика извлечения контента (ТЗ)
        if ($item['text']) {
            $post->setTitle($item['text']);
            $post->setContent($item['text']);
            $post->setDate($item['date']);
        } elseif (!empty($item['copy_history'])) {
            $copyHistory = reset($item['copy_history']);
            $post->setTitle($copyHistory['text']);
            $post->setContent($copyHistory['text']);
            $post->setDate($item['date']);
        }/* elseif (!empty($item['attachments'][0]['link'])) {
            $itemLink = $item['attachments'][0]['link'];
        }*/
    }
}
