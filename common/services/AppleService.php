<?php

namespace common\services;

use common\models\Apple;
use common\repositories\AppleRepository;

/**
 * Service for Apple business logic
 */
class AppleService
{
    /**
     * @var AppleRepository
     */
    private $repository;

    /**
     * AppleService constructor.
     *
     * @param AppleRepository|null $repository
     */
    public function __construct(AppleRepository $repository = null)
    {
        $this->repository = $repository ?: new AppleRepository();
    }

    /**
     * Get all apples with updated rotten status
     *
     * @return Apple[]
     */
    public function getAllApples()
    {
        $this->repository->updateRottenStatusForAll();
        return $this->repository->findAll();
    }

    /**
     * Generate random apples
     *
     * @param int $count Number of apples to generate
     * @return int Number of actually generated apples
     */
    public function generateRandomApples($count)
    {
        $count = max(1, min(50, (int)$count));
        $generated = 0;

        for ($i = 0; $i < $count; $i++) {
            if (Apple::createRandomApple()) {
                $generated++;
            }
        }

        return $generated;
    }

    /**
     * Make apple fall to ground
     *
     * @param int $id
     * @return void
     * @throws \Exception
     */
    public function fallApple($id)
    {
        $apple = $this->repository->findById($id);
        $apple->fallToGround();
    }

    /**
     * Eat part of apple
     *
     * @param int $id
     * @param float $percent
     * @return void
     * @throws \Exception
     */
    public function eatApple($id, $percent)
    {
        $apple = $this->repository->findById($id);
        $apple->eat($percent);
    }

    /**
     * Delete apple
     *
     * @param int $id
     * @return void
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     * @throws \yii\web\NotFoundHttpException
     */
    public function deleteApple($id)
    {
        $apple = $this->repository->findById($id);
        $this->repository->delete($apple);
    }

    /**
     * Find apple by ID
     *
     * @param int $id
     * @return Apple
     * @throws \yii\web\NotFoundHttpException
     */
    public function findApple($id)
    {
        return $this->repository->findById($id);
    }
}
