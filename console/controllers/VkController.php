<?php

namespace console\controllers;

use console\models\Post;
use console\models\TermTaxonomy;
use console\models\UrbanSource;
use console\models\UrbanSourceType;
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
    const URL_VK_HOME = 'https://vk.com/';
    
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
        $this->accessToken = \Yii::$app->params[self::SOURCE_TYPE]['accessToken'] ?? null;
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
        
        $vkTag = TermTaxonomy::findOrCreate(self::SOURCE_TYPE);
        
        $period = \Yii::$app->params['period'] ?? self::MIN_DATE;
        $minDate = strtotime("-$period days");
        
        $sourceType = UrbanSourceType::findOne(['name' => self::SOURCE_TYPE]);
        /** @var UrbanSource[] $urbanSources */
        $urbanSources = UrbanSource::findAll([
            'urban_source_type_id' => $sourceType->id,
            'active' => 1,
        ]);
        foreach ($urbanSources as $urbanSource) {
            $domain = str_replace(self::URL_VK_HOME, '', $urbanSource->url);
            
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
                
                $post->post_content .= "\n\n" . self::URL_VK_WALL . "{$item['owner_id']}_{$item['id']}";
                $post->post_modified = date(\Yii::$app->formatter->datetimeFormat, time());
                
                if ($post->save()) {
                    $post->addTag($vkTag);
                    $post->addTag($domain);
                    
                    $this->updateLatestRecord($urbanSource, $item['date']);
                    
                    $this->stdout("Новый пост: id='{$item['id']}' date='{$post->post_date}' "
                        . "\"" . mb_substr($post->post_content, 0, 50) . "...\"" . PHP_EOL);
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
    
    /**
     * @param UrbanSource $urbanSource
     * @param int $date
     */
    private function updateLatestRecord(UrbanSource $urbanSource, int $date): void
    {
        // latest_record stores timestamp (as a string) in case of VK source
        $latestRecordTimestamp = (int) $urbanSource->latest_record;
        if ($latestRecordTimestamp < $date) {
            $urbanSource->latest_record = (string) $date;
        }
        $urbanSource->save();
    }
    
    private function fillPostData(Post $post, array $item): void
    {
        // TODO: нужна более лучшая и полная логика извлечения контента (ТЗ)
        if ($item['text']) {
            $post->post_title = mb_substr($item['text'], 0, self::TITLE_LENGTH);
            $post->post_content = $item['text'];
            $post->post_date = date(\Yii::$app->formatter->datetimeFormat, $item['date']);
        } elseif (!empty($item['copy_history'])) {
            $copyHistory = reset($item['copy_history']);
            $post->post_title = mb_substr($copyHistory['text'], 0, self::TITLE_LENGTH);
            $post->post_content = $copyHistory['text'];
            $post->post_date = date(\Yii::$app->formatter->datetimeFormat, $item['date']);
        }/* elseif (!empty($item['attachments'][0]['link'])) {
            $itemLink = $item['attachments'][0]['link'];
        }*/
    }
}
