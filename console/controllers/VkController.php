<?php

namespace console\controllers;

use app\models\UrbanSource;
use console\models\UrbanSourceType;
use VK\Client\VKApiClient;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

class VkController extends Controller
{
    const SOURCE_TYPE = 'vk';
    
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
        /** @var UrbanSource[] $vkGroups */
        $vkGroups = UrbanSource::find()->where(['urban_source_type_id' => $sourceType->id])->all();
        foreach ($vkGroups as $vkGroup) {
            $response = $vk->wall()->get($this->accessToken, [
                'domain' => $vkGroup->short_name,
                'filter' => 'owner',
                'extended' => 1,
                'count' => 5,
            ]);
    
            if (empty($response['items'])) {
                $this->stderr("Пустой ответ от сервера.\n", Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }
            
            foreach ($response['items'] as $item) {
                // TODO: сохранение поста как черновика
            }
        }
        
        return ExitCode::OK;
    }
}
