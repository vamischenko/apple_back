<?php

namespace backend\controllers\api;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use OpenApi\Generator;

/**
 * Swagger контроллер для генерации OpenAPI документации
 */
class SwaggerController extends Controller
{
    /**
     * Отключаем CSRF для API
     */
    public $enableCsrfValidation = false;

    /**
     * GET /api/swagger - получить OpenAPI спецификацию в JSON
     */
    public function actionJson()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $openapi = Generator::scan([
            Yii::getAlias('@backend/controllers/api'),
        ]);

        Yii::$app->response->headers->set('Content-Type', 'application/json');
        return json_decode($openapi->toJson(), true);
    }

    /**
     * GET /api/swagger/ui - Swagger UI интерфейс
     */
    public function actionUi()
    {
        $swaggerUiDist = 'https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/';

        return $this->renderPartial('swagger-ui', [
            'swaggerUiDist' => $swaggerUiDist,
            'specUrl' => '/api/swagger/json',
        ]);
    }
}
