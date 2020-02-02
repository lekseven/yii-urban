<?php

namespace console\models;

use GuzzleHttp\Client;
use yii\base\Exception;

class TwitterUrbanSource extends UrbanSource
{
    const SOURCE_TYPE = 'twitter';
    
    const HOME_URL = 'https://twitter.com';
    
    const API_URL = 'https://api.twitter.com';
    
    const HEADERS = [
        'authorization' => 'Bearer AAAAAAAAAAAAAAAAAAAAANRILgAAAAAAnNwIzUejRCOuH5E6I8xnZz4puTs%3D1Zv7ttfk8LF81IUq16cHjhLTvJu4FA33AGWWjCpTnA',
        'x-guest-token' => '1223954146920890368',
    ];
    
    private Client $client;
    
    public function __construct($config = [])
    {
        parent::__construct($config);
    
        $this->client = new Client();
    }
    
    /**
     * @return array
     * @throws Exception
     */
    public function getUpdates(): array
    {
        $userId = $this->getUserId($this->getUserScreenName());
        if (!$userId) {
            throw new Exception('User ID is not valid');
        }
        
        return $this->getTweets($userId);
    }
    
    /**
     * @param string $screenName
     * @return int
     */
    private function getUserId(string $screenName): int
    {
        $response = $this->client->get(self::API_URL . "/1.1/users/show.json?screen_name={$screenName}", [
            'headers' => self::HEADERS,
        ]);
        
        return json_decode($response->getBody()->getContents(), true)['id'] ?? null;
    }
    
    /**
     * @param int $userId
     * @return array
     * @throws Exception
     */
    private function getTweets(int $userId): array
    {
        $response = $this->client->get(self::API_URL . "/2/timeline/profile/$userId.json", [
            'headers' => self::HEADERS,
        ]);
        \Yii::debug($response);
        
        $profileInfo = json_decode($response->getBody()->getContents(), true);
        $unsortedTweets = $profileInfo['globalObjects']['tweets'] ?? null;
        $timeline = $profileInfo['timeline']['instructions'][0]['addEntries']['entries'] ?? null;
        \Yii::debug($unsortedTweets);
        \Yii::debug($timeline);
        
        if ($unsortedTweets === null || $timeline === null) {
            throw new Exception('Error while getting tweets. Check the debug log.');
        }
    
        $sortedTweets = [];
        foreach ($timeline as $entry) {
            $tweetId = $entry['content']['item']['content']['tweet']['id'] ?? null;
            if ($tweetId) {
                $tweet = $unsortedTweets[$tweetId] ?? null;
                if ($tweet) {
                    $sortedTweets[] = $tweet;
                }
            }
        }
        
        return $sortedTweets;
    }
    
    public function getUserScreenName(): string
    {
        return str_replace(self::HOME_URL . '/', '', $this->url);
    }
    
    public static function getTweetLink(string $tweetId): string
    {
        return static::HOME_URL . '/status/' . $tweetId;
    }
}
