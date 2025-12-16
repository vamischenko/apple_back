<?php

namespace common\exceptions;

use Exception;

/**
 * Исключение "Недопустимое состояние яблока"
 *
 * Выбрасывается при попытке выполнить операцию, которая невозможна
 * в текущем состоянии яблока (например, съесть яблоко на дереве).
 */
class AppleInvalidStateException extends Exception
{
    /**
     * Предопределенные сообщения об ошибках
     */
    const ERROR_ALREADY_FALLEN = 'Яблоко уже не на дереве';
    const ERROR_ON_TREE = 'Съесть нельзя, яблоко на дереве';
    const ERROR_ROTTEN = 'Съесть нельзя, яблоко испорчено';
    const ERROR_SAVE_FAILED = 'Не удалось сохранить яблоко';

    /**
     * Создать исключение "яблоко уже упало"
     *
     * @return static
     */
    public static function alreadyFallen()
    {
        return new static(self::ERROR_ALREADY_FALLEN);
    }

    /**
     * Создать исключение "яблоко на дереве"
     *
     * @return static
     */
    public static function onTree()
    {
        return new static(self::ERROR_ON_TREE);
    }

    /**
     * Создать исключение "яблоко гнилое"
     *
     * @return static
     */
    public static function rotten()
    {
        return new static(self::ERROR_ROTTEN);
    }

    /**
     * Создать исключение "не удалось сохранить"
     *
     * @return static
     */
    public static function saveFailed()
    {
        return new static(self::ERROR_SAVE_FAILED);
    }
}
