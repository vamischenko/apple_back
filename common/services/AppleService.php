<?php

namespace common\services;

use Yii;
use common\models\Apple;
use common\repositories\AppleRepository;
use common\exceptions\AppleNotFoundException;
use common\exceptions\AppleInvalidStateException;
use common\exceptions\AppleValidationException;

/**
 * Сервис для бизнес-логики работы с яблоками
 *
 * Инкапсулирует бизнес-логику и координирует взаимодействие
 * между контроллерами и репозиторием.
 */
class AppleService
{
    /**
     * @var AppleRepository Репозиторий для работы с данными яблок
     */
    private $repository;

    /**
     * Конструктор сервиса
     *
     * @param AppleRepository $repository Репозиторий яблок (внедряется через DI)
     */
    public function __construct(AppleRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Получить все яблоки с обновленным статусом гниения
     *
     * Использует кеширование для оптимизации производительности.
     * Кеш сбрасывается при любых изменениях данных яблок.
     *
     * @return Apple[] Массив всех яблок
     */
    public function getAllApples(): array
    {
        $cache = Yii::$app->cache;
        $key = 'apples_list';

        $apples = $cache->get($key);
        if ($apples === false) {
            $this->repository->updateRottenStatusForAll();
            $apples = $this->repository->findAll();
            $cache->set($key, $apples, 60); // Кеш на 60 секунд
        }

        return $apples;
    }

    /**
     * Сгенерировать случайные яблоки
     *
     * @param int $count Количество яблок для генерации (1-50)
     * @return int Количество фактически сгенерированных яблок
     * @throws AppleValidationException Если количество вне допустимого диапазона
     * @throws \Throwable
     */
    public function generateRandomApples(int $count): int
    {
        $count = (int)$count;

        if ($count < 1 || $count > 50) {
            Yii::warning("Attempt to generate invalid count of apples: {$count}", 'apple');
            throw AppleValidationException::invalidCount($count);
        }

        Yii::info("Starting generation of {$count} random apples", 'apple');

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $generated = 0;

            for ($i = 0; $i < $count; $i++) {
                if (Apple::createRandomApple()) {
                    $generated++;
                }
            }

            $transaction->commit();

            // Сбросить кеш после генерации
            $this->clearCache();

            Yii::info("Successfully generated {$generated} out of {$count} requested apples", 'apple');

            return $generated;
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error("Failed to generate apples: {$e->getMessage()}", 'apple');
            throw $e;
        }
    }

    /**
     * Уронить яблоко на землю
     *
     * @param int $id Идентификатор яблока
     * @return void
     * @throws AppleNotFoundException Если яблоко не найдено
     * @throws AppleInvalidStateException Если яблоко не на дереве
     * @throws \Throwable
     */
    public function fallApple(int $id): void
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $apple = $this->repository->findById($id);
            $apple->fallToGround();

            $transaction->commit();

            // Сбросить кеш после изменения
            $this->clearCache();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Съесть часть яблока
     *
     * @param int $id Идентификатор яблока
     * @param float $percent Процент для съедения (0-100)
     * @return void
     * @throws AppleNotFoundException Если яблоко не найдено
     * @throws AppleInvalidStateException Если яблоко на дереве или гнилое
     * @throws AppleValidationException Если процент некорректный
     * @throws \Throwable
     */
    public function eatApple(int $id, float $percent): void
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $apple = $this->repository->findById($id);
            $apple->eat($percent);

            $transaction->commit();

            // Сбросить кеш после изменения
            $this->clearCache();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Удалить яблоко
     *
     * @param int $id Идентификатор яблока
     * @return void
     * @throws AppleNotFoundException Если яблоко не найдено
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function deleteApple(int $id): void
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $apple = $this->repository->findById($id);

            Yii::info("Deleting apple #{$id} (color: {$apple->color}, status: {$apple->status})", 'apple');

            $this->repository->delete($apple);

            $transaction->commit();

            // Сбросить кеш после удаления
            $this->clearCache();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Найти яблоко по ID
     *
     * @param int $id Идентификатор яблока
     * @return Apple
     * @throws AppleNotFoundException Если яблоко не найдено
     */
    public function findApple(int $id): Apple
    {
        return $this->repository->findById($id);
    }

    /**
     * Получить статистику по яблокам
     *
     * @return array Массив со статистикой
     */
    public function getStatistics(): array
    {
        return $this->repository->getStatistics();
    }

    /**
     * Сбросить кеш списка яблок и метрик
     *
     * Вызывается после любых операций изменения данных
     * (создание, обновление, удаление яблок).
     *
     * @return void
     */
    private function clearCache(): void
    {
        $cache = Yii::$app->cache;
        $cache->delete('apples_list');
        $cache->delete('apple_metrics_full_report');
    }
}
