<?php

namespace common\tests\unit\services;

use Yii;
use common\models\Apple;
use common\services\AppleService;
use common\repositories\AppleRepository;
use common\exceptions\AppleNotFoundException;
use common\exceptions\AppleValidationException;

/**
 * AppleService test
 */
class AppleServiceTest extends \Codeception\Test\Unit
{
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    /**
     * @var AppleService
     */
    protected $service;

    protected function _before()
    {
        // Очистка таблицы перед каждым тестом
        Apple::deleteAll();

        // Очистка кеша
        Yii::$app->cache->flush();

        // Создаём сервис
        $repository = new AppleRepository();
        $this->service = new AppleService($repository);
    }

    protected function _after()
    {
        // Очистка после теста
        Apple::deleteAll();
        Yii::$app->cache->flush();
    }

    /**
     * Тест получения всех яблок
     */
    public function testGetAllApples()
    {
        // Создаём 3 яблока
        Apple::createRandomApple();
        Apple::createRandomApple();
        Apple::createRandomApple();

        $apples = $this->service->getAllApples();

        $this->assertCount(3, $apples);
        $this->assertInstanceOf(Apple::class, $apples[0]);
    }

    /**
     * Тест кеширования при получении всех яблок
     */
    public function testGetAllApplesUsesCaching()
    {
        // Создаём яблоко
        Apple::createRandomApple();

        // Первый запрос - данные из БД
        $apples1 = $this->service->getAllApples();
        $this->assertCount(1, $apples1);

        // Создаём ещё одно яблоко напрямую в БД
        Apple::createRandomApple();

        // Второй запрос - данные из кеша (старые)
        $apples2 = $this->service->getAllApples();
        $this->assertCount(1, $apples2, 'Должны быть закешированные данные');

        // Очищаем кеш
        Yii::$app->cache->flush();

        // Третий запрос - данные из БД (новые)
        $apples3 = $this->service->getAllApples();
        $this->assertCount(2, $apples3, 'После очистки кеша должны быть актуальные данные');
    }

    /**
     * Тест генерации случайных яблок
     */
    public function testGenerateRandomApples()
    {
        $generated = $this->service->generateRandomApples(5);

        $this->assertEquals(5, $generated);
        $this->assertCount(5, Apple::find()->all());
    }

    /**
     * Тест генерации с неверным количеством
     */
    public function testGenerateRandomApplesInvalidCount()
    {
        $this->expectException(AppleValidationException::class);
        $this->service->generateRandomApples(100); // Больше 50
    }

    /**
     * Тест генерации с отрицательным количеством
     */
    public function testGenerateRandomApplesNegativeCount()
    {
        $this->expectException(AppleValidationException::class);
        $this->service->generateRandomApples(-5);
    }

    /**
     * Тест падения яблока
     */
    public function testFallApple()
    {
        $apple = Apple::createRandomApple();
        $id = $apple->id;

        $this->service->fallApple($id);

        $apple->refresh();
        $this->assertEquals(Apple::STATUS_FALLEN, $apple->status);
        $this->assertNotNull($apple->fell_at);
    }

    /**
     * Тест падения несуществующего яблока
     */
    public function testFallAppleNotFound()
    {
        $this->expectException(AppleNotFoundException::class);
        $this->service->fallApple(999);
    }

    /**
     * Тест съедения яблока
     */
    public function testEatApple()
    {
        $apple = Apple::createRandomApple();
        $apple->fallToGround();
        $id = $apple->id;

        $this->service->eatApple($id, 30);

        $apple->refresh();
        $this->assertEquals(30, $apple->eaten_percent);
    }

    /**
     * Тест съедения яблока на 100%
     */
    public function testEatAppleCompletely()
    {
        $apple = Apple::createRandomApple();
        $apple->fallToGround();
        $id = $apple->id;

        $this->service->eatApple($id, 100);

        // Яблоко должно быть удалено
        $deleted = Apple::findOne($id);
        $this->assertNull($deleted);
    }

    /**
     * Тест съедения несуществующего яблока
     */
    public function testEatAppleNotFound()
    {
        $this->expectException(AppleNotFoundException::class);
        $this->service->eatApple(999, 50);
    }

    /**
     * Тест удаления яблока
     */
    public function testDeleteApple()
    {
        $apple = Apple::createRandomApple();
        $id = $apple->id;

        $this->service->deleteApple($id);

        $deleted = Apple::findOne($id);
        $this->assertNull($deleted);
    }

    /**
     * Тест удаления несуществующего яблока
     */
    public function testDeleteAppleNotFound()
    {
        $this->expectException(AppleNotFoundException::class);
        $this->service->deleteApple(999);
    }

    /**
     * Тест поиска яблока по ID
     */
    public function testFindApple()
    {
        $apple = Apple::createRandomApple();
        $id = $apple->id;

        $found = $this->service->findApple($id);

        $this->assertNotNull($found);
        $this->assertEquals($id, $found->id);
        $this->assertInstanceOf(Apple::class, $found);
    }

    /**
     * Тест поиска несуществующего яблока
     */
    public function testFindAppleNotFound()
    {
        $this->expectException(AppleNotFoundException::class);
        $this->service->findApple(999);
    }

    /**
     * Тест получения статистики
     */
    public function testGetStatistics()
    {
        // Создаём яблоки в разных состояниях
        $apple1 = Apple::createRandomApple(); // на дереве

        $apple2 = Apple::createRandomApple();
        $apple2->fallToGround(); // упало

        $apple3 = Apple::createRandomApple();
        $apple3->fallToGround();
        $apple3->fell_at = time() - (6 * 3600); // гнилое
        $apple3->save(false);
        $apple3->updateRottenStatus();

        $stats = $this->service->getStatistics();

        $this->assertEquals(3, $stats['total']);
        $this->assertEquals(1, $stats['on_tree']);
        $this->assertEquals(1, $stats['fallen']);
        $this->assertEquals(1, $stats['rotten']);
    }

    /**
     * Тест сброса кеша после изменений
     */
    public function testCacheClearedAfterChanges()
    {
        // Создаём яблоко
        Apple::createRandomApple();

        // Получаем список (кешируется)
        $apples1 = $this->service->getAllApples();
        $this->assertCount(1, $apples1);

        // Генерируем ещё яблоки через сервис (сбросит кеш)
        $this->service->generateRandomApples(2);

        // Получаем список снова (должен быть из БД, не из кеша)
        $apples2 = $this->service->getAllApples();
        $this->assertCount(3, $apples2);
    }

    /**
     * Тест транзакций при генерации
     */
    public function testGenerateUsesTransaction()
    {
        // Генерируем яблоки
        $generated = $this->service->generateRandomApples(3);

        $this->assertEquals(3, $generated);
        $this->assertCount(3, Apple::find()->all());
    }
}
