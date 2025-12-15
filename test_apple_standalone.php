<?php
/**
 * Автоматическое тестирование класса Apple
 */

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/common/config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/common/config/main.php',
    require __DIR__ . '/common/config/main-local.php'
);

new yii\console\Application($config);

use common\models\Apple;

$passed = 0;
$failed = 0;

function test($name, $callback) {
    global $passed, $failed;

    try {
        $result = $callback();
        if ($result === true) {
            echo "✅ $name\n";
            $passed++;
        } else {
            echo "❌ $name - Unexpected result\n";
            $failed++;
        }
    } catch (\Exception $e) {
        echo "❌ $name - Exception: " . $e->getMessage() . "\n";
        $failed++;
    }
}

function expectException($name, $callback, $expectedMessage = null) {
    global $passed, $failed;

    try {
        $callback();
        echo "❌ $name - Expected exception was not thrown\n";
        $failed++;
    } catch (\Exception $e) {
        if ($expectedMessage && strpos($e->getMessage(), $expectedMessage) === false) {
            echo "❌ $name - Wrong exception message: " . $e->getMessage() . "\n";
            $failed++;
        } else {
            echo "✅ $name\n";
            $passed++;
        }
    }
}

echo "========================================\n";
echo "Автоматическое тестирование класса Apple\n";
echo "========================================\n\n";

// Тест 1: Создание яблока
test("Создание яблока со случайными параметрами", function() {
    $apple = Apple::createRandomApple();
    return $apple !== null && in_array($apple->color, ['red', 'green', 'yellow']) && $apple->status === 'on_tree';
});

// Тест 2: Размер нового яблока
test("Размер нового яблока = 1", function() {
    $apple = Apple::createRandomApple();
    return $apple->getSize() === 1.0;
});

// Тест 3: Падение яблока
test("Яблоко падает с дерева", function() {
    $apple = Apple::createRandomApple();
    $apple->fallToGround();
    $apple->refresh();
    return $apple->status === 'fallen' && $apple->fell_at !== null;
});

// Тест 4: Нельзя съесть яблоко на дереве
expectException(
    "Нельзя съесть яблоко на дереве",
    function() {
        $apple = Apple::createRandomApple();
        $apple->eat(50);
    },
    "Съесть нельзя, яблоко на дереве"
);

// Тест 5: Можно съесть упавшее яблоко
test("Можно съесть упавшее яблоко", function() {
    $apple = Apple::createRandomApple();
    $apple->fallToGround();
    $apple->eat(25);
    $apple->refresh();
    return $apple->eaten_percent == 25;
});

// Тест 6: Размер после поедания
test("Размер уменьшается после поедания", function() {
    $apple = Apple::createRandomApple();
    $apple->fallToGround();
    $apple->eat(50);
    $apple->refresh();
    return $apple->getSize() === 0.5;
});

// Тест 7: Полное поедание удаляет яблоко
test("Полное поедание удаляет яблоко", function() {
    $apple = Apple::createRandomApple();
    $id = $apple->id;
    $apple->fallToGround();
    $apple->eat(100);

    // Проверяем, что яблоко удалено
    $deleted = Apple::findOne($id);
    return $deleted === null;
});

// Тест 8: Яблоко гниет через 5 часов
test("Яблоко гниет через 5 часов", function() {
    $apple = Apple::createRandomApple();
    $apple->fallToGround();
    $apple->fell_at = time() - (6 * 3600); // 6 часов назад
    $apple->save(false);
    $apple->updateRottenStatus();
    $apple->refresh();
    return $apple->status === 'rotten';
});

// Тест 9: Нельзя съесть гнилое яблоко
expectException(
    "Нельзя съесть гнилое яблоко",
    function() {
        $apple = Apple::createRandomApple();
        $apple->fallToGround();
        $apple->fell_at = time() - (6 * 3600);
        $apple->save(false);
        $apple->updateRottenStatus();
        $apple->eat(10);
    },
    "Съесть нельзя, яблоко испорчено"
);

// Тест 10: Яблоко на дереве не гниет
test("Яблоко на дереве не гниет", function() {
    $apple = Apple::createRandomApple();
    $apple->created_at = time() - (10 * 3600); // 10 часов назад
    $apple->save(false);
    $apple->updateRottenStatus();
    $apple->refresh();
    return $apple->status === 'on_tree';
});

// Тест 11: Нельзя съесть отрицательный процент
expectException(
    "Нельзя съесть отрицательный процент",
    function() {
        $apple = Apple::createRandomApple();
        $apple->fallToGround();
        $apple->eat(-10);
    },
    "Процент должен быть от 0 до 100"
);

// Тест 12: Нельзя съесть больше 100%
expectException(
    "Нельзя съесть больше 100%",
    function() {
        $apple = Apple::createRandomApple();
        $apple->fallToGround();
        $apple->eat(150);
    },
    "Процент должен быть от 0 до 100"
);

// Тест 13: Нельзя съесть больше, чем осталось
expectException(
    "Нельзя съесть больше, чем осталось",
    function() {
        $apple = Apple::createRandomApple();
        $apple->fallToGround();
        $apple->eat(60);
        $apple->eat(50); // Осталось только 40%
    },
    "Нельзя съесть больше, чем осталось"
);

// Тест 14: Постепенное поедание
test("Постепенное поедание работает корректно", function() {
    $apple = Apple::createRandomApple();
    $apple->fallToGround();

    $apple->eat(20);
    $apple->refresh();
    if ($apple->eaten_percent != 20) return false;

    $apple->eat(30);
    $apple->refresh();
    if ($apple->eaten_percent != 50) return false;

    $apple->eat(25);
    $apple->refresh();
    if ($apple->eaten_percent != 75) return false;

    return true;
});

// Тест 15: Метка статуса
test("Метка статуса возвращает правильное значение", function() {
    $apple = Apple::createRandomApple();

    if ($apple->getStatusLabel() !== 'На дереве') return false;

    $apple->fallToGround();
    $apple->refresh();
    if ($apple->getStatusLabel() !== 'Упало') return false;

    $apple->fell_at = time() - (6 * 3600);
    $apple->save(false);
    $apple->updateRottenStatus();
    $apple->refresh();
    if ($apple->getStatusLabel() !== 'Гнилое') return false;

    return true;
});

// Тест 16: Пример из задания
test("Пример из задания работает", function() {
    $apple = Apple::createRandomApple();
    $apple->color = 'green';
    $apple->save();

    if ($apple->color !== 'green') return false;

    try {
        $apple->eat(50);
        return false; // Должно выбросить исключение
    } catch (\Exception $e) {
        // Ожидаемое поведение
    }

    if ($apple->getSize() !== 1.0) return false;

    $apple->fallToGround();
    $apple->eat(25);
    $apple->refresh();

    return abs($apple->getSize() - 0.75) < 0.01; // Проверка с погрешностью
});

// Итоги
echo "\n========================================\n";
echo "Результаты тестирования\n";
echo "========================================\n";
echo "✅ Пройдено: $passed\n";
echo "❌ Провалено: $failed\n";
echo "========================================\n";

if ($failed === 0) {
    echo "\n🎉 Все тесты пройдены успешно!\n\n";
    exit(0);
} else {
    echo "\n⚠️  Некоторые тесты не прошли.\n\n";
    exit(1);
}
