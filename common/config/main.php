<?php
return [
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'authManager' => [
            'class' => \yii\rbac\DbManager::class,
            'cache' => 'cache',
        ],
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
    ],
    'container' => [
        'singletons' => [
            // Регистрация AppleRepository как singleton
            'common\repositories\AppleRepository' => 'common\repositories\AppleRepository',

            // Регистрация AppleService с автоматическим внедрением зависимостей
            'common\services\AppleService' => [
                'class' => 'common\services\AppleService',
                // Repository будет автоматически внедрен через конструктор
            ],
        ],
    ],
];
