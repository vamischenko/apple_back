<?php

namespace common\repositories;

use common\models\Apple;
use yii\web\NotFoundHttpException;

/**
 * Repository for Apple model
 */
class AppleRepository
{
    /**
     * Find all apples
     *
     * @param array $orderBy
     * @return Apple[]
     */
    public function findAll(array $orderBy = ['id' => SORT_DESC])
    {
        return Apple::find()->orderBy($orderBy)->all();
    }

    /**
     * Find apple by ID
     *
     * @param int $id
     * @return Apple
     * @throws NotFoundHttpException
     */
    public function findById($id)
    {
        $model = Apple::findOne(['id' => $id]);

        if ($model === null) {
            throw new NotFoundHttpException('Яблоко не найдено.');
        }

        return $model;
    }

    /**
     * Save apple model
     *
     * @param Apple $apple
     * @return bool
     */
    public function save(Apple $apple)
    {
        return $apple->save();
    }

    /**
     * Delete apple model
     *
     * @param Apple $apple
     * @return false|int
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function delete(Apple $apple)
    {
        return $apple->delete();
    }

    /**
     * Create new apple instance
     *
     * @return Apple
     */
    public function create()
    {
        return new Apple();
    }

    /**
     * Update rotten status for all fallen apples
     *
     * @return void
     */
    public function updateRottenStatusForAll()
    {
        $apples = $this->findAll();

        foreach ($apples as $apple) {
            $apple->updateRottenStatus();
        }
    }
}
