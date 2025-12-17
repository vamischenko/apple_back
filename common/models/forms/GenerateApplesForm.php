<?php

namespace common\models\forms;

use yii\base\Model;

/**
 * Форма для валидации генерации яблок
 *
 * Валидирует количество яблок, которое нужно сгенерировать.
 */
class GenerateApplesForm extends Model
{
    /**
     * @var int Количество яблок для генерации
     */
    public $count;

    /**
     * Правила валидации
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            ['count', 'required', 'message' => 'Укажите количество яблок'],
            ['count', 'integer', 'message' => 'Количество должно быть целым числом'],
            ['count', 'integer', 'min' => 1, 'max' => 50,
                'tooSmall' => 'Минимальное количество - 1 яблоко',
                'tooBig' => 'Максимальное количество - 50 яблок'],
        ];
    }

    /**
     * Метки атрибутов
     *
     * @return array
     */
    public function attributeLabels(): array
    {
        return [
            'count' => 'Количество яблок',
        ];
    }
}
