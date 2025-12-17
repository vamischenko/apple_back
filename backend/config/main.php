<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-backend',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'backend\controllers',
    'bootstrap' => ['log'],
    'modules' => [],
    'components' => [
        'appleService' => [
            'class' => \common\services\AppleService::class,
        ],
        'request' => [
            'csrfParam' => '_csrf-backend',
        ],
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-backend', 'httpOnly' => true],
        ],
        'session' => [
            // this is the name of the session cookie used for login on the backend
            'name' => 'advanced-backend',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => false,
            'showScriptName' => false,
            'rules' => [
                // API Documentation - Swagger
                'GET api/swagger' => 'api/swagger/ui',
                'GET api/swagger/ui' => 'api/swagger/ui',
                'GET api/swagger/json' => 'api/swagger/json',

                // API Rules - Authentication
                'POST api/auth/login' => 'api/auth/login',
                'POST api/auth/logout' => 'api/auth/logout',

                // API Rules - Apples
                'POST api/apples/generate' => 'api/apple/generate',
                'POST api/apples/<id:\d+>/fall' => 'api/apple/fall',
                'POST api/apples/<id:\d+>/eat' => 'api/apple/eat',
                'GET api/apples/metrics' => 'api/apple/metrics',
                [
                    'class' => \yii\rest\UrlRule::class,
                    'controller' => ['api/apple'],
                    'pluralize' => false,
                    'extraPatterns' => [
                        'GET metrics' => 'metrics',
                        'POST generate' => 'generate',
                        'POST {id}/fall' => 'fall',
                        'POST {id}/eat' => 'eat',
                    ],
                ],
            ],
        ],
    ],
    'params' => $params,
];
