<?php

namespace console\controllers;

use console\models\UrbanSource;
use console\models\UrbanSourceType;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;


/**
 * Управление источниками
 *
 * Class SourceController
 * @package console\controllers
 */
class SourceController extends Controller
{
    /**
     * Показать все источники или только источники указанного типа
     *
     * @param string|null $sourceTypeName
     * @return int
     */
    public final function actionIndex(?string $sourceTypeName = null): int
    {
        if ($sourceTypeName) {
            $sourceType = UrbanSourceType::findOne(['name' => $sourceTypeName]);
    
            $this->stdout("Источники: {$sourceType->name}", Console::BOLD, Console::BG_CYAN);
            echo PHP_EOL;
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
    
    /**
     * Добавить источник
     *
     * @param string $url
     * @param string $sourceTypeName
     * @return int
     */
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
    
    /**
     * Удалить источник
     *
     * @param int $id
     * @return int
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public final function actionRemove(int $id): int
    {
        $source = UrbanSource::findOne($id);
        if (!$source) {
            $this->stdout("Источник id=$id не найден.\n", Console::BOLD, Console::FG_RED);
    
            return ExitCode::UNSPECIFIED_ERROR;
        }
    
        // TODO: Add confirmation before removing
        if (!$source->delete()) {
            $this->stderr("Возникла ошибка при удалении источника.\n", Console::BOLD, Console::FG_RED);
            foreach ($source->getErrorSummary(true) as $message) {
                $this->stderr($message . PHP_EOL, Console::BOLD, Console::FG_RED);
            }
        
            return ExitCode::UNSPECIFIED_ERROR;
        }
    
        $this->stdout("Источник id=$id удален.\n", Console::FG_GREEN, Console::BOLD);
    
        return ExitCode::OK;
    }
    
    /**
     * Сбросить сохраненные данные источника
     *
     * @param int $id
     */
    public final function actionReset(int $id): void
    {
        UrbanSource::updateAll(['latest_record' => null], ['id' => $id]);
    }
    
    /**
     * Сбросить сохраненные данные всех источников
     */
    public final function actionResetAll(): void
    {
        UrbanSource::updateAll(['latest_record' => null]);
    }
    
    /**
     * Включить источник
     *
     * @param int $id
     * @return int
     */
    public final function actionEnable(int $id): int
    {
        $result = UrbanSource::updateAll(['active' => true], ['id' => $id]);
        if (!$result) {
            $this->stdout("Источник не найден: id=$id\n", Console::BOLD, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
    
        $this->stdout("Источник включен: id=$id\n");
        return ExitCode::OK;
    }
    
    /**
     * Выключить источник
     *
     * @param int $id
     * @return int
     */
    public final function actionDisable(int $id): int
    {
        $result = UrbanSource::updateAll(['active' => false], ['id' => $id]);
        if (!$result) {
            $this->stdout("Источник не найден: id=$id\n", Console::BOLD, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
    
        $this->stdout("Источник выключен: id=$id\n");
        return ExitCode::OK;
    }
}
