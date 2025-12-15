<?php

use yii\helpers\Html;
use yii\helpers\Url;
use common\models\Apple;

/* @var $this yii\web\View */
/* @var $apples common\models\Apple[] */

$this->title = 'Управление яблоками';
$this->params['breadcrumbs'][] = $this->title;
?>

<style>
    .apple-card {
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        background: #f9f9f9;
    }
    .apple-card.on-tree {
        background: #e8f5e9;
    }
    .apple-card.fallen {
        background: #fff3e0;
    }
    .apple-card.rotten {
        background: #ffebee;
    }
    .apple-emoji {
        font-size: 48px;
        display: inline-block;
        margin-right: 15px;
    }
    .apple-info {
        display: inline-block;
        vertical-align: top;
    }
    .apple-actions {
        margin-top: 10px;
    }
    .progress {
        height: 25px;
        margin-top: 10px;
    }
    .generate-form {
        background: #e3f2fd;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
</style>

<div class="apple-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="generate-form">
        <h3>Генерация яблок</h3>
        <?php echo Html::beginForm(['generate'], 'post', ['style' => 'display: inline-block;']); ?>
            <div class="form-group" style="display: inline-block; margin-right: 10px;">
                <?= Html::input('number', 'count', 5, [
                    'class' => 'form-control',
                    'min' => 1,
                    'max' => 50,
                    'style' => 'width: 100px; display: inline-block;'
                ]) ?>
            </div>
            <?= Html::submitButton('🌳 Сгенерировать яблоки', ['class' => 'btn btn-success']) ?>
        <?php echo Html::endForm(); ?>
    </div>

    <?php if (Yii::$app->session->hasFlash('success')): ?>
        <div class="alert alert-success">
            <?= Yii::$app->session->getFlash('success') ?>
        </div>
    <?php endif; ?>

    <?php if (Yii::$app->session->hasFlash('error')): ?>
        <div class="alert alert-danger">
            <?= Yii::$app->session->getFlash('error') ?>
        </div>
    <?php endif; ?>

    <?php if (empty($apples)): ?>
        <div class="alert alert-info">
            Нет яблок. Сгенерируйте несколько яблок, чтобы начать работу.
        </div>
    <?php else: ?>
        <h3>Всего яблок: <?= count($apples) ?></h3>

        <?php foreach ($apples as $apple): ?>
            <?php
            $statusClass = '';
            if ($apple->status === Apple::STATUS_ON_TREE) {
                $statusClass = 'on-tree';
                $emoji = '🍏';
            } elseif ($apple->status === Apple::STATUS_ROTTEN) {
                $statusClass = 'rotten';
                $emoji = '🤢';
            } else {
                $statusClass = 'fallen';
                $emoji = '🍎';
            }

            // Цвет эмодзи в зависимости от цвета яблока
            if ($apple->color === 'green') {
                $emoji = '🍏';
            } elseif ($apple->color === 'red') {
                $emoji = '🍎';
            } else {
                $emoji = '🍋'; // желтое
            }

            if ($apple->status === Apple::STATUS_ROTTEN) {
                $emoji = '🤢';
            }
            ?>

            <div class="apple-card <?= $statusClass ?>">
                <div>
                    <span class="apple-emoji"><?= $emoji ?></span>
                    <div class="apple-info">
                        <strong>Яблоко #<?= $apple->id ?></strong><br>
                        <strong>Цвет:</strong> <?= Html::encode($apple->color) ?><br>
                        <strong>Статус:</strong> <span class="badge badge-info"><?= $apple->getStatusLabel() ?></span><br>
                        <strong>Появилось:</strong> <?= $apple->formatDate($apple->created_at) ?><br>
                        <?php if ($apple->fell_at): ?>
                            <strong>Упало:</strong> <?= $apple->formatDate($apple->fell_at) ?><br>
                        <?php endif; ?>
                        <strong>Размер:</strong> <?= number_format($apple->getSize(), 2) ?> (съедено: <?= $apple->eaten_percent ?>%)
                    </div>
                </div>

                <!-- Прогресс-бар съеденной части -->
                <?php if ($apple->eaten_percent > 0): ?>
                    <div class="progress">
                        <div class="progress-bar bg-warning" role="progressbar"
                             style="width: <?= $apple->eaten_percent ?>%"
                             aria-valuenow="<?= $apple->eaten_percent ?>"
                             aria-valuemin="0"
                             aria-valuemax="100">
                            Съедено: <?= $apple->eaten_percent ?>%
                        </div>
                    </div>
                <?php endif; ?>

                <div class="apple-actions">
                    <?php if ($apple->status === Apple::STATUS_ON_TREE): ?>
                        <!-- Кнопка "Упасть" -->
                        <?= Html::beginForm(['fall', 'id' => $apple->id], 'post', ['style' => 'display: inline-block;']) ?>
                            <?= Html::submitButton('⬇️ Упасть', ['class' => 'btn btn-warning btn-sm']) ?>
                        <?= Html::endForm() ?>
                    <?php endif; ?>

                    <?php if ($apple->status === Apple::STATUS_FALLEN): ?>
                        <!-- Форма "Съесть" с указанием процента -->
                        <?= Html::beginForm(['eat', 'id' => $apple->id], 'post', ['style' => 'display: inline-block; margin-right: 5px;']) ?>
                            <div class="input-group input-group-sm" style="width: 200px; display: inline-flex;">
                                <?= Html::input('number', 'percent', 25, [
                                    'class' => 'form-control',
                                    'min' => 1,
                                    'max' => 100 - $apple->eaten_percent,
                                    'step' => 0.01,
                                    'placeholder' => '%'
                                ]) ?>
                                <div class="input-group-append">
                                    <?= Html::submitButton('🍴 Съесть %', ['class' => 'btn btn-primary btn-sm']) ?>
                                </div>
                            </div>
                        <?= Html::endForm() ?>
                    <?php endif; ?>

                    <!-- Кнопка удаления -->
                    <?= Html::beginForm(['delete', 'id' => $apple->id], 'post', [
                        'style' => 'display: inline-block;',
                        'onsubmit' => 'return confirm("Вы уверены, что хотите удалить это яблоко?");'
                    ]) ?>
                        <?= Html::submitButton('🗑️ Удалить', ['class' => 'btn btn-danger btn-sm']) ?>
                    <?= Html::endForm() ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>
