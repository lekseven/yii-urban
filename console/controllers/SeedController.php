<?php

namespace console\controllers;

use app\models\UrbanSource;
use console\models\UrbanSourceType;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\IntegrityException;

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
        ];
        
        UrbanSourceType::deleteAll();
        
        foreach ($sources as $sourceName) {
            $sourceType = new UrbanSourceType();
            $sourceType->name = $sourceName;
            $sourceType->save();
        }
        
        return ExitCode::OK;
    }
    
    public function actionSources()
    {
        $fileContent = file_get_contents(__DIR__ . '/../../vk_groups.txt');
        if (!$fileContent) {
            echo 'File is empty';
            return ExitCode::UNSPECIFIED_ERROR;
        }
    
        $sourceType = UrbanSourceType::findOne(['name' => 'vk']);
        if (!$sourceType) {
            echo 'No source type found';
            return ExitCode::UNSPECIFIED_ERROR;
        }
    
        $vkGroupUrls = explode("\n", $fileContent);
        foreach ($vkGroupUrls as $url) {
            $source = new UrbanSource();
            $source->url = $url;
            $source->urban_source_type_id = $sourceType->id;
            try {
                if ($source->save()) {
                    echo "Source $url was added\n";
                }
            } catch (IntegrityException $exception) {
                echo $exception->getMessage() . "\n";
            }
        }
        
        return ExitCode::OK;
    }
}
