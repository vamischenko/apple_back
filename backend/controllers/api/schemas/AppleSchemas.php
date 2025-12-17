<?php

namespace backend\controllers\api\schemas;

use OpenApi\Attributes as OA;

/**
 * Схемы данных для Apple API
 */

#[OA\Schema(
    schema: "Apple",
    title: "Apple",
    description: "Модель яблока",
    required: ["id", "color", "status", "created_at", "eaten_percent"],
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "color", type: "string", enum: ["red", "green", "yellow"], example: "red"),
        new OA\Property(property: "status", type: "string", enum: ["on_tree", "fallen", "rotten"], example: "on_tree"),
        new OA\Property(property: "status_label", type: "string", example: "На дереве"),
        new OA\Property(property: "created_at", type: "integer", example: 1702742460),
        new OA\Property(property: "created_at_formatted", type: "string", example: "16.12.2023 15:41"),
        new OA\Property(property: "fell_at", type: "integer", nullable: true, example: null),
        new OA\Property(property: "fell_at_formatted", type: "string", example: "-"),
        new OA\Property(property: "eaten_percent", type: "number", format: "float", example: 0),
        new OA\Property(property: "size", type: "number", format: "float", example: 1.0),
    ]
)]
#[OA\Schema(
    schema: "GenerateApplesRequest",
    title: "Generate Apples Request",
    required: ["count"],
    properties: [
        new OA\Property(property: "count", type: "integer", minimum: 1, maximum: 50, example: 5),
    ]
)]
#[OA\Schema(
    schema: "EatAppleRequest",
    title: "Eat Apple Request",
    required: ["percent"],
    properties: [
        new OA\Property(property: "percent", type: "number", format: "float", minimum: 0.01, maximum: 100, example: 25),
    ]
)]
#[OA\Schema(
    schema: "LoginRequest",
    title: "Login Request",
    required: ["username", "password"],
    properties: [
        new OA\Property(property: "username", type: "string", example: "admin"),
        new OA\Property(property: "password", type: "string", format: "password", example: "admin123"),
    ]
)]
#[OA\Schema(
    schema: "ErrorResponse",
    title: "Error Response",
    properties: [
        new OA\Property(property: "success", type: "boolean", example: false),
        new OA\Property(property: "error", type: "string", example: "Яблоко не найдено"),
        new OA\Property(
            property: "errors",
            type: "object",
            example: ["count" => ["Укажите количество яблок"]]
        ),
    ]
)]
#[OA\Schema(
    schema: "SuccessResponse",
    title: "Success Response",
    properties: [
        new OA\Property(property: "success", type: "boolean", example: true),
        new OA\Property(property: "message", type: "string", example: "Операция выполнена успешно"),
    ]
)]
class AppleSchemas
{
}
