<?php


namespace console\controllers;


use yii\console\Controller;
use yii\helpers\Console;

abstract class BaseController extends Controller
{
    public string $logCategory = 'application';
    
    public function logError(string $message): void
    {
        $this->stderr($message, Console::BOLD, Console::FG_RED);
        $this->stderr(PHP_EOL);
        
        \Yii::error($message, $this->logCategory);
    }
    
    public function logInfo(string $message, ...$format): void
    {
        $this->stdout($message, ...$format);
        $this->stdout(PHP_EOL);
        
        \Yii::info($message, $this->logCategory);
    }
}
