<?php

namespace backend\controllers\api;

use Yii;
use yii\rest\Controller;
use yii\filters\ContentNegotiator;
use yii\web\Response;
use common\models\LoginForm;

/**
 * REST API контроллер для аутентификации
 *
 * Endpoints:
 * POST /api/auth/login  - получение JWT токена
 * POST /api/auth/logout - выход (инвалидация токена)
 *
 * ВАЖНО: Для работы требуется установка библиотеки firebase/php-jwt
 * и настройка согласно документации в docs/JWT_AUTHENTICATION_SETUP.md
 */
class AuthController extends Controller
{
    /**
     * Настройка behaviors
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // Настройка формата ответа
        $behaviors['contentNegotiator'] = [
            'class' => ContentNegotiator::class,
            'formats' => [
                'application/json' => Response::FORMAT_JSON,
            ],
        ];

        return $behaviors;
    }

    /**
     * POST /api/auth/login - получение токена
     *
     * Принимает JSON: {"username": "admin", "password": "admin123"}
     *
     * ПРИМЕЧАНИЕ: Текущая реализация использует стандартную сессионную аутентификацию.
     * Для JWT требуется установка пакета firebase/php-jwt и настройка модели User.
     * См. docs/JWT_AUTHENTICATION_SETUP.md для полной инструкции.
     */
    public function actionLogin()
    {
        $model = new LoginForm();

        if ($model->load(Yii::$app->request->post(), '') && $model->login()) {
            $user = $model->getUser();

            // Проверяем, есть ли метод generateJwtToken
            if (method_exists($user, 'generateJwtToken')) {
                $token = $user->generateJwtToken();

                return [
                    'success' => true,
                    'token' => $token,
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'email' => $user->email ?? null,
                    ],
                ];
            }

            // Fallback: возвращаем базовую информацию без JWT
            return [
                'success' => true,
                'message' => 'Вход выполнен успешно',
                'note' => 'JWT не настроен. См. docs/JWT_AUTHENTICATION_SETUP.md',
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                ],
            ];
        }

        Yii::$app->response->statusCode = 401;
        return [
            'success' => false,
            'errors' => $model->errors,
            'message' => 'Неверные учетные данные',
        ];
    }

    /**
     * POST /api/auth/logout - выход
     */
    public function actionLogout()
    {
        $user = Yii::$app->user->identity;

        if ($user) {
            // Если есть метод для инвалидации токена
            if (property_exists($user, 'access_token')) {
                $user->access_token = null;
                $user->save(false);
            }

            Yii::$app->user->logout();
        }

        return [
            'success' => true,
            'message' => 'Успешный выход',
        ];
    }
}
