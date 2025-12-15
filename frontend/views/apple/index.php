<?php

use yii\helpers\Html;
use yii\helpers\Url;
use common\models\Apple;

/* @var $this yii\web\View */
/* @var $apples Apple[] */

$this->title = 'Управление яблоками';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="apple-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="row mb-3">
        <div class="col-md-12">
            <div class="btn-group" role="group">
                <?= Html::a('Создать яблоко', ['create'], ['class' => 'btn btn-success']) ?>

                <?= Html::beginForm(['generate'], 'post', ['class' => 'd-inline']) ?>
                    <div class="input-group d-inline-flex" style="width: auto;">
                        <input type="number" name="count" class="form-control" value="5" min="1" max="50" style="width: 80px;">
                        <button type="submit" class="btn btn-primary">Сгенерировать</button>
                    </div>
                <?= Html::endForm() ?>
            </div>
        </div>
    </div>

    <?php if (empty($apples)): ?>
        <div class="alert alert-info">
            Яблок пока нет. Создайте новое яблоко или сгенерируйте несколько.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Цвет</th>
                        <th>Статус</th>
                        <th>Дата появления</th>
                        <th>Дата падения</th>
                        <th>Съедено</th>
                        <th>Размер</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($apples as $apple): ?>
                        <tr>
                            <td><?= Html::encode($apple->id) ?></td>
                            <td>
                                <span class="badge" style="background-color: <?= Html::encode($apple->color) ?>; color: white;">
                                    <?= Html::encode($apple->color) ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $statusClass = match($apple->status) {
                                    Apple::STATUS_ON_TREE => 'success',
                                    Apple::STATUS_FALLEN => 'warning',
                                    Apple::STATUS_ROTTEN => 'danger',
                                    default => 'secondary',
                                };
                                ?>
                                <span class="badge bg-<?= $statusClass ?>">
                                    <?= Html::encode($apple->getStatusLabel()) ?>
                                </span>
                            </td>
                            <td><?= Html::encode($apple->formatDate($apple->created_at)) ?></td>
                            <td><?= Html::encode($apple->formatDate($apple->fell_at)) ?></td>
                            <td>
                                <div class="progress" style="min-width: 100px;">
                                    <div class="progress-bar" role="progressbar"
                                         style="width: <?= $apple->eaten_percent ?>%"
                                         aria-valuenow="<?= $apple->eaten_percent ?>"
                                         aria-valuemin="0"
                                         aria-valuemax="100">
                                        <?= number_format($apple->eaten_percent, 1) ?>%
                                    </div>
                                </div>
                            </td>
                            <td><?= number_format($apple->getSize() * 100, 1) ?>%</td>
                            <td>
                                <div class="btn-group-vertical btn-group-sm">
                                    <?= Html::a('Просмотр', ['view', 'id' => $apple->id], ['class' => 'btn btn-info btn-sm']) ?>

                                    <?php if ($apple->status === Apple::STATUS_ON_TREE): ?>
                                        <?= Html::beginForm(['fall', 'id' => $apple->id], 'post', ['style' => 'display:inline']) ?>
                                            <button type="submit" class="btn btn-warning btn-sm">Уронить</button>
                                        <?= Html::endForm() ?>
                                    <?php endif; ?>

                                    <?php if ($apple->status === Apple::STATUS_FALLEN): ?>
                                        <?= Html::beginForm(['eat', 'id' => $apple->id], 'post', ['style' => 'display:inline']) ?>
                                            <input type="hidden" name="percent" value="25">
                                            <button type="submit" class="btn btn-success btn-sm">Откусить 25%</button>
                                        <?= Html::endForm() ?>
                                    <?php endif; ?>

                                    <?= Html::beginForm(['delete', 'id' => $apple->id], 'post', [
                                        'style' => 'display:inline',
                                        'onsubmit' => 'return confirm("Вы уверены, что хотите удалить это яблоко?")'
                                    ]) ?>
                                        <button type="submit" class="btn btn-danger btn-sm">Удалить</button>
                                    <?= Html::endForm() ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="alert alert-info">
            <strong>Всего яблок:</strong> <?= count($apples) ?>
        </div>
    <?php endif; ?>
</div>

<style>
.btn-group-vertical {
    gap: 2px;
}
</style>
