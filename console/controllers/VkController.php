<?php

namespace console\controllers;

use VK\Client\VKApiClient;
use yii\console\Controller;

class VkController extends Controller
{
    private $accessToken;
    
    public function __construct($id, $module, $config = [])
    {
        $this->accessToken = \Yii::$app->params['vk']['accessToken'] ?? null;
        if (!$this->accessToken) {
            echo 'Параметр accessToken не установлен.';
            return;
        }
        
        parent::__construct($id, $module, $config);
    }
    
    public function actionIndex()
    {
        $vk = new VKApiClient();
        
        $response = $vk->wall()->get($this->accessToken, [
            'domain' => 'urbnsk',
            'filter' => 'owner',
            'extended' => 1,
        ]);
        
        //var_dump($response);
    }
}
