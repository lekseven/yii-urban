<?php

namespace console\models;

use VK\Client\VKApiClient;
use yii\base\Exception;

class VkUrbanSource extends UrbanSource
{
    const SOURCE_TYPE = 'vk';
    
    const URL_VK_WALL = 'https://vk.com/wall';
    
    private string $accessToken;
    
    /**
     * VkUrbanSource constructor.
     * @param array $config
     * @throws Exception
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
    
        $this->accessToken = \Yii::$app->params[VkUrbanSource::SOURCE_TYPE]['accessToken'] ?? null;
        if (!$this->accessToken) {
            throw new Exception('Параметр accessToken не установлен.');
        }
    }
    
    /**
     * @return string
     */
    public function getDomain(): string
    {
        return preg_replace("/https?:\/\/vk\.com\//i", '', $this->url);
    }
    
    /**
     * @return array
     * @throws \VK\Exceptions\Api\VKApiBlockedException
     * @throws \VK\Exceptions\VKApiException
     * @throws \VK\Exceptions\VKClientException
     */
    public function getUpdates(): array
    {
        $vk = new VKApiClient();
    
        $response = $vk->wall()->get($this->accessToken, [
            'domain' => $this->getDomain(),
            'filter' => 'owner',
        ]);
        
        \Yii::debug($response);
        
        return $response['items'] ?? [];
    }
    
    /**
     * @param string $ownerId
     * @param string $itemId
     * @return string
     */
    public static function getPostLink(string $ownerId, string $itemId): string
    {
        return self::URL_VK_WALL . "{$ownerId}_{$itemId}";
    }
}
