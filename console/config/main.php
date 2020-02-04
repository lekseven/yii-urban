<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'console\controllers',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'controllerMap' => [
        'fixture' => [
            'class' => 'yii\console\controllers\FixtureController',
            'namespace' => 'common\fixtures',
          ],
    ],
    'components' => [
        'log' => [
            'flushInterval' => 1,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'categories' => ['vk'],
                    'logFile' => '@console/runtime/logs/vk.log',
                    'exportInterval' => 1,
                    'logVars' => [],
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'categories' => ['rss'],
                    'logFile' => '@console/runtime/logs/rss.log',
                    'exportInterval' => 1,
                    'logVars' => [],
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'categories' => ['youtube'],
                    'logFile' => '@console/runtime/logs/youtube.log',
                    'exportInterval' => 1,
                    'logVars' => [],
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'categories' => ['twitter'],
                    'logFile' => '@console/runtime/logs/twitter.log',
                    'exportInterval' => 1,
                    'logVars' => [],
                ],
            ],
        ],
        'formatter' => [
            'class'    => 'yii\i18n\Formatter',
            'timeZone' => 'UTC',
            'dateFormat' => 'php:Y-m-d',
            'datetimeFormat' => 'php:Y-m-d H:i:s',
            'timeFormat' => 'php:H:i:s',
            'nullDisplay' => '',
        ],
    ],
    'params' => $params,
];
