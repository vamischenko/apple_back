<?php

namespace backend\controllers\api;

use Yii;
use yii\rest\ActiveController;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\ContentNegotiator;
use yii\web\Response;
use common\models\Apple;
use common\services\AppleService;
use yii\web\NotFoundHttpException;

/**
 * REST API контроллер для управления яблоками
 *
 * Endpoints:
 * GET    /api/apples       - список всех яблок
 * GET    /api/apples/{id}  - получить яблоко по ID
 * POST   /api/apples       - создать случайное яблоко
 * DELETE /api/apples/{id}  - удалить яблоко
 * POST   /api/apples/{id}/fall - уронить яблоко
 * POST   /api/apples/{id}/eat  - откусить от яблока
 * POST   /api/apples/generate  - сгенерировать несколько яблок
 */
class AppleController extends ActiveController
{
    public $modelClass = 'common\models\Apple';

    /**
     * @var AppleService
     */
    private $appleService;

    /**
     * Конструктор контроллера
     */
    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->appleService = new AppleService();
    }

    /**
     * Настройка behaviors
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // Отключаем аутентификацию для упрощения (можно включить при необходимости)
        unset($behaviors['authenticator']);

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
     * Переопределяем список действий
     */
    public function actions()
    {
        $actions = parent::actions();

        // Отключаем стандартные действия, чтобы использовать свои
        unset($actions['create'], $actions['update']);

        return $actions;
    }

    /**
     * GET /api/apples - получить список всех яблок
     */
    public function actionIndex()
    {
        $apples = $this->appleService->getAllApples();

        return array_map(function($apple) {
            return $this->formatAppleResponse($apple);
        }, $apples);
    }

    /**
     * GET /api/apples/{id} - получить яблоко по ID
     */
    public function actionView($id)
    {
        try {
            $apple = $this->appleService->findApple($id);
            return $this->formatAppleResponse($apple);
        } catch (NotFoundHttpException $e) {
            Yii::$app->response->statusCode = 404;
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * POST /api/apples - создать случайное яблоко
     */
    public function actionCreate()
    {
        $apple = Apple::createRandomApple();

        if ($apple) {
            Yii::$app->response->statusCode = 201;
            return $this->formatAppleResponse($apple);
        }

        Yii::$app->response->statusCode = 400;
        return ['error' => 'Не удалось создать яблоко'];
    }

    /**
     * POST /api/apples/generate - сгенерировать несколько яблок
     */
    public function actionGenerate()
    {
        $count = Yii::$app->request->post('count', 1);
        $generated = $this->appleService->generateRandomApples($count);

        return [
            'success' => true,
            'generated' => $generated,
            'message' => "Сгенерировано яблок: {$generated}"
        ];
    }

    /**
     * POST /api/apples/{id}/fall - уронить яблоко
     */
    public function actionFall($id)
    {
        try {
            $this->appleService->fallApple($id);
            $apple = $this->appleService->findApple($id);

            return [
                'success' => true,
                'message' => 'Яблоко упало на землю',
                'apple' => $this->formatAppleResponse($apple)
            ];
        } catch (\Exception $e) {
            Yii::$app->response->statusCode = 400;
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * POST /api/apples/{id}/eat - откусить от яблока
     */
    public function actionEat($id)
    {
        $percent = (float)Yii::$app->request->post('percent', 25);

        try {
            $this->appleService->eatApple($id, $percent);

            // Проверяем, существует ли еще яблоко (могло быть удалено, если съедено полностью)
            try {
                $apple = $this->appleService->findApple($id);
                return [
                    'success' => true,
                    'message' => "Откушено {$percent}% яблока",
                    'apple' => $this->formatAppleResponse($apple)
                ];
            } catch (NotFoundHttpException $e) {
                return [
                    'success' => true,
                    'message' => "Яблоко съедено полностью и удалено",
                    'apple' => null
                ];
            }
        } catch (\Exception $e) {
            Yii::$app->response->statusCode = 400;
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * DELETE /api/apples/{id} - удалить яблоко
     */
    public function actionDelete($id)
    {
        try {
            $this->appleService->deleteApple($id);

            Yii::$app->response->statusCode = 204;
            return [
                'success' => true,
                'message' => 'Яблоко удалено'
            ];
        } catch (\Exception $e) {
            Yii::$app->response->statusCode = 400;
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Форматирование яблока для ответа API
     */
    private function formatAppleResponse(Apple $apple)
    {
        return [
            'id' => $apple->id,
            'color' => $apple->color,
            'status' => $apple->status,
            'status_label' => $apple->getStatusLabel(),
            'created_at' => $apple->created_at,
            'created_at_formatted' => $apple->formatDate($apple->created_at),
            'fell_at' => $apple->fell_at,
            'fell_at_formatted' => $apple->formatDate($apple->fell_at),
            'eaten_percent' => $apple->eaten_percent,
            'size' => $apple->getSize(),
        ];
    }
}
