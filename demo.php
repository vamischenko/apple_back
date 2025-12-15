<?php
/**
 * Демонстрация работы класса Apple
 *
 * Этот файл демонстрирует все возможности класса Apple
 * в соответствии с требованиями задания
 */

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/common/config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/common/config/main.php',
    require __DIR__ . '/common/config/main-local.php'
);

new yii\console\Application($config);

use common\models\Apple;

echo "========================================\n";
echo "Демонстрация класса Apple\n";
echo "========================================\n\n";

// Пример из задания
echo "=== Пример из задания ===\n\n";

echo "// Создание яблока\n";
echo "\$apple = Apple::createRandomApple();\n";
$apple = Apple::createRandomApple();

// Устанавливаем зеленый цвет для примера
$apple->color = 'green';
$apple->save();

echo "\necho \$apple->color; // " . $apple->color . "\n";

echo "\n// Попытка съесть яблоко на дереве\n";
echo "\$apple->eat(50);\n";
try {
    $apple->eat(50);
} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\necho \$apple->getSize(); // " . $apple->getSize() . "\n";

echo "\n// Яблоко падает на землю\n";
echo "\$apple->fallToGround();\n";
$apple->fallToGround();
echo "✅ Яблоко упало на землю\n";

echo "\n// Откусить четверть яблока\n";
echo "\$apple->eat(25);\n";
$apple->eat(25);
echo "✅ Откушено 25% яблока\n";

echo "\necho \$apple->getSize(); // " . $apple->getSize() . "\n";

echo "\n========================================\n";
echo "Дополнительные примеры\n";
echo "========================================\n\n";

// Создание нескольких яблок
echo "=== Создание яблок разных цветов ===\n\n";

$apples = [];
foreach (['red', 'green', 'yellow'] as $color) {
    $newApple = Apple::createRandomApple();
    $newApple->color = $color;
    $newApple->save();
    $apples[] = $newApple;

    $emoji = $color === 'green' ? '🍏' : ($color === 'red' ? '🍎' : '🍋');
    echo "$emoji Создано {$color} яблоко (ID: {$newApple->id})\n";
}

echo "\n=== Тест состояний ===\n\n";

// Яблоко на дереве
$treeApple = Apple::createRandomApple();
echo "1️⃣ Яблоко на дереве (ID: {$treeApple->id})\n";
echo "   Статус: {$treeApple->getStatusLabel()}\n";
echo "   Размер: {$treeApple->getSize()}\n";

// Упавшее яблоко
$fallenApple = Apple::createRandomApple();
$fallenApple->fallToGround();
echo "\n2️⃣ Упавшее яблоко (ID: {$fallenApple->id})\n";
echo "   Статус: {$fallenApple->getStatusLabel()}\n";
echo "   Размер: {$fallenApple->getSize()}\n";

// Гнилое яблоко (симулируем старое падение)
$rottenApple = Apple::createRandomApple();
$rottenApple->fallToGround();
$rottenApple->fell_at = time() - (6 * 3600); // 6 часов назад
$rottenApple->save(false);
$rottenApple->updateRottenStatus();
$rottenApple->refresh();
echo "\n3️⃣ Гнилое яблоко (ID: {$rottenApple->id})\n";
echo "   Статус: {$rottenApple->getStatusLabel()}\n";
echo "   Размер: {$rottenApple->getSize()}\n";

// Попытка съесть гнилое яблоко
echo "\n// Попытка съесть гнилое яблоко\n";
echo "\$rottenApple->eat(10);\n";
try {
    $rottenApple->eat(10);
} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n=== Постепенное поедание яблока ===\n\n";

$eatApple = Apple::createRandomApple();
$eatApple->fallToGround();
echo "Яблоко упало. Начинаем есть...\n\n";

$bites = [20, 30, 25, 25]; // 100% в сумме
foreach ($bites as $i => $percent) {
    $bite_num = $i + 1;
    echo "Откус #{$bite_num}: {$percent}%\n";

    try {
        $eatApple->eat($percent);
        $eatApple->refresh();

        if ($eatApple->eaten_percent < 100) {
            echo "   ✅ Съедено всего: {$eatApple->eaten_percent}%, осталось размер: {$eatApple->getSize()}\n";
        } else {
            echo "   ✅ Яблоко съедено полностью и удалено из БД\n";
        }
    } catch (\Exception $e) {
        echo "   ❌ Ошибка: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Проверка валидации ===\n\n";

$validApple = Apple::createRandomApple();
$validApple->fallToGround();

echo "1. Попытка съесть отрицательный процент:\n";
try {
    $validApple->eat(-10);
} catch (\Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n2. Попытка съесть больше 100%:\n";
try {
    $validApple->eat(150);
} catch (\Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n3. Попытка съесть больше, чем осталось:\n";
$validApple->eat(60);
echo "   ✅ Съедено 60%\n";
try {
    $validApple->eat(50); // Осталось только 40%
} catch (\Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n========================================\n";
echo "Демонстрация завершена!\n";
echo "========================================\n\n";

echo "Всего яблок в БД: " . Apple::find()->count() . "\n";
echo "\nДля просмотра всех яблок запустите веб-приложение:\n";
echo "  php yii serve --docroot=@backend/web --port=8080\n";
echo "  http://localhost:8080\n\n";
