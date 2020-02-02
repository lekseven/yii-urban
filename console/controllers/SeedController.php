<?php

namespace console\controllers;

use console\models\UrbanSource;
use console\models\UrbanSourceType;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Exception;

class SeedController extends Controller
{
    const SOURCES = [
        'vk',
        'facebook',
        'twitter',
        'youtube',
        'medium',
        'telegram',
        'zen',
        'rss',
    ];
    
    /**
     * Seeds a predefined list of sources
     *
     * @return int
     */
    public function actionIndex()
    {
        foreach (self::SOURCES as $sourceName) {
            if (!UrbanSourceType::find()->where(['name' => $sourceName])->exists()) {
                $sourceType = new UrbanSourceType();
                $sourceType->name = $sourceName;
                $sourceType->save();
            }
        }
        
        return ExitCode::OK;
    }
    
    /**
     * Seeds URLs from txt file to sources
     *
     * @return int
     */
    public function actionSources()
    {
        foreach (self::SOURCES as $sourceName) {
            $seedFile = __DIR__ . "/../../data/$sourceName.txt";
            if (!file_exists($seedFile)) {
                echo "File $seedFile does not exists. Skipping.\n";
                continue;
            }
    
            $seedFileContent = file_get_contents($seedFile);
            if (!$seedFileContent) {
                echo "File $seedFile is empty. Skipping.\n";
                continue;
            }
    
            $sourceType = UrbanSourceType::findOne(['name' => $sourceName]);
            if (!$sourceType) {
                echo "No source type $sourceName found. Skipping.\n";
                continue;
            }
    
            $urls = explode("\n", $seedFileContent);
            foreach ($urls as $url) {
                if (!UrbanSource::find()->where(['url' => $url])->exists()) {
                    $source = new UrbanSource();
                    $source->url = $url;
                    $source->urban_source_type_id = $sourceType->id;
                    try {
                        if ($source->save()) {
                            echo "Source $url was added\n";
                        }
                    } catch (Exception $exception) {
                        echo $exception->getMessage() . "\n";
                    }
                }
            }
        }
        
        return ExitCode::OK;
    }
}
