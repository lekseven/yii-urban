<?php

namespace console\controllers;

use console\models\UrbanSource;
use console\models\UrbanSourceType;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Exception;

class SeedController extends Controller
{
    public function actionIndex()
    {
        $sources = [
            'vk',
            'facebook',
            'twitter',
            'youtube',
            'medium',
            'telegram',
            'zen',
            'rss',
        ];
        
        foreach ($sources as $sourceName) {
            if (!UrbanSourceType::find()->where(['name' => $sourceName])->exists()) {
                $sourceType = new UrbanSourceType();
                $sourceType->name = $sourceName;
                $sourceType->save();
            }
        }
        
        return ExitCode::OK;
    }
    
    public function actionSources()
    {
        $sourceFiles = [
            'vk' => 'vk_groups.txt',
            'rss' => 'rss.txt',
        ];
    
        foreach ($sourceFiles as $sourceTypeName => $sourceFile) {
            $fileContent = file_get_contents(__DIR__ . "/../../$sourceFile");
            if (!$fileContent) {
                echo "File $sourceFile is empty. Skipping.\n";
                continue;
            }
    
            $sourceType = UrbanSourceType::findOne(['name' => $sourceTypeName]);
            if (!$sourceType) {
                echo "No source type $sourceTypeName found. Skipping.\n";
                continue;
            }
    
            $urls = explode("\n", $fileContent);
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
