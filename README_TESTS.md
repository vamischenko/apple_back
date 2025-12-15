# Тестирование проекта Apple Management System

## Виды тестов

### 1. Codeception Unit Tests (рекомендуется)

Профессиональные unit-тесты с использованием Codeception Framework.

**Расположение:** [common/tests/unit/models/AppleTest.php](common/tests/unit/models/AppleTest.php)

**Количество тестов:** 16

**Запуск всех тестов:**
```bash
php vendor/bin/codecept run common
```

**Запуск только тестов Apple:**
```bash
php vendor/bin/codecept run common/tests/unit/models/AppleTest.php
```

**Запуск с подробным выводом:**
```bash
php vendor/bin/codecept run common/tests/unit/models/AppleTest.php --verbose
```

### 2. Standalone Test Script (для быстрой проверки)

Простой скрипт для быстрого тестирования без дополнительных зависимостей.

**Расположение:** [test_apple_standalone.php](test_apple_standalone.php)

**Запуск:**
```bash
php test_apple_standalone.php
```

## Список тестов

### Тесты создания и состояний
1. ✅ Создание яблока со случайными параметрами
2. ✅ Размер нового яблока = 1
3. ✅ Яблоко падает с дерева
4. ✅ Яблоко на дереве не гниет

### Тесты поедания
5. ✅ Нельзя съесть яблоко на дереве (Exception)
6. ✅ Можно съесть упавшее яблоко
7. ✅ Размер уменьшается после поедания
8. ✅ Полное поедание удаляет яблоко
9. ✅ Постепенное поедание работает корректно

### Тесты гниения
10. ✅ Яблоко гниет через 5 часов
11. ✅ Нельзя съесть гнилое яблоко (Exception)

### Тесты валидации
12. ✅ Нельзя съесть отрицательный процент (Exception)
13. ✅ Нельзя съесть больше 100% (Exception)
14. ✅ Нельзя съесть больше, чем осталось (Exception)

### Дополнительные тесты
15. ✅ Метки статуса возвращают правильные значения
16. ✅ Пример из задания работает

## Требования для тестов

### Для Codeception:
- PHP 7.4+
- MySQL запущен и доступен
- База данных создана (`apple_db`)
- Миграции применены (`php yii migrate`)

### Для Standalone:
- PHP 7.4+
- MySQL запущен
- База данных создана

## Настройка тестовой БД

Тестовая конфигурация находится в [common/config/test-local.php](common/config/test-local.php)

По умолчанию используется та же БД что и для разработки:
```php
'db' => [
    'dsn' => 'mysql:host=localhost;dbname=apple_db',
],
```

## Структура Codeception тестов

```
common/tests/
├── unit/
│   ├── models/
│   │   ├── AppleTest.php       ← Тесты модели Apple
│   │   └── LoginFormTest.php   ← Тесты формы входа
│   └── unit.suite.yml
├── _bootstrap.php
├── _data/
├── _output/
└── _support/
```

## Примеры использования

### Запуск всех unit-тестов:
```bash
php vendor/bin/codecept run unit
```

### Запуск с отчетом покрытия:
```bash
php vendor/bin/codecept run unit --coverage
```

### Запуск определенного теста:
```bash
php vendor/bin/codecept run unit common/tests/unit/models/AppleTest.php:testCreateRandomApple
```

## Интеграция с CI/CD

Codeception тесты можно легко интегрировать в CI/CD pipeline:

```yaml
# .github/workflows/tests.yml
test:
  runs-on: ubuntu-latest
  steps:
    - uses: actions/checkout@v2
    - name: Run tests
      run: php vendor/bin/codecept run
```

## Отладка тестов

Для вывода дополнительной информации используйте:
```bash
php vendor/bin/codecept run unit --debug
```

Для вывода trace при ошибках:
```bash
php vendor/bin/codecept run unit --verbose --debug
```
