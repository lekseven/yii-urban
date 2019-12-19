<?php

namespace console\controllers;

use app\models\UrbanSource;
use console\models\Post;
use console\models\UrbanSourceType;
use VK\Client\VKApiClient;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

class VkController extends Controller
{
    const SOURCE_TYPE = 'vk';
    
    const MAX_COUNT = 5;
    
    private $accessToken;
    
    public function __construct($id, $module, $config = [])
    {
        $this->accessToken = \Yii::$app->params[self::SOURCE_TYPE]['accessToken'] ?? null;
        if (!$this->accessToken) {
            echo 'Параметр accessToken не установлен.';
            return;
        }
        
        parent::__construct($id, $module, $config);
    }
    
    public final function actionIndex(): int
    {
        $vk = new VKApiClient();
        
        $sourceType = UrbanSourceType::findOne(['name' => self::SOURCE_TYPE]);
        /** @var UrbanSource[] $urbanSources */
        $urbanSources = UrbanSource::find()->where(['urban_source_type_id' => $sourceType->id])->all();
        foreach ($urbanSources as $urbanSource) {
            $this->stdout("Источник: {$urbanSource->short_name} [{$sourceType->name}]",
                Console::BOLD, Console::BG_CYAN);
            echo PHP_EOL;
            
            $response = null;
            try {
                $response = $vk->wall()->get($this->accessToken, [
                    'domain' => $urbanSource->short_name,
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

            foreach ($response['items'] as $index => $item) {
                if (!empty($item['is_pinned']) || !empty($item['marked_as_ads'])) {
                    continue;
                }
    
                if ($item['date'] == $urbanSource->latest_record || ($index + 1) > self::MAX_COUNT) {
                    break;
                }
    
                $post = new Post();
                $post->post_content = $item['text'];
                $post->post_date = date(\Yii::$app->formatter->datetimeFormat, $item['date']);
    
                if (isset($item['copy_history'])) {
                    $copyHistory = reset($item['copy_history']);
                    
                    $post->post_content = $copyHistory['text'];
                    $post->post_date = date(\Yii::$app->formatter->datetimeFormat, $item['date']);
                }
    
                $post->post_modified = date(\Yii::$app->formatter->datetimeFormat, time());
                if ($post->save()) {
                    $this->stdout("Новый пост: id='{$item['id']}' date='{$post->post_date}' "
                        . "\"" . mb_substr($post->post_content, 0, 50) . "...\"" . PHP_EOL);
        
                    // latest_record stores timestamp (as a string) in case of VK source
                    $latestRecordTimestamp = (int) $urbanSource->latest_record;
                    if ($latestRecordTimestamp < $item['date']) {
                        $urbanSource->latest_record = (string) $item['date'];
                    }
                    $urbanSource->save();
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
        
        return ExitCode::OK;
    }
}
