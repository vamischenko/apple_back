<?php

namespace common\tests\unit\models;

use Yii;
use common\models\Apple;

/**
 * Apple model test
 */
class AppleTest extends \Codeception\Test\Unit
{
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    protected function _before()
    {
        // Очистка таблицы перед каждым тестом
        Apple::deleteAll();
    }

    protected function _after()
    {
        // Очистка после теста
        Apple::deleteAll();
    }

    /**
     * Тест создания яблока со случайными параметрами
     */
    public function testCreateRandomApple()
    {
        $apple = Apple::createRandomApple();

        $this->assertNotNull($apple);
        $this->assertContains($apple->color, ['red', 'green', 'yellow']);
        $this->assertEquals('on_tree', $apple->status);
        $this->assertNotNull($apple->created_at);
        $this->assertNull($apple->fell_at);
        $this->assertEquals(0, $apple->eaten_percent);
    }

    /**
     * Тест размера нового яблока
     */
    public function testNewAppleSize()
    {
        $apple = Apple::createRandomApple();
        $this->assertEquals(1.0, $apple->getSize());
    }

    /**
     * Тест падения яблока с дерева
     */
    public function testFallToGround()
    {
        $apple = Apple::createRandomApple();
        $apple->fallToGround();
        $apple->refresh();

        $this->assertEquals('fallen', $apple->status);
        $this->assertNotNull($apple->fell_at);
    }

    /**
     * Тест: нельзя съесть яблоко на дереве
     */
    public function testCannotEatAppleOnTree()
    {
        $apple = Apple::createRandomApple();

        $this->expectException(\yii\base\Exception::class);
        $this->expectExceptionMessage('Съесть нельзя, яблоко на дереве');

        $apple->eat(50);
    }

    /**
     * Тест: можно съесть упавшее яблоко
     */
    public function testCanEatFallenApple()
    {
        $apple = Apple::createRandomApple();
        $apple->fallToGround();
        $apple->eat(25);
        $apple->refresh();

        $this->assertEquals(25, $apple->eaten_percent);
        $this->assertEquals(0.75, $apple->getSize());
    }

    /**
     * Тест уменьшения размера после поедания
     */
    public function testSizeDecreasesAfterEating()
    {
        $apple = Apple::createRandomApple();
        $apple->fallToGround();
        $apple->eat(50);
        $apple->refresh();

        $this->assertEquals(0.5, $apple->getSize());
    }

    /**
     * Тест: полное поедание удаляет яблоко
     */
    public function testFullEatingDeletesApple()
    {
        $apple = Apple::createRandomApple();
        $id = $apple->id;
        $apple->fallToGround();
        $apple->eat(100);

        $deleted = Apple::findOne($id);
        $this->assertNull($deleted);
    }

    /**
     * Тест: яблоко гниет через 5 часов
     */
    public function testAppleRotsAfter5Hours()
    {
        $apple = Apple::createRandomApple();
        $apple->fallToGround();
        $apple->fell_at = time() - (6 * 3600); // 6 часов назад
        $apple->save(false);
        $apple->updateRottenStatus();
        $apple->refresh();

        $this->assertEquals('rotten', $apple->status);
    }

    /**
     * Тест: нельзя съесть гнилое яблоко
     */
    public function testCannotEatRottenApple()
    {
        $apple = Apple::createRandomApple();
        $apple->fallToGround();
        $apple->fell_at = time() - (6 * 3600);
        $apple->save(false);
        $apple->updateRottenStatus();

        $this->expectException(\yii\base\Exception::class);
        $this->expectExceptionMessage('Съесть нельзя, яблоко испорчено');

        $apple->eat(10);
    }

    /**
     * Тест: яблоко на дереве не гниет
     */
    public function testAppleOnTreeDoesNotRot()
    {
        $apple = Apple::createRandomApple();
        $apple->created_at = time() - (10 * 3600); // 10 часов назад
        $apple->save(false);
        $apple->updateRottenStatus();
        $apple->refresh();

        $this->assertEquals('on_tree', $apple->status);
    }

    /**
     * Тест: нельзя съесть отрицательный процент
     */
    public function testCannotEatNegativePercent()
    {
        $apple = Apple::createRandomApple();
        $apple->fallToGround();

        $this->expectException(\yii\base\Exception::class);
        $this->expectExceptionMessage('Процент должен быть от 0 до 100');

        $apple->eat(-10);
    }

    /**
     * Тест: нельзя съесть больше 100%
     */
    public function testCannotEatMoreThan100Percent()
    {
        $apple = Apple::createRandomApple();
        $apple->fallToGround();

        $this->expectException(\yii\base\Exception::class);
        $this->expectExceptionMessage('Процент должен быть от 0 до 100');

        $apple->eat(150);
    }

    /**
     * Тест: нельзя съесть больше, чем осталось
     */
    public function testCannotEatMoreThanRemaining()
    {
        $apple = Apple::createRandomApple();
        $apple->fallToGround();
        $apple->eat(60);

        $this->expectException(\yii\base\Exception::class);
        $this->expectExceptionMessage('Нельзя съесть больше, чем осталось');

        $apple->eat(50); // Осталось только 40%
    }

    /**
     * Тест постепенного поедания
     */
    public function testGradualEating()
    {
        $apple = Apple::createRandomApple();
        $apple->fallToGround();

        $apple->eat(20);
        $apple->refresh();
        $this->assertEquals(20, $apple->eaten_percent);

        $apple->eat(30);
        $apple->refresh();
        $this->assertEquals(50, $apple->eaten_percent);

        $apple->eat(25);
        $apple->refresh();
        $this->assertEquals(75, $apple->eaten_percent);
    }

    /**
     * Тест меток статуса
     */
    public function testStatusLabels()
    {
        $apple = Apple::createRandomApple();
        $this->assertEquals('На дереве', $apple->getStatusLabel());

        $apple->fallToGround();
        $apple->refresh();
        $this->assertEquals('Упало', $apple->getStatusLabel());

        $apple->fell_at = time() - (6 * 3600);
        $apple->save(false);
        $apple->updateRottenStatus();
        $apple->refresh();
        $this->assertEquals('Гнилое', $apple->getStatusLabel());
    }

    /**
     * Тест примера из задания
     */
    public function testExampleFromTask()
    {
        $apple = Apple::createRandomApple();
        $apple->color = 'green';
        $apple->save();

        $this->assertEquals('green', $apple->color);

        // Попытка съесть яблоко на дереве должна выбросить исключение
        try {
            $apple->eat(50);
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            // Ожидаемое поведение
        }

        $this->assertEquals(1.0, $apple->getSize());

        $apple->fallToGround();
        $apple->eat(25);
        $apple->refresh();

        $this->assertEqualsWithDelta(0.75, $apple->getSize(), 0.01);
    }
}
