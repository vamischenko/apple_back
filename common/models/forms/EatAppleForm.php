<?php

namespace common\models\forms;

use yii\base\Model;

/**
 * Форма для валидации съедения яблока
 *
 * Валидирует процент яблока, который нужно съесть.
 */
class EatAppleForm extends Model
{
    /**
     * @var float Процент для съедения (0-100)
     */
    public $percent;

    /**
     * Правила валидации
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            ['percent', 'required', 'message' => 'Укажите процент для съедения'],
            ['percent', 'number', 'message' => 'Процент должен быть числом'],
            ['percent', 'number', 'min' => 0.01, 'max' => 100,
                'tooSmall' => 'Минимальный процент - 0.01%',
                'tooBig' => 'Максимальный процент - 100%'],
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
            'percent' => 'Процент для съедения',
        ];
    }
}
