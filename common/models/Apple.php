<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "apple".
 *
 * @property int $id
 * @property string $color
 * @property int $created_at
 * @property int|null $fell_at
 * @property string $status
 * @property float $eaten_percent
 */
class Apple extends ActiveRecord
{
    const STATUS_ON_TREE = 'on_tree';
    const STATUS_FALLEN = 'fallen';
    const STATUS_ROTTEN = 'rotten';

    const COLORS = ['red', 'green', 'yellow'];

    const ROTTEN_TIME = 5 * 3600; // 5 часов в секундах

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'apple';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['color', 'created_at'], 'required'],
            [['created_at', 'fell_at'], 'integer'],
            [['eaten_percent'], 'number', 'min' => 0, 'max' => 100],
            [['color'], 'string', 'max' => 50],
            [['status'], 'string', 'max' => 20],
            [['status'], 'in', 'range' => [self::STATUS_ON_TREE, self::STATUS_FALLEN, self::STATUS_ROTTEN]],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'color' => 'Цвет',
            'created_at' => 'Дата появления',
            'fell_at' => 'Дата падения',
            'status' => 'Статус',
            'eaten_percent' => 'Съедено (%)',
        ];
    }

    /**
     * Создает новое яблоко со случайным цветом и датой появления
     *
     * @return Apple
     */
    public static function createRandomApple()
    {
        $apple = new self();
        $apple->color = self::COLORS[array_rand(self::COLORS)];
        $apple->created_at = rand(time() - 30 * 24 * 3600, time()); // случайная дата за последние 30 дней
        $apple->status = self::STATUS_ON_TREE;
        $apple->eaten_percent = 0;

        if ($apple->save()) {
            return $apple;
        }

        return null;
    }

    /**
     * Яблоко падает с дерева
     *
     * @throws \Exception
     */
    public function fallToGround()
    {
        if ($this->status !== self::STATUS_ON_TREE) {
            throw new \Exception('Яблоко уже не на дереве');
        }

        $this->status = self::STATUS_FALLEN;
        $this->fell_at = time();

        if (!$this->save()) {
            throw new \Exception('Не удалось сохранить яблоко');
        }
    }

    /**
     * Откусить от яблока
     *
     * @param float $percent Процент откушенной части (0-100)
     * @throws \Exception
     */
    public function eat($percent)
    {
        // Проверяем, что яблоко на земле
        if ($this->status === self::STATUS_ON_TREE) {
            throw new \Exception('Съесть нельзя, яблоко на дереве');
        }

        // Обновляем статус, если яблоко испортилось
        $this->updateRottenStatus();

        // Проверяем, что яблоко не гнилое
        if ($this->status === self::STATUS_ROTTEN) {
            throw new \Exception('Съесть нельзя, яблоко испорчено');
        }

        // Проверяем валидность процента
        if ($percent <= 0 || $percent > 100) {
            throw new \Exception('Процент должен быть от 0 до 100');
        }

        // Проверяем, что не пытаемся съесть больше, чем осталось
        if ($this->eaten_percent + $percent > 100) {
            throw new \Exception('Нельзя съесть больше, чем осталось');
        }

        $this->eaten_percent += $percent;

        if (!$this->save()) {
            throw new \Exception('Не удалось сохранить яблоко');
        }

        // Если яблоко съедено полностью, удаляем его
        if ($this->eaten_percent >= 100) {
            $this->delete();
        }
    }

    /**
     * Проверяет и обновляет статус яблока на "гнилое", если прошло 5 часов с момента падения
     */
    public function updateRottenStatus()
    {
        if ($this->status === self::STATUS_FALLEN && $this->fell_at !== null) {
            $timeSinceFall = time() - $this->fell_at;

            if ($timeSinceFall >= self::ROTTEN_TIME) {
                $this->status = self::STATUS_ROTTEN;
                $this->save();
            }
        }
    }

    /**
     * Получить размер яблока (от 0 до 1)
     *
     * @return float
     */
    public function getSize()
    {
        return (100 - $this->eaten_percent) / 100;
    }

    /**
     * Получить человекочитаемое название статуса
     *
     * @return string
     */
    public function getStatusLabel()
    {
        $labels = [
            self::STATUS_ON_TREE => 'На дереве',
            self::STATUS_FALLEN => 'Упало',
            self::STATUS_ROTTEN => 'Гнилое',
        ];

        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Получить дату в читаемом формате
     *
     * @param int $timestamp
     * @return string
     */
    public function formatDate($timestamp)
    {
        return $timestamp ? date('d.m.Y H:i', $timestamp) : '-';
    }

    /**
     * Before find - обновляем статус гнилости для всех яблок
     */
    public static function find()
    {
        return parent::find();
    }
}
