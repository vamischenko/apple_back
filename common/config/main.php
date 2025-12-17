<?php
return [
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'bootstrap' => ['appleEventHandler'],
    'components' => [
        'appleEventHandler' => [
            'class' => \common\handlers\AppleEventHandler::class,
        ],
        'authManager' => [
            'class' => \yii\rbac\DbManager::class,
            'cache' => 'cache',
        ],
        'cache' => [
            // Использует Redis если доступен (в Docker), иначе FileCache
            'class' => getenv('REDIS_HOST') ? \yii\redis\Cache::class : \yii\caching\FileCache::class,
        ],
        'redis' => [
            'class' => \yii\redis\Connection::class,
            'hostname' => getenv('REDIS_HOST') ?: 'localhost',
            'port' => 6379,
            'database' => 0,
        ],
    ],
    'container' => [
        'singletons' => [
            // Регистрация AppleRepository как singleton
            \common\repositories\AppleRepository::class => \common\repositories\AppleRepository::class,

            // Регистрация AppleService с автоматическим внедрением зависимостей
            \common\services\AppleService::class => [
                'class' => \common\services\AppleService::class,
                // Repository будет автоматически внедрен через конструктор
            ],

            // Регистрация AppleMetricsService
            \common\services\AppleMetricsService::class => [
                'class' => \common\services\AppleMetricsService::class,
                // Repository будет автоматически внедрен через конструктор
            ],
        ],
    ],
];
