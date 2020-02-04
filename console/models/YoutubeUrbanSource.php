<?php

namespace console\models;

use Google_Service_YouTube_VideoSnippet;
use yii\base\Exception;

class YoutubeUrbanSource extends UrbanSource
{
    const SOURCE_TYPE = 'youtube';
    
    const BASE_CHANNEL_URL = 'https://www.youtube.com/channel/';
    
    const BASE_WATCH_URL = 'https://www.youtube.com/watch?v=';
    
    private string $appName;
    
    private string $appKey;
    
    private \Google_Service_YouTube $service;
    
    /**
     * YoutubeUrbanSource constructor.
     * @param array $config
     * @throws Exception
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
        
        $this->appName = \Yii::$app->params[YoutubeUrbanSource::SOURCE_TYPE]['appName'] ?? null;
        if (!$this->appName) {
            throw new Exception('Параметр appName не установлен.');
        }
    
        $this->appKey = \Yii::$app->params[YoutubeUrbanSource::SOURCE_TYPE]['appKey'] ?? null;
        if (!$this->appKey) {
            throw new Exception('Параметр appKey не установлен.');
        }
    
        $client = new \Google_Client();
        $client->setApplicationName($this->appName);
        $client->setDeveloperKey($this->appKey);
        
        $this->service = new \Google_Service_YouTube($client);
    }
    
    public function getChannelId(): string
    {
        return str_replace(self::BASE_CHANNEL_URL, '', $this->url);
    }
    
    public function getUpdates(): array
    {
        $response = $this->service->activities->listActivities('contentDetails', [
            'channelId' => $this->getChannelId(),
        ]);
        
        return $response['items'] ?? [];
    }
    
    public function getVideoInfo(string $videoId): ?Google_Service_YouTube_VideoSnippet
    {
        $response = $this->service->videos->listVideos('snippet', [
            'id' => $videoId,
        ]);
        
        return $response['items'][0]['snippet'] ?? null;
    }
    
    public static function getVideoLink(string $videoId): string
    {
        return self::BASE_WATCH_URL . $videoId;
    }
}
