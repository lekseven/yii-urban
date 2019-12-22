<?php

namespace console\controllers;

use app\models\UrbanSource;
use console\models\UrbanSourceType;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

class SourceController extends Controller
{
    public final function actionIndex(?string $sourceTypeName = null): int
    {
        if ($sourceTypeName) {
            $sourceType = UrbanSourceType::findOne(['name' => $sourceTypeName]);
        }
        
        // TODO: сделать возврат частями
        /** @var UrbanSource[] $sources */
        $sources = UrbanSource::find()->andFilterWhere(['urban_source_type_id' => $sourceType->id ?? null])->all();
        if (!$sources) {
            $this->stdout("Не найдено.\n");
            
            return ExitCode::OK;
        }
        
        foreach ($sources as $source) {
            $this->stdout(print_r($source->attributes, true));
        }
        
        return ExitCode::OK;
    }
    
    public final function actionAdd(string $url, string $sourceTypeName): int
    {
        $sourceType = UrbanSourceType::findOne(['name' => $sourceTypeName]);
        if (!$sourceType) {
            $this->stderr("Неизвестный тип источника: $sourceTypeName\n",
                Console::FG_RED, Console::BOLD);
            
            return ExitCode::IOERR;
        }
        
        $source = new UrbanSource();
        $source->url = $url;
        $source->urban_source_type_id = $sourceType->id;
        if (!$source->save()) {
            $this->stderr("Возникла ошибка при добавлении источника.\n", Console::BOLD, Console::FG_RED);
            foreach ($source->getErrorSummary(true) as $message) {
                $this->stderr($message . PHP_EOL, Console::BOLD, Console::FG_RED);
            }
            
            return ExitCode::DATAERR;
        }
        
        $this->stdout("Источник успешно добавлен.\n", Console::FG_GREEN, Console::BOLD);
        
        return ExitCode::OK;
    }
    
    public final function actionRemove(int $id): int
    {
        return ExitCode::OK;
    }
    
    public final function actionReset(?int $id): int
    {
        return ExitCode::OK;
    }
    
    public final function actionResetAll(): void
    {
        UrbanSource::updateAll(['latest_record' => null]);
    }
    
    public final function actionEnable(int $id): int
    {
        return ExitCode::OK;
    }
    
    public final function actionDisable(int $id): int
    {
        return ExitCode::OK;
    }
}
