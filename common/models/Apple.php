<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use common\exceptions\AppleInvalidStateException;
use common\exceptions\AppleValidationException;

/**
 * Модель яблока
 *
 * Представляет яблоко в системе с возможностью отслеживания его состояния,
 * от появления на дереве до падения, гниения и съедения.
 *
 * @property int $id ID яблока
 * @property string $color Цвет яблока (red, green, yellow)
 * @property int $created_at Временная метка появления яблока (UNIX timestamp)
 * @property int|null $fell_at Временная метка падения яблока (UNIX timestamp)
 * @property string $status Статус яблока (on_tree, fallen, rotten)
 * @property float $eaten_percent Процент съеденной части (0-100)
 *
 * @property-read float $size Размер яблока (от 0 до 1)
 * @property-read string $statusLabel Человекочитаемое название статуса
 */
class Apple extends ActiveRecord
{
    /** @var string Событие: яблоко упало */
    const EVENT_APPLE_FALLEN = 'appleFallen';

    /** @var string Событие: яблоко испортилось */
    const EVENT_APPLE_ROTTEN = 'appleRotten';

    /** @var string Статус: яблоко на дереве */
    const STATUS_ON_TREE = 'on_tree';

    /** @var string Статус: яблоко упало на землю */
    const STATUS_FALLEN = 'fallen';

    /** @var string Статус: яблоко испортилось */
    const STATUS_ROTTEN = 'rotten';

    /** @var array Доступные цвета яблок */
    const COLORS = ['red', 'green', 'yellow'];

    /** @var int Время в секундах, через которое яблоко портится после падения (5 часов) */
    const ROTTEN_TIME = 5 * 3600;

    /**
     * Возвращает имя таблицы базы данных
     *
     * @return string Имя таблицы
     */
    public static function tableName(): string
    {
        return 'apple';
    }

    /**
     * Правила валидации атрибутов модели
     *
     * @return array Массив правил валидации
     */
    public function rules(): array
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
     * Возвращает метки атрибутов для отображения
     *
     * @return array Ассоциативный массив меток атрибутов
     */
    public function attributeLabels(): array
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
     * Генерирует яблоко с случайным цветом из доступных вариантов,
     * случайной датой появления за последние 30 дней, статусом "на дереве"
     * и нулевым процентом съеденной части.
     *
     * @return Apple|null Созданное яблоко или null в случае ошибки сохранения
     */
    public static function createRandomApple(): ?self
    {
        $apple = new self();
        $apple->color = self::COLORS[array_rand(self::COLORS)];
        $apple->created_at = rand(time() - 30 * 24 * 3600, time()); // случайная дата за последние 30 дней
        $apple->status = self::STATUS_ON_TREE;
        $apple->eaten_percent = 0;

        if ($apple->save()) {
            Yii::info("Created new apple #{$apple->id} (color: {$apple->color})", 'apple');
            return $apple;
        }

        Yii::error("Failed to create random apple", 'apple');
        return null;
    }

    /**
     * Яблоко падает с дерева
     *
     * Изменяет статус яблока на "упало" и записывает время падения.
     * Метод может быть вызван только для яблок, находящихся на дереве.
     *
     * @return void
     * @throws AppleInvalidStateException Если яблоко не на дереве или не удалось сохранить изменения
     */
    public function fallToGround(): void
    {
        if ($this->status !== self::STATUS_ON_TREE) {
            Yii::warning("Attempt to drop apple #{$this->id} that is not on tree (status: {$this->status})", 'apple');
            throw AppleInvalidStateException::alreadyFallen();
        }

        $this->status = self::STATUS_FALLEN;
        $this->fell_at = time();

        if (!$this->save()) {
            Yii::error("Failed to save apple #{$this->id} after falling", 'apple');
            throw AppleInvalidStateException::saveFailed();
        }

        Yii::info("Apple #{$this->id} (color: {$this->color}) fallen to ground", 'apple');

        // Генерация события о падении яблока
        $this->trigger(self::EVENT_APPLE_FALLEN);
    }

    /**
     * Откусить от яблока
     *
     * Увеличивает процент съеденной части яблока на указанное значение.
     * Метод выполняет следующие проверки:
     * - Яблоко не должно находиться на дереве
     * - Яблоко не должно быть гнилым (автоматически обновляет статус)
     * - Процент должен быть в диапазоне (0, 100]
     * - Сумма съеденного не должна превышать 100%
     *
     * Если яблоко съедено полностью (100%), оно автоматически удаляется из БД.
     *
     * @param float $percent Процент откушенной части (0-100)
     * @return void
     * @throws AppleInvalidStateException Если яблоко на дереве, испорчено или не удалось сохранить
     * @throws AppleValidationException Если процент некорректный или превышает остаток
     */
    public function eat(float $percent): void
    {
        // Проверяем, что яблоко на земле
        if ($this->status === self::STATUS_ON_TREE) {
            Yii::warning("Attempt to eat apple #{$this->id} on tree", 'apple');
            throw AppleInvalidStateException::onTree();
        }

        // Обновляем статус, если яблоко испортилось
        $this->updateRottenStatus();

        // Проверяем, что яблоко не гнилое
        if ($this->status === self::STATUS_ROTTEN) {
            Yii::warning("Attempt to eat rotten apple #{$this->id}", 'apple');
            throw AppleInvalidStateException::rotten();
        }

        // Проверяем валидность процента
        if ($percent <= 0 || $percent > 100) {
            Yii::warning("Invalid percent {$percent} for eating apple #{$this->id}", 'apple');
            throw AppleValidationException::invalidPercent($percent);
        }

        // Проверяем, что не пытаемся съесть больше, чем осталось
        $remaining = 100 - $this->eaten_percent;
        if ($percent > $remaining) {
            Yii::warning("Attempt to eat {$percent}% of apple #{$this->id}, but only {$remaining}% remaining", 'apple');
            throw AppleValidationException::exceedsRemaining($percent, $remaining);
        }

        $this->eaten_percent += $percent;

        if (!$this->save()) {
            Yii::error("Failed to save apple #{$this->id} after eating", 'apple');
            throw AppleInvalidStateException::saveFailed();
        }

        Yii::info("Eaten {$percent}% of apple #{$this->id} (color: {$this->color}), total eaten: {$this->eaten_percent}%", 'apple');

        // Если яблоко съедено полностью, удаляем его
        if ($this->eaten_percent >= 100) {
            Yii::info("Apple #{$this->id} completely eaten and deleted", 'apple');
            $this->delete();
        }
    }

    /**
     * Проверяет и обновляет статус яблока на "гнилое"
     *
     * Если яблоко упало и с момента падения прошло более 5 часов,
     * автоматически изменяет статус на "гнилое" и сохраняет изменения.
     *
     * @return void
     */
    public function updateRottenStatus(): void
    {
        if ($this->status === self::STATUS_FALLEN && $this->fell_at !== null) {
            $timeSinceFall = time() - $this->fell_at;

            if ($timeSinceFall >= self::ROTTEN_TIME) {
                $this->status = self::STATUS_ROTTEN;
                $this->save();

                Yii::info("Apple #{$this->id} (color: {$this->color}) became rotten after {$timeSinceFall} seconds", 'apple');

                // Генерация события о том, что яблоко испортилось
                $this->trigger(self::EVENT_APPLE_ROTTEN);
            }
        }
    }

    /**
     * Получить размер яблока
     *
     * Возвращает размер яблока в виде коэффициента от 0 до 1,
     * где 1 - яблоко целое, 0 - яблоко полностью съедено.
     * Размер вычисляется как (100 - процент_съеденного) / 100.
     *
     * @return float Размер яблока (0.0 - 1.0)
     */
    public function getSize(): float
    {
        return (100 - $this->eaten_percent) / 100;
    }

    /**
     * Получить человекочитаемое название статуса
     *
     * Преобразует технический статус яблока в локализованную метку
     * для отображения пользователю.
     *
     * @return string Локализованное название статуса
     */
    public function getStatusLabel(): string
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
     * Преобразует UNIX timestamp в читаемую строку формата "дд.мм.гггг чч:мм".
     * Если timestamp не указан (null или 0), возвращает "-".
     *
     * @param int|null $timestamp UNIX timestamp для форматирования
     * @return string Отформатированная дата или "-"
     */
    public function formatDate(?int $timestamp): string
    {
        return $timestamp ? date('d.m.Y H:i', $timestamp) : '-';
    }

    /**
     * Переопределение метода find для ActiveRecord
     *
     * В текущей реализации возвращает стандартный объект запроса.
     * Метод может быть расширен для автоматического обновления
     * статуса гнилости при выборке записей.
     *
     * @return \yii\db\ActiveQuery Объект запроса для выборки записей
     */
    public static function find()
    {
        return parent::find();
    }
}
