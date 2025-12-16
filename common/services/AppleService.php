<?php

namespace common\services;

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
     * @return Apple[] Массив всех яблок
     */
    public function getAllApples()
    {
        $this->repository->updateRottenStatusForAll();
        return $this->repository->findAll();
    }

    /**
     * Сгенерировать случайные яблоки
     *
     * @param int $count Количество яблок для генерации (1-50)
     * @return int Количество фактически сгенерированных яблок
     * @throws AppleValidationException Если количество вне допустимого диапазона
     */
    public function generateRandomApples($count)
    {
        $count = (int)$count;

        if ($count < 1 || $count > 50) {
            throw AppleValidationException::invalidCount($count);
        }

        $generated = 0;

        for ($i = 0; $i < $count; $i++) {
            if (Apple::createRandomApple()) {
                $generated++;
            }
        }

        return $generated;
    }

    /**
     * Уронить яблоко на землю
     *
     * @param int $id Идентификатор яблока
     * @return void
     * @throws AppleNotFoundException Если яблоко не найдено
     * @throws AppleInvalidStateException Если яблоко не на дереве
     */
    public function fallApple($id)
    {
        $apple = $this->repository->findById($id);
        $apple->fallToGround();
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
     */
    public function eatApple($id, $percent)
    {
        $apple = $this->repository->findById($id);
        $apple->eat($percent);
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
    public function deleteApple($id)
    {
        $apple = $this->repository->findById($id);
        $this->repository->delete($apple);
    }

    /**
     * Найти яблоко по ID
     *
     * @param int $id Идентификатор яблока
     * @return Apple
     * @throws AppleNotFoundException Если яблоко не найдено
     */
    public function findApple($id)
    {
        return $this->repository->findById($id);
    }

    /**
     * Получить статистику по яблокам
     *
     * @return array Массив со статистикой
     */
    public function getStatistics()
    {
        return $this->repository->getStatistics();
    }
}
