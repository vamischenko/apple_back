# Логирование и мониторинг в проекте Яблоки

## Содержание
- [Логирование](#логирование)
- [Метрики и статистика](#метрики-и-статистика)
- [API endpoints для метрик](#api-endpoints-для-метрик)
- [Примеры использования](#примеры-использования)

## Логирование

### Категория логов
Все логи приложения записываются в категорию `'apple'`.

### Уровни логирования

#### INFO - Информационные сообщения
Логирование успешных операций:

```php
// Создание яблока
Yii::info("Created new apple #{$apple->id} (color: {$apple->color})", 'apple');

// Падение яблока
Yii::info("Apple #{$this->id} (color: {$this->color}) fallen to ground", 'apple');

// Съедение части яблока
Yii::info("Eaten {$percent}% of apple #{$this->id} (color: {$this->color}), total eaten: {$this->eaten_percent}%", 'apple');

// Полное съедение
Yii::info("Apple #{$this->id} completely eaten and deleted", 'apple');

// Порча яблока
Yii::info("Apple #{$this->id} (color: {$this->color}) became rotten after {$timeSinceFall} seconds", 'apple');

// Удаление яблока
Yii::info("Deleting apple #{$id} (color: {$apple->color}, status: {$apple->status})", 'apple');

// Генерация яблок
Yii::info("Starting generation of {$count} random apples", 'apple');
Yii::info("Successfully generated {$generated} out of {$count} requested apples", 'apple');

// Генерация отчета
Yii::info('Generating full metrics report', 'apple');
```

#### WARNING - Предупреждения
Логирование попыток некорректных действий:

```php
// Попытка уронить яблоко не с дерева
Yii::warning("Attempt to drop apple #{$this->id} that is not on tree (status: {$this->status})", 'apple');

// Попытка съесть яблоко на дереве
Yii::warning("Attempt to eat apple #{$this->id} on tree", 'apple');

// Попытка съесть гнилое яблоко
Yii::warning("Attempt to eat rotten apple #{$this->id}", 'apple');

// Некорректный процент
Yii::warning("Invalid percent {$percent} for eating apple #{$this->id}", 'apple');

// Превышение остатка
Yii::warning("Attempt to eat {$percent}% of apple #{$this->id}, but only {$remaining}% remaining", 'apple');

// Некорректное количество для генерации
Yii::warning("Attempt to generate invalid count of apples: {$count}", 'apple');
```

#### ERROR - Ошибки
Логирование критических ошибок:

```php
// Ошибка сохранения после падения
Yii::error("Failed to save apple #{$this->id} after falling", 'apple');

// Ошибка сохранения после съедения
Yii::error("Failed to save apple #{$this->id} after eating", 'apple');

// Ошибка создания яблока
Yii::error("Failed to create random apple", 'apple');
```

### Настройка логирования

В конфигурации приложения (`common/config/main-local.php` или соответствующем файле) настройте компонент `log`:

```php
'components' => [
    'log' => [
        'traceLevel' => YII_DEBUG ? 3 : 0,
        'targets' => [
            // Файл для логов категории 'apple'
            [
                'class' => 'yii\log\FileTarget',
                'levels' => ['info', 'warning', 'error'],
                'categories' => ['apple'],
                'logFile' => '@runtime/logs/apple.log',
                'maxFileSize' => 1024 * 2, // 2MB
                'maxLogFiles' => 5,
                'logVars' => [],
            ],
            // Отдельный файл для ошибок
            [
                'class' => 'yii\log\FileTarget',
                'levels' => ['error'],
                'categories' => ['apple'],
                'logFile' => '@runtime/logs/apple-errors.log',
                'maxFileSize' => 1024 * 2,
                'maxLogFiles' => 5,
                'logVars' => [],
            ],
        ],
    ],
],
```

### Просмотр логов

```bash
# Все логи яблок
tail -f runtime/logs/apple.log

# Только ошибки
tail -f runtime/logs/apple-errors.log

# Поиск по логам
grep "fallen to ground" runtime/logs/apple.log
```

## Метрики и статистика

### AppleMetricsService

Сервис `AppleMetricsService` предоставляет детальную статистику и метрики по яблокам.

#### Расширенная статистика

```php
$metricsService = new AppleMetricsService($repository);
$stats = $metricsService->getExtendedStatistics();
```

Возвращает:
- `total_count` - общее количество яблок
- `status_distribution` - распределение по статусам
- `color_distribution` - распределение по цветам
- `percentage_distribution` - процентное распределение по статусам
- `eating_metrics` - метрики по съеденным яблокам
- `age_metrics` - метрики по возрасту яблок

#### Метрики создания

```php
$metrics = $metricsService->getCreationMetrics();
```

Возвращает:
- `total_created` - всего создано яблок
- `created_today` - создано сегодня
- `created_this_week` - создано на этой неделе
- `created_this_month` - создано в этом месяце

#### Метрики порчи

```php
$metrics = $metricsService->getRottenMetrics();
```

Возвращает:
- `total_rotten` - всего испорченных яблок
- `total_fallen` - всего упавших яблок
- `soon_to_rot` - скоро испортятся (< 1 часа)
- `rotten_rate` - процент испорченных среди упавших

#### Метрики съедения

```php
$metrics = $metricsService->getEatingMetrics();
```

Возвращает:
- `partially_eaten_count` - частично съеденных
- `untouched_count` - нетронутых
- `average_eaten_percent` - средний процент съеденного
- `total_eaten_percent` - общий процент съеденного

#### Метрики возраста

```php
$metrics = $metricsService->getAgeMetrics();
```

Возвращает:
- `average_age_seconds` - средний возраст в секундах
- `average_age_days` - средний возраст в днях
- `oldest_age_seconds` - возраст старейшего в секундах
- `oldest_age_days` - возраст старейшего в днях
- `newest_age_seconds` - возраст новейшего в секундах
- `newest_age_days` - возраст новейшего в днях

#### Полный отчет

```php
$report = $metricsService->getFullReport();
```

Возвращает все метрики в одном массиве.

## API endpoints для метрик

### GET /api/apples/metrics

Получить метрики и статистику.

#### Параметры запроса

- `type` - тип метрик (опционально, по умолчанию `full`)
  - `full` - полный отчет
  - `extended` - расширенная статистика
  - `creation` - метрики создания
  - `rotten` - метрики порчи
  - `eating` - метрики съедения

#### Примеры запросов

```bash
# Полный отчет
curl http://localhost/api/apples/metrics

# Расширенная статистика
curl http://localhost/api/apples/metrics?type=extended

# Метрики создания
curl http://localhost/api/apples/metrics?type=creation

# Метрики порчи
curl http://localhost/api/apples/metrics?type=rotten

# Метрики съедения
curl http://localhost/api/apples/metrics?type=eating
```

#### Пример ответа (полный отчет)

```json
{
  "statistics": {
    "total_count": 100,
    "status_distribution": {
      "on_tree": 50,
      "fallen": 30,
      "rotten": 20
    },
    "color_distribution": {
      "red": 35,
      "green": 33,
      "yellow": 32
    },
    "percentage_distribution": {
      "on_tree_percent": 50.0,
      "fallen_percent": 30.0,
      "rotten_percent": 20.0
    },
    "eating_metrics": {
      "partially_eaten_count": 25,
      "untouched_count": 75,
      "average_eaten_percent": 12.5,
      "total_eaten_percent": 312.5
    },
    "age_metrics": {
      "average_age_seconds": 86400,
      "average_age_days": 1.0,
      "oldest_age_seconds": 259200,
      "oldest_age_days": 3.0,
      "newest_age_seconds": 3600,
      "newest_age_days": 0.04
    },
    "timestamp": 1234567890
  },
  "creation_metrics": {
    "total_created": 100,
    "created_today": 10,
    "created_this_week": 45,
    "created_this_month": 100
  },
  "rotten_metrics": {
    "total_rotten": 20,
    "total_fallen": 30,
    "soon_to_rot": 5,
    "rotten_rate": 40.0
  },
  "generated_at": "2025-12-17 12:00:00"
}
```

## Примеры использования

### Мониторинг в реальном времени

```bash
# Следить за логами и автоматически обновлять метрики каждые 5 секунд
watch -n 5 'curl -s http://localhost/api/apples/metrics?type=extended | jq .'
```

### Анализ трендов

```php
// Получение метрик и сохранение в БД для анализа трендов
$metricsService = new AppleMetricsService($repository);
$metrics = $metricsService->getFullReport();

// Сохранение в таблицу metrics_history
Yii::$app->db->createCommand()->insert('metrics_history', [
    'data' => json_encode($metrics),
    'created_at' => time(),
])->execute();
```

### Алерты

```php
// Проверка критических метрик и отправка уведомлений
$rottenMetrics = $metricsService->getRottenMetrics();

if ($rottenMetrics['soon_to_rot'] > 10) {
    // Отправить уведомление
    Yii::warning("Critical: {$rottenMetrics['soon_to_rot']} apples will rot soon!", 'apple');
    // Можно отправить email или push-уведомление
}

if ($rottenMetrics['rotten_rate'] > 50) {
    // Более 50% упавших яблок испортилось
    Yii::warning("High rotten rate: {$rottenMetrics['rotten_rate']}%", 'apple');
}
```

### Интеграция с системами мониторинга

```php
// Экспорт метрик в формате для Prometheus
public function actionPrometheusMetrics()
{
    $metrics = $this->metricsService->getExtendedStatistics();

    header('Content-Type: text/plain');

    echo "# HELP apples_total Total number of apples\n";
    echo "# TYPE apples_total gauge\n";
    echo "apples_total {$metrics['total_count']}\n\n";

    echo "# HELP apples_rotten Number of rotten apples\n";
    echo "# TYPE apples_rotten gauge\n";
    echo "apples_rotten {$metrics['status_distribution']['rotten']}\n\n";

    // ... другие метрики
}
```

## Рекомендации

1. **Ротация логов**: Настройте автоматическую ротацию логов для предотвращения переполнения диска
2. **Мониторинг**: Регулярно проверяйте метрики порчи и съедения
3. **Алерты**: Настройте уведомления при критических значениях метрик
4. **Архивация**: Сохраняйте исторические данные метрик для анализа трендов
5. **Производительность**: Используйте кеширование для метрик при высокой нагрузке
