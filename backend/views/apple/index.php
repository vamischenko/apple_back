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

    <!-- Контейнер для уведомлений AJAX -->
    <div id="ajax-notifications"></div>

    <div class="generate-form">
        <h3>Генерация яблок</h3>
        <form id="generate-form">
            <div class="form-group" style="display: inline-block; margin-right: 10px;">
                <input type="number" name="count" id="count-input" value="5" min="1" max="50" class="form-control" style="width: 100px; display: inline-block;">
            </div>
            <button type="submit" class="btn btn-success" id="generate-btn">
                🌳 Сгенерировать яблоки
            </button>
        </form>
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

            <div class="apple-card <?= $statusClass ?>" data-apple-id="<?= $apple->id ?>">
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
                        <button class="btn btn-warning btn-sm ajax-fall" data-id="<?= $apple->id ?>">
                            ⬇️ Упасть
                        </button>
                    <?php endif; ?>

                    <?php if ($apple->status === Apple::STATUS_FALLEN): ?>
                        <!-- Форма "Съесть" с указанием процента -->
                        <div style="display: inline-block; margin-right: 5px;">
                            <div class="input-group input-group-sm" style="width: 200px; display: inline-flex;">
                                <input type="number"
                                       class="form-control eat-percent-input"
                                       data-id="<?= $apple->id ?>"
                                       value="25"
                                       min="1"
                                       max="<?= 100 - $apple->eaten_percent ?>"
                                       step="0.01"
                                       placeholder="%">
                                <div class="input-group-append">
                                    <button class="btn btn-primary btn-sm ajax-eat" data-id="<?= $apple->id ?>">
                                        🍴 Съесть %
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Кнопка удаления -->
                    <button class="btn btn-danger btn-sm ajax-delete" data-id="<?= $apple->id ?>">
                        🗑️ Удалить
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

<script>
// CSRF Token для Yii2
const csrfToken = '<?= Yii::$app->request->csrfToken ?>';

// Функция для показа уведомлений
function showNotification(message, type = 'success') {
    const container = document.getElementById('ajax-notifications');
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.role = 'alert';
    alert.innerHTML = `
        ${message}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    `;
    container.appendChild(alert);

    // Автоматически скрыть через 5 секунд
    setTimeout(() => {
        alert.classList.remove('show');
        setTimeout(() => alert.remove(), 150);
    }, 5000);
}

// Генерация яблок
document.getElementById('generate-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const count = document.getElementById('count-input').value;
    const button = document.getElementById('generate-btn');
    button.disabled = true;
    button.textContent = '⏳ Генерация...';

    fetch('<?= Url::to(['generate']) ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': csrfToken
        },
        body: `count=${count}&<?= Yii::$app->request->csrfParam ?>=${csrfToken}`
    })
    .then(response => {
        if (response.status === 429) {
            throw new Error('Превышен лимит запросов. Попробуйте позже.');
        }
        return response.text();
    })
    .then(() => {
        showNotification(`Сгенерировано ${count} яблок`, 'success');
        button.disabled = false;
        button.textContent = '🌳 Сгенерировать яблоки';
        // Перезагрузить страницу через 1 секунду
        setTimeout(() => location.reload(), 1000);
    })
    .catch(error => {
        showNotification(error.message || 'Ошибка при генерации яблок', 'danger');
        button.disabled = false;
        button.textContent = '🌳 Сгенерировать яблоки';
    });
});

// Падение яблока
document.querySelectorAll('.ajax-fall').forEach(button => {
    button.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        this.disabled = true;
        this.textContent = '⏳ Падает...';

        fetch('<?= Url::to(['fall']) ?>?id=' + id, {
            method: 'POST',
            headers: {
                'X-CSRF-Token': csrfToken
            },
            body: `<?= Yii::$app->request->csrfParam ?>=${csrfToken}`
        })
        .then(response => response.text())
        .then(() => {
            showNotification('Яблоко упало на землю', 'success');
            // Перезагрузить страницу через 0.5 секунд
            setTimeout(() => location.reload(), 500);
        })
        .catch(error => {
            showNotification('Ошибка при падении яблока', 'danger');
            this.disabled = false;
            this.textContent = '⬇️ Упасть';
        });
    });
});

// Съесть яблоко
document.querySelectorAll('.ajax-eat').forEach(button => {
    button.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        const percentInput = document.querySelector(`.eat-percent-input[data-id="${id}"]`);
        const percent = percentInput.value;

        this.disabled = true;
        this.textContent = '⏳ Кушаем...';

        fetch('<?= Url::to(['eat']) ?>?id=' + id, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': csrfToken
            },
            body: `percent=${percent}&<?= Yii::$app->request->csrfParam ?>=${csrfToken}`
        })
        .then(response => response.text())
        .then(() => {
            showNotification(`Откушено ${percent}% яблока`, 'success');
            // Перезагрузить страницу через 0.5 секунд
            setTimeout(() => location.reload(), 500);
        })
        .catch(error => {
            showNotification('Ошибка при поедании яблока', 'danger');
            this.disabled = false;
            this.textContent = '🍴 Съесть %';
        });
    });
});

// Удаление яблока
document.querySelectorAll('.ajax-delete').forEach(button => {
    button.addEventListener('click', function() {
        if (!confirm('Вы уверены, что хотите удалить это яблоко?')) {
            return;
        }

        const id = this.getAttribute('data-id');
        this.disabled = true;
        this.textContent = '⏳ Удаление...';

        fetch('<?= Url::to(['delete']) ?>?id=' + id, {
            method: 'POST',
            headers: {
                'X-CSRF-Token': csrfToken
            },
            body: `<?= Yii::$app->request->csrfParam ?>=${csrfToken}`
        })
        .then(response => response.text())
        .then(() => {
            showNotification('Яблоко удалено', 'success');
            // Скрыть карточку с анимацией
            const card = document.querySelector(`.apple-card[data-apple-id="${id}"]`);
            card.style.opacity = '0';
            card.style.transition = 'opacity 0.5s';
            setTimeout(() => card.remove(), 500);
        })
        .catch(error => {
            showNotification('Ошибка при удалении яблока', 'danger');
            this.disabled = false;
            this.textContent = '🗑️ Удалить';
        });
    });
});
</script>
