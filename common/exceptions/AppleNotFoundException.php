<?php

namespace common\exceptions;

use yii\web\NotFoundHttpException;

/**
 * Исключение "Яблоко не найдено"
 *
 * Выбрасывается, когда запрашиваемое яблоко не найдено в базе данных.
 */
class AppleNotFoundException extends NotFoundHttpException
{
    /**
     * Конструктор
     *
     * @param int|null $id Идентификатор яблока
     * @param int $code Код ошибки
     * @param \Throwable|null $previous Предыдущее исключение
     */
    public function __construct($id = null, $code = 0, \Throwable $previous = null)
    {
        $message = $id !== null
            ? "Яблоко с ID {$id} не найдено"
            : "Яблоко не найдено";

        parent::__construct($message, $code, $previous);
    }
}
