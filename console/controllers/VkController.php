<?php

namespace console\controllers;

use app\models\TermRelationship;
use app\models\UrbanSource;
use console\models\Post;
use console\models\Term;
use console\models\TermTaxonomy;
use console\models\UrbanSourceType;
use VK\Client\VKApiClient;
use yii\base\InvalidArgumentException;
use yii\base\Module;
use yii\console\Controller;
use yii\console\Exception;
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
    
    const MAX_COUNT = 5;
    
    const WALL_POST_URL = 'https://vk.com/wall';
    
    const TITLE_LENGTH = 54;
    
    const WP_TAG_TAXONOMY = 'post_tag';
    
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
     */
    public final function actionIndex(): int
    {
        $vk = new VKApiClient();
        
        $vkTag = $this->getPostTag(self::SOURCE_TYPE);
        
        $sourceType = UrbanSourceType::findOne(['name' => self::SOURCE_TYPE]);
        /** @var UrbanSource[] $urbanSources */
        $urbanSources = UrbanSource::find()->where(['urban_source_type_id' => $sourceType->id])->all();
        foreach ($urbanSources as $urbanSource) {
            $domain = str_replace('https://vk.com/', '', $urbanSource->url);
            
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

            foreach ($response['items'] as $index => $item) {
                if (!empty($item['is_pinned']) || !empty($item['marked_as_ads'])) {
                    continue;
                }
    
                if ($item['date'] == $urbanSource->latest_record || ($index + 1) > self::MAX_COUNT) {
                    break;
                }
    
                $post = new Post();
                
                // TODO: нужна более лучшая и полная логика извлечения контента
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
                
                if (!$post->post_content) {
                    continue;
                }
                
                $post->post_content .= "\n" . self::WALL_POST_URL . "{$item['owner_id']}_{$item['id']}";
                $post->post_modified = date(\Yii::$app->formatter->datetimeFormat, time());
                if ($post->save()) {
                    $this->addTag($post, $vkTag);
                    $this->addTag($post, $domain);
                    
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
    
        $this->stdout("Завершено.\n", Console::BOLD, Console::FG_YELLOW);
        
        return ExitCode::OK;
    }
    
    /**
     * @param string $tagName
     * @return TermTaxonomy
     * @throws Exception
     */
    private function getPostTag(string $tagName): TermTaxonomy
    {
        $term = Term::findOne(['name' => $tagName]);
        if (!$term) {
            $term = new Term();
            $term->name = $tagName;
            if (!$term->save()) {
                throw new Exception();
            }
            
            $taxonomy = new TermTaxonomy();
            $taxonomy->term_id = $term->term_id;
            $taxonomy->taxonomy = self::WP_TAG_TAXONOMY;
            if (!$taxonomy->save()) {
                throw new Exception();
            }
            
            return $taxonomy;
        }
        
        return TermTaxonomy::findOne(['term_id' => $term->term_id, 'taxonomy' => self::WP_TAG_TAXONOMY]);
    }
    
    /**
     * @param Post $post
     * @param TermTaxonomy|string $tag
     * @throws Exception
     */
    private function addTag(Post $post, $tag): void
    {
        if ($tag instanceof TermTaxonomy) {
            $termTaxonomy = $tag;
        } elseif (is_string($tag)) {
            $termTaxonomy = $this->getPostTag($tag);
        } else {
            throw new InvalidArgumentException();
        }
        
        $termRelationship = new TermRelationship();
        $termRelationship->object_id = $post->ID;
        $termRelationship->term_taxonomy_id = $termTaxonomy->term_taxonomy_id;
        if (!$termRelationship->save()) {
            throw new Exception();
        }
    }
}
