<?php

namespace common\repositories;

use common\models\Apple;
use common\exceptions\AppleNotFoundException;

/**
 * Репозиторий для работы с моделью Apple
 *
 * Инкапсулирует логику доступа к данным яблок в базе данных.
 * Следует паттерну Repository для разделения бизнес-логики и логики доступа к данным.
 */
class AppleRepository
{
    /**
     * Получить все яблоки
     *
     * @param array $orderBy Параметры сортировки
     * @return Apple[] Массив всех яблок
     */
    public function findAll(array $orderBy = ['id' => SORT_DESC]): array
    {
        return Apple::find()->orderBy($orderBy)->all();
    }

    /**
     * Найти яблоко по ID
     *
     * @param int $id Идентификатор яблока
     * @return Apple
     * @throws AppleNotFoundException Если яблоко не найдено
     */
    public function findById(int $id): Apple
    {
        $model = Apple::findOne(['id' => $id]);

        if ($model === null) {
            throw new AppleNotFoundException($id);
        }

        // Обновляем статус гнилости перед возвратом
        $model->updateRottenStatus();

        return $model;
    }

    /**
     * Сохранить яблоко
     *
     * @param Apple $apple Яблоко для сохранения
     * @return bool True в случае успеха
     */
    public function save(Apple $apple): bool
    {
        return $apple->save();
    }

    /**
     * Удалить яблоко
     *
     * @param Apple $apple Яблоко для удаления
     * @return int|false Количество удаленных записей или false при ошибке
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function delete(Apple $apple)
    {
        return $apple->delete();
    }

    /**
     * Создать новый экземпляр яблока
     *
     * @return Apple
     */
    public function create(): Apple
    {
        return new Apple();
    }

    /**
     * Обновить статус гнилости для всех упавших яблок
     *
     * Проходит по всем яблокам и обновляет их статус,
     * если они испортились (лежат больше 5 часов).
     *
     * @return int Количество обновленных яблок
     */
    public function updateRottenStatusForAll(): int
    {
        $apples = $this->findAll();
        $updated = 0;

        foreach ($apples as $apple) {
            $oldStatus = $apple->status;
            $apple->updateRottenStatus();

            if ($apple->status !== $oldStatus) {
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Получить яблоки по статусу
     *
     * @param string $status Статус яблока (on_tree, fallen, rotten)
     * @return Apple[] Массив яблок с указанным статусом
     */
    public function findByStatus(string $status): array
    {
        return Apple::find()
            ->where(['status' => $status])
            ->all();
    }

    /**
     * Получить яблоки по цвету
     *
     * @param string $color Цвет яблока (red, green, yellow)
     * @return Apple[] Массив яблок указанного цвета
     */
    public function findByColor(string $color): array
    {
        return Apple::find()
            ->where(['color' => $color])
            ->all();
    }

    /**
     * Получить количество яблок
     *
     * @param string|null $status Опциональный фильтр по статусу
     * @return int Количество яблок
     */
    public function count(?string $status = null): int
    {
        $query = Apple::find();

        if ($status !== null) {
            $query->where(['status' => $status]);
        }

        return (int)$query->count();
    }

    /**
     * Получить статистику по яблокам
     *
     * @return array Массив со статистикой
     */
    public function getStatistics(): array
    {
        return [
            'total' => $this->count(),
            'on_tree' => $this->count(Apple::STATUS_ON_TREE),
            'fallen' => $this->count(Apple::STATUS_FALLEN),
            'rotten' => $this->count(Apple::STATUS_ROTTEN),
            'by_color' => [
                'red' => Apple::find()->where(['color' => 'red'])->count(),
                'green' => Apple::find()->where(['color' => 'green'])->count(),
                'yellow' => Apple::find()->where(['color' => 'yellow'])->count(),
            ],
        ];
    }

    /**
     * Проверить существование яблока по ID
     *
     * @param int $id Идентификатор яблока
     * @return bool True, если яблоко существует
     */
    public function exists(int $id): bool
    {
        return Apple::find()->where(['id' => $id])->exists();
    }
}

