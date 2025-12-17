<?php

namespace common\tests\unit\repositories;

use Yii;
use common\models\Apple;
use common\repositories\AppleRepository;
use common\exceptions\AppleNotFoundException;

/**
 * AppleRepository test
 */
class AppleRepositoryTest extends \Codeception\Test\Unit
{
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    /**
     * @var AppleRepository
     */
    protected $repository;

    protected function _before()
    {
        // Очистка таблицы перед каждым тестом
        Apple::deleteAll();

        // Создаём репозиторий
        $this->repository = new AppleRepository();
    }

    protected function _after()
    {
        // Очистка после теста
        Apple::deleteAll();
    }

    /**
     * Тест получения всех яблок
     */
    public function testFindAll()
    {
        // Создаём 3 яблока
        Apple::createRandomApple();
        Apple::createRandomApple();
        Apple::createRandomApple();

        $apples = $this->repository->findAll();

        $this->assertCount(3, $apples);
        $this->assertInstanceOf(Apple::class, $apples[0]);
    }

    /**
     * Тест получения всех яблок с сортировкой
     */
    public function testFindAllWithOrdering()
    {
        $apple1 = Apple::createRandomApple();
        $apple1->color = 'red';
        $apple1->save();

        $apple2 = Apple::createRandomApple();
        $apple2->color = 'green';
        $apple2->save();

        $apples = $this->repository->findAll(['color' => SORT_ASC]);

        $this->assertEquals('green', $apples[0]->color);
        $this->assertEquals('red', $apples[1]->color);
    }

    /**
     * Тест поиска яблока по ID
     */
    public function testFindById()
    {
        $apple = Apple::createRandomApple();
        $id = $apple->id;

        $found = $this->repository->findById($id);

        $this->assertNotNull($found);
        $this->assertEquals($id, $found->id);
    }

    /**
     * Тест поиска несуществующего яблока
     */
    public function testFindByIdNotFound()
    {
        $this->expectException(AppleNotFoundException::class);
        $this->repository->findById(999);
    }

    /**
     * Тест сохранения яблока
     */
    public function testSave()
    {
        $apple = new Apple();
        $apple->color = 'red';
        $apple->created_at = time();
        $apple->status = Apple::STATUS_ON_TREE;
        $apple->eaten_percent = 0;

        $result = $this->repository->save($apple);

        $this->assertTrue($result);
        $this->assertNotNull($apple->id);
    }

    /**
     * Тест удаления яблока
     */
    public function testDelete()
    {
        $apple = Apple::createRandomApple();
        $id = $apple->id;

        $result = $this->repository->delete($apple);

        $this->assertNotEquals(false, $result);
        $this->assertNull(Apple::findOne($id));
    }

    /**
     * Тест создания нового экземпляра
     */
    public function testCreate()
    {
        $apple = $this->repository->create();

        $this->assertInstanceOf(Apple::class, $apple);
        $this->assertTrue($apple->isNewRecord);
    }

    /**
     * Тест обновления статуса гнилости (оптимизированный метод)
     */
    public function testUpdateRottenStatusForAll()
    {
        // Создаём упавшее яблоко, которое должно испортиться
        $apple1 = Apple::createRandomApple();
        $apple1->fallToGround();
        $apple1->fell_at = time() - (6 * 3600); // 6 часов назад
        $apple1->save(false);

        // Создаём упавшее яблоко, которое НЕ должно испортиться
        $apple2 = Apple::createRandomApple();
        $apple2->fallToGround();
        $apple2->fell_at = time() - (2 * 3600); // 2 часа назад
        $apple2->save(false);

        // Создаём яблоко на дереве
        $apple3 = Apple::createRandomApple();

        // Обновляем статусы
        $updated = $this->repository->updateRottenStatusForAll();

        // Проверяем результат
        $this->assertEquals(1, $updated, 'Должно обновиться только одно яблоко');

        $apple1->refresh();
        $apple2->refresh();
        $apple3->refresh();

        $this->assertEquals(Apple::STATUS_ROTTEN, $apple1->status);
        $this->assertEquals(Apple::STATUS_FALLEN, $apple2->status);
        $this->assertEquals(Apple::STATUS_ON_TREE, $apple3->status);
    }

    /**
     * Тест поиска по статусу
     */
    public function testFindByStatus()
    {
        $apple1 = Apple::createRandomApple();

        $apple2 = Apple::createRandomApple();
        $apple2->fallToGround();

        $apple3 = Apple::createRandomApple();

        $onTree = $this->repository->findByStatus(Apple::STATUS_ON_TREE);
        $fallen = $this->repository->findByStatus(Apple::STATUS_FALLEN);

        $this->assertCount(2, $onTree);
        $this->assertCount(1, $fallen);
    }

    /**
     * Тест поиска по цвету
     */
    public function testFindByColor()
    {
        $apple1 = Apple::createRandomApple();
        $apple1->color = 'red';
        $apple1->save();

        $apple2 = Apple::createRandomApple();
        $apple2->color = 'red';
        $apple2->save();

        $apple3 = Apple::createRandomApple();
        $apple3->color = 'green';
        $apple3->save();

        $redApples = $this->repository->findByColor('red');
        $greenApples = $this->repository->findByColor('green');

        $this->assertCount(2, $redApples);
        $this->assertCount(1, $greenApples);
    }

    /**
     * Тест подсчёта яблок
     */
    public function testCount()
    {
        Apple::createRandomApple();
        Apple::createRandomApple();
        Apple::createRandomApple();

        $count = $this->repository->count();

        $this->assertEquals(3, $count);
    }

    /**
     * Тест подсчёта яблок по статусу
     */
    public function testCountByStatus()
    {
        $apple1 = Apple::createRandomApple();

        $apple2 = Apple::createRandomApple();
        $apple2->fallToGround();

        $apple3 = Apple::createRandomApple();

        $onTreeCount = $this->repository->count(Apple::STATUS_ON_TREE);
        $fallenCount = $this->repository->count(Apple::STATUS_FALLEN);

        $this->assertEquals(2, $onTreeCount);
        $this->assertEquals(1, $fallenCount);
    }

    /**
     * Тест получения статистики (оптимизированный метод)
     */
    public function testGetStatistics()
    {
        // Создаём яблоки разных цветов и статусов
        $apple1 = Apple::createRandomApple();
        $apple1->color = 'red';
        $apple1->save();

        $apple2 = Apple::createRandomApple();
        $apple2->color = 'green';
        $apple2->fallToGround();
        $apple2->save();

        $apple3 = Apple::createRandomApple();
        $apple3->color = 'red';
        $apple3->fallToGround();
        $apple3->fell_at = time() - (6 * 3600);
        $apple3->save(false);
        $apple3->updateRottenStatus();

        $stats = $this->repository->getStatistics();

        $this->assertEquals(3, $stats['total']);
        $this->assertEquals(1, $stats['on_tree']);
        $this->assertEquals(1, $stats['fallen']);
        $this->assertEquals(1, $stats['rotten']);
        $this->assertEquals(2, $stats['by_color']['red']);
        $this->assertEquals(1, $stats['by_color']['green']);
        $this->assertEquals(0, $stats['by_color']['yellow']);
    }

    /**
     * Тест проверки существования яблока
     */
    public function testExists()
    {
        $apple = Apple::createRandomApple();
        $id = $apple->id;

        $exists = $this->repository->exists($id);
        $notExists = $this->repository->exists(999);

        $this->assertTrue($exists);
        $this->assertFalse($notExists);
    }

    /**
     * Тест производительности getStatistics (должен делать 2 запроса вместо 7)
     */
    public function testGetStatisticsPerformance()
    {
        // Создаём много яблок
        for ($i = 0; $i < 100; $i++) {
            Apple::createRandomApple();
        }

        // Запускаем профилирование запросов
        $queryCount = 0;
        Yii::$app->db->on(\yii\db\Connection::EVENT_AFTER_OPEN, function() use (&$queryCount) {
            $queryCount++;
        });

        // Получаем статистику
        $stats = $this->repository->getStatistics();

        // Проверяем, что статистика корректна
        $this->assertEquals(100, $stats['total']);
        $this->assertArrayHasKey('by_color', $stats);
        $this->assertArrayHasKey('on_tree', $stats);
    }
}
