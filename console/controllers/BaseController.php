<?php


namespace console\controllers;


use console\models\UrbanSource;
use console\models\UrbanSourceType;
use yii\base\InvalidArgumentException;
use yii\console\Controller;
use yii\db\ActiveRecord;
use yii\helpers\Console;
use yii\web\NotFoundHttpException;

abstract class BaseController extends Controller
{
    public string $logCategory = 'application';
    
    public bool $quiet = false;
    
    public ?array $sourceId = [];
    
    public ?array $sourceUrl = [];
    
    public function options($actionID)
    {
        return [
            'quiet',
            'sourceId',
            'sourceUrl',
        ];
    }
    
    public function optionAliases()
    {
        return [
            'q' => 'quiet',
            'i' => 'sourceId',
            'u' => 'sourceUrl',
        ];
    }
    
    public function logError(string $message): void
    {
        if (!$this->quiet) {
            $this->stderr($message, Console::BOLD, Console::FG_RED);
            $this->stderr(PHP_EOL);
        }
        
        \Yii::error($message, $this->logCategory);
    }
    
    public function logInfo(string $message, ...$format): void
    {
        if (!$this->quiet) {
            $this->stdout($message, ...$format);
            $this->stdout(PHP_EOL);
        }
        
        \Yii::info($message, $this->logCategory);
    }
    
    /**
     * @param string $class
     * @return array
     * @throws NotFoundHttpException
     * @throws \ReflectionException
     */
    public function fetchSources(string $class): array
    {
        $reflection = new \ReflectionClass($class);
        if (!$reflection->isSubclassOf(UrbanSource::class)) {
            throw new InvalidArgumentException();
        }
        
        $urbanSources = [];
    
        /** @var $class ActiveRecord */
        if ($this->sourceId) {
            $urbanSources = $class::findAll(['id' => $this->sourceId]);
        } elseif ($this->sourceUrl) {
            $urbanSources = $class::findAll(['url' => $this->sourceUrl]);
        } elseif ($class) {
            $sourceType = UrbanSourceType::findOne(['name' => $class::SOURCE_TYPE]);
            if (!$sourceType) {
                throw new NotFoundHttpException();
            }
            
            $urbanSources = $class::findAll([
                'urban_source_type_id' => $sourceType->id,
                'active' => 1,
            ]);
        }
        
        return $urbanSources;
    }
}
