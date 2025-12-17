<?php

namespace backend\controllers\api;

use OpenApi\Attributes as OA;

/**
 * Базовая информация для OpenAPI документации
 */
#[OA\Info(
    version: "1.0.0",
    title: "Apple Management API",
    description: "REST API для управления яблоками с полным жизненным циклом: создание, падение, съедение и гниение",
    contact: new OA\Contact(
        email: "admin@example.com"
    )
)]
#[OA\Server(
    url: "http://localhost:21080",
    description: "Development Server"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT"
)]
#[OA\Tag(
    name: "Apples",
    description: "Операции с яблоками"
)]
#[OA\Tag(
    name: "Auth",
    description: "Аутентификация и авторизация"
)]
#[OA\Tag(
    name: "Metrics",
    description: "Метрики и статистика"
)]
class OpenApiInfo
{
}
