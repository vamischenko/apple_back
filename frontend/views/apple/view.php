<?php

use yii\helpers\Html;
use common\models\Apple;

/* @var $this yii\web\View */
/* @var $apple Apple */

$this->title = 'Яблоко #' . $apple->id;
$this->params['breadcrumbs'][] = ['label' => 'Управление яблоками', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="apple-view">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3>Информация о яблоке</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th style="width: 40%;">ID</th>
                            <td><?= Html::encode($apple->id) ?></td>
                        </tr>
                        <tr>
                            <th>Цвет</th>
                            <td>
                                <span class="badge" style="background-color: <?= Html::encode($apple->color) ?>; color: white; padding: 10px 20px; font-size: 16px;">
                                    <?= Html::encode($apple->color) ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Статус</th>
                            <td>
                                <?php
                                $statusClass = match($apple->status) {
                                    Apple::STATUS_ON_TREE => 'success',
                                    Apple::STATUS_FALLEN => 'warning',
                                    Apple::STATUS_ROTTEN => 'danger',
                                    default => 'secondary',
                                };
                                ?>
                                <span class="badge bg-<?= $statusClass ?>" style="font-size: 14px; padding: 8px 15px;">
                                    <?= Html::encode($apple->getStatusLabel()) ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Дата появления</th>
                            <td><?= Html::encode($apple->formatDate($apple->created_at)) ?></td>
                        </tr>
                        <tr>
                            <th>Дата падения</th>
                            <td><?= Html::encode($apple->formatDate($apple->fell_at)) ?></td>
                        </tr>
                        <tr>
                            <th>Съедено</th>
                            <td>
                                <div class="progress" style="height: 30px;">
                                    <div class="progress-bar" role="progressbar"
                                         style="width: <?= $apple->eaten_percent ?>%; font-size: 16px; line-height: 30px;"
                                         aria-valuenow="<?= $apple->eaten_percent ?>"
                                         aria-valuemin="0"
                                         aria-valuemax="100">
                                        <?= number_format($apple->eaten_percent, 1) ?>%
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th>Текущий размер</th>
                            <td>
                                <strong style="font-size: 18px;"><?= number_format($apple->getSize() * 100, 1) ?>%</strong>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3>Действия с яблоком</h3>
                </div>
                <div class="card-body">
                    <?php if ($apple->status === Apple::STATUS_ON_TREE): ?>
                        <div class="alert alert-info">
                            Яблоко висит на дереве. Вы можете его уронить.
                        </div>

                        <?= Html::beginForm(['fall', 'id' => $apple->id], 'post') ?>
                            <button type="submit" class="btn btn-warning btn-lg w-100">
                                🍎 Уронить яблоко
                            </button>
                        <?= Html::endForm() ?>

                    <?php elseif ($apple->status === Apple::STATUS_FALLEN): ?>
                        <div class="alert alert-success">
                            Яблоко упало на землю. Вы можете его съесть.
                        </div>

                        <?= Html::beginForm(['eat', 'id' => $apple->id], 'post') ?>
                            <div class="mb-3">
                                <label for="percent" class="form-label">Сколько процентов откусить?</label>
                                <input type="number" class="form-control" id="percent" name="percent"
                                       value="25" min="1" max="<?= 100 - $apple->eaten_percent ?>" step="0.1">
                                <small class="form-text text-muted">
                                    Осталось: <?= number_format(100 - $apple->eaten_percent, 1) ?>%
                                </small>
                            </div>
                            <button type="submit" class="btn btn-success btn-lg w-100">
                                😋 Откусить яблоко
                            </button>
                        <?= Html::endForm() ?>

                    <?php elseif ($apple->status === Apple::STATUS_ROTTEN): ?>
                        <div class="alert alert-danger">
                            ❌ Яблоко испортилось! Оно пролежало на земле более 5 часов и теперь непригодно для употребления.
                        </div>
                    <?php endif; ?>

                    <hr>

                    <?= Html::beginForm(['delete', 'id' => $apple->id], 'post', [
                        'onsubmit' => 'return confirm("Вы уверены, что хотите удалить это яблоко?")'
                    ]) ?>
                        <button type="submit" class="btn btn-danger btn-lg w-100">
                            🗑️ Удалить яблоко
                        </button>
                    <?= Html::endForm() ?>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <?= Html::a('← Вернуться к списку', ['index'], ['class' => 'btn btn-primary']) ?>
    </div>
</div>
