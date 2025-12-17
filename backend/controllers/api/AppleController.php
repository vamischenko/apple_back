<?php

namespace backend\controllers\api;

use Yii;
use yii\rest\ActiveController;
use yii\filters\ContentNegotiator;
use yii\web\Response;
use common\models\Apple;
use common\models\forms\GenerateApplesForm;
use common\models\forms\EatAppleForm;
use common\services\AppleService;
use common\services\AppleMetricsService;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;

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
 * GET    /api/apples/metrics   - получить метрики и статистику
 */
class AppleController extends ActiveController
{
    public $modelClass = 'common\models\Apple';

    /**
     * @var AppleService
     */
    private AppleService $appleService;

    /**
     * @var AppleMetricsService
     */
    private AppleMetricsService $metricsService;

    /**
     * Конструктор контроллера с Dependency Injection
     *
     * @param string $id ID контроллера
     * @param \yii\base\Module $module Модуль контроллера
     * @param AppleService $appleService Сервис яблок (внедряется через DI)
     * @param AppleMetricsService $metricsService Сервис метрик (внедряется через DI)
     * @param array $config Конфигурация
     */
    public function __construct(
        $id,
        $module,
        AppleService $appleService,
        AppleMetricsService $metricsService,
        $config = []
    ) {
        parent::__construct($id, $module, $config);
        $this->appleService = $appleService;
        $this->metricsService = $metricsService;
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
     * GET /api/apples - получить список всех яблок с пагинацией
     *
     * Query параметры:
     * - page: номер страницы (по умолчанию 1)
     * - per-page: элементов на странице (по умолчанию 20, макс 100)
     */
    public function actionIndex()
    {
        $page = max(1, (int)Yii::$app->request->get('page', 1));
        $perPage = min(100, max(1, (int)Yii::$app->request->get('per-page', 20)));

        $allApples = $this->appleService->getAllApples();
        $total = count($allApples);

        // Пагинация
        $offset = ($page - 1) * $perPage;
        $apples = array_slice($allApples, $offset, $perPage);

        $items = array_map(function($apple) {
            return $this->formatAppleResponse($apple);
        }, $apples);

        return [
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
            ],
        ];
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
        $form = new GenerateApplesForm();
        $form->load(Yii::$app->request->post(), '');

        if (!$form->validate()) {
            Yii::$app->response->statusCode = 400;
            return [
                'success' => false,
                'errors' => $form->errors,
                'message' => 'Ошибка валидации данных'
            ];
        }

        try {
            $generated = $this->appleService->generateRandomApples($form->count);

            return [
                'success' => true,
                'generated' => $generated,
                'message' => "Сгенерировано яблок: {$generated}"
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
        $form = new EatAppleForm();
        $form->load(Yii::$app->request->post(), '');

        if (!$form->validate()) {
            Yii::$app->response->statusCode = 400;
            return [
                'success' => false,
                'errors' => $form->errors,
                'message' => 'Ошибка валидации данных'
            ];
        }

        try {
            $this->appleService->eatApple($id, $form->percent);

            // Проверяем, существует ли еще яблоко (могло быть удалено, если съедено полностью)
            try {
                $apple = $this->appleService->findApple($id);
                return [
                    'success' => true,
                    'message' => "Откушено {$form->percent}% яблока",
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
     * GET /api/apples/metrics - получить метрики и статистику
     */
    public function actionMetrics(): array
    {
        $type = Yii::$app->request->get('type', 'full');

        switch ($type) {
            case 'extended':
                return $this->metricsService->getExtendedStatistics();
            case 'creation':
                return $this->metricsService->getCreationMetrics();
            case 'rotten':
                return $this->metricsService->getRottenMetrics();
            case 'eating':
                return $this->metricsService->getEatingMetrics();
            case 'full':
            default:
                return $this->metricsService->getFullReport();
        }
    }

    /**
     * Форматирование яблока для ответа API
     */
    private function formatAppleResponse(Apple $apple): array
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
