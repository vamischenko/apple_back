<?php

namespace common\exceptions;

use Exception;

/**
 * Исключение валидации яблока
 *
 * Выбрасывается при некорректных входных данных
 * (например, попытка съесть отрицательный процент).
 */
class AppleValidationException extends Exception
{
    /**
     * Предопределенные сообщения об ошибках
     */
    const ERROR_INVALID_PERCENT = 'Процент должен быть от 0 до 100';
    const ERROR_EXCEEDS_REMAINING = 'Нельзя съесть больше, чем осталось';
    const ERROR_INVALID_COUNT = 'Количество должно быть от 1 до 50';

    /**
     * Создать исключение "некорректный процент"
     *
     * @param float $percent Некорректное значение
     * @return static
     */
    public static function invalidPercent($percent)
    {
        return new static(self::ERROR_INVALID_PERCENT . " (указано: {$percent})");
    }

    /**
     * Создать исключение "превышен остаток"
     *
     * @param float $percent Процент, который пытались съесть
     * @param float $remaining Процент, который остался
     * @return static
     */
    public static function exceedsRemaining($percent, $remaining)
    {
        return new static(
            "Нельзя съесть {$percent}%, осталось только {$remaining}%"
        );
    }

    /**
     * Создать исключение "некорректное количество"
     *
     * @param int $count Некорректное значение
     * @return static
     */
    public static function invalidCount($count)
    {
        return new static(self::ERROR_INVALID_COUNT . " (указано: {$count})");
    }
}
