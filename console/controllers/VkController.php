<?php

namespace console\controllers;

use console\models\Post;
use console\models\TermTaxonomy;
use console\models\UrbanSource;
use console\models\UrbanSourceType;
use console\models\VkUrbanSource;
use VK\Client\VKApiClient;
use yii\base\Module;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Управление парсером VK
 *
 * Class VkController
 * @package console\controllers
 */
class VkController extends Controller
{
    const SOURCE_TYPE = 'vk';
    
    const URL_VK_WALL = 'https://vk.com/wall';
    
    const TITLE_LENGTH = 54;
    
    // Кол-во дней, в рамках которых выполняется поиск новых записей
    const MIN_DATE = 5;
    
    private $accessToken;
    
    /**
     * VkController constructor.
     * @param string $id
     * @param Module $module
     * @param array $config
     */
    public function __construct(string $id, Module $module, array $config = [])
    {
        $this->accessToken = \Yii::$app->params[VkUrbanSource::SOURCE_TYPE]['accessToken'] ?? null;
        if (!$this->accessToken) {
            echo 'Параметр accessToken не установлен.';
            return;
        }
        
        parent::__construct($id, $module, $config);
    }
    
    /**
     * Получить новые посты
     *
     * @return int
     * @throws \yii\base\Exception
     */
    public final function actionIndex(): int
    {
        $vk = new VKApiClient();
        
        $vkTag = TermTaxonomy::findOrCreate(VkUrbanSource::SOURCE_TYPE);
        
        $period = \Yii::$app->params['period'] ?? VkUrbanSource::MIN_DATE;
        $minDate = strtotime("-$period days");
        
        $sourceType = UrbanSourceType::findOne(['name' => VkUrbanSource::SOURCE_TYPE]);
        /** @var UrbanSource[] $urbanSources */
        $urbanSources = UrbanSource::findAll([
            'urban_source_type_id' => $sourceType->id,
            'active' => 1,
        ]);
        foreach ($urbanSources as $urbanSource) {
            $domain = preg_replace("/https?:\/\/vk\.com\//i", '', $urbanSource->url);
            
            $this->stdout("Источник: {$domain} [{$sourceType->name}]",
                Console::BOLD, Console::BG_CYAN);
            echo PHP_EOL;
            
            $response = null;
            try {
                $response = $vk->wall()->get($this->accessToken, [
                    'domain' => $domain,
                    'filter' => 'owner',
                ]);
            } catch (\Exception $exception) {
                $this->stderr(print_r($urbanSource->attributes, true), Console::BOLD, Console::FG_RED);
                $this->stderr($exception->getMessage() . PHP_EOL, Console::BOLD, Console::FG_RED);
                
                continue;
            }
    
            if (!isset($response['items'])) {
                $this->stderr(print_r($response, true), Console::FG_RED);
                
                continue;
            }
            
            $latestRecord = $urbanSource->latest_record;
            foreach ($response['items'] as $item) {
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
                
                $post->addLink(self::URL_VK_WALL . "{$item['owner_id']}_{$item['id']}");
                
                if ($post->save()) {
                    $post->addTag($vkTag);
                    $post->addTag($domain);
                    
                    $urbanSource->updateLatestRecord($item['date']);
                    
                    $this->stdout("Новый пост: id='{$item['id']}' date='{$post->post_date}' "
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
            $this->stdout("Пауза $pause сек.\n", Console::BOLD, Console::FG_YELLOW);
            sleep($pause);
        }
    
        $this->stdout("Завершено.\n", Console::BOLD, Console::FG_YELLOW);
        
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
