<?php

namespace console\models;

use GuzzleHttp\Client;
use yii\base\Exception;

class TwitterUrbanSource extends UrbanSource
{
    const SOURCE_TYPE = 'twitter';
    
    const HOME_URL = 'https://twitter.com';
    
    const API_URL = 'https://api.twitter.com';
    
    private Client $client;
    
    private array $headers = [];
    
    /**
     * TwitterUrbanSource constructor.
     * @param array $config
     * @throws Exception
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
    
        $this->client = new Client();
        
        $this->initHeaders();
    }
    
    /**
     * @throws Exception
     */
    private function initHeaders()
    {
        $authToken = \Yii::$app->params['twitter']['authToken'] ?? null;
        if (!$authToken) {
            throw new Exception('Twitter authToken is not defined.');
        }
        $this->headers['authorization'] = "Bearer $authToken";
    
        $response = $this->client->post(self::API_URL . "/1.1/guest/activate.json", [
            'headers' => $this->headers,
        ]);
        $guestToken = json_decode($response->getBody()->getContents(), true)['guest_token'] ?? null;
        if (!$guestToken) {
            if (!$authToken) {
                throw new Exception('Twitter guest_token is invalid.');
            }
        }
        $this->headers['x-guest-token'] = $guestToken;
    }
    
    /**
     * @return array
     * @throws Exception
     */
    public function getUpdates(): array
    {
        $userId = $this->requestUserId($this->getUserScreenName());
        if (!$userId) {
            throw new Exception('User ID is not valid');
        }
        
        return $this->requestTweets($userId);
    }
    
    /**
     * @param string $screenName
     * @return int
     */
    private function requestUserId(string $screenName): int
    {
        $response = $this->client->get(self::API_URL . "/1.1/users/show.json?screen_name={$screenName}", [
            'headers' => $this->headers,
        ]);
        
        return json_decode($response->getBody()->getContents(), true)['id'] ?? null;
    }
    
    /**
     * @param int $userId
     * @return array
     * @throws Exception
     */
    private function requestTweets(int $userId): array
    {
        $response = $this->client->get(self::API_URL . "/2/timeline/profile/$userId.json", [
            'headers' => $this->headers,
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
