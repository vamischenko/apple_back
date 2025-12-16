<?php

namespace frontend\controllers;

use Yii;
use common\models\Apple;
use common\services\AppleService;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * Контроллер для управления яблоками через веб-интерфейс
 */
class AppleController extends Controller
{
    /**
     * @var AppleService Сервис для работы с яблоками
     */
    private $appleService;

    /**
     * Конструктор контроллера
     *
     * Сервис внедряется через DI контейнер Yii2.
     *
     * @param string $id Идентификатор контроллера
     * @param \yii\base\Module $module Модуль, которому принадлежит контроллер
     * @param AppleService $appleService Сервис яблок (внедряется через DI)
     * @param array $config Параметры конфигурации
     */
    public function __construct($id, $module, AppleService $appleService = null, $config = [])
    {
        $this->appleService = $appleService ?: Yii::$app->get('appleService');
        parent::__construct($id, $module, $config);
    }

    /**
     * Настройка behaviors
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'fall' => ['POST'],
                    'eat' => ['POST'],
                    'generate' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Отображает список всех яблок
     */
    public function actionIndex()
    {
        $apples = $this->appleService->getAllApples();

        return $this->render('index', [
            'apples' => $apples,
        ]);
    }

    /**
     * Отображает информацию о конкретном яблоке
     */
    public function actionView($id)
    {
        try {
            $apple = $this->appleService->findApple($id);
            return $this->render('view', [
                'apple' => $apple,
            ]);
        } catch (NotFoundHttpException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
            return $this->redirect(['index']);
        }
    }

    /**
     * Создает новое случайное яблоко
     */
    public function actionCreate()
    {
        $apple = Apple::createRandomApple();

        if ($apple) {
            Yii::$app->session->setFlash('success', 'Яблоко успешно создано');
            return $this->redirect(['view', 'id' => $apple->id]);
        }

        Yii::$app->session->setFlash('error', 'Не удалось создать яблоко');
        return $this->redirect(['index']);
    }

    /**
     * Генерирует случайные яблоки
     */
    public function actionGenerate()
    {
        $count = Yii::$app->request->post('count', 1);
        $generated = $this->appleService->generateRandomApples($count);

        Yii::$app->session->setFlash('success', "Сгенерировано яблок: {$generated}");
        return $this->redirect(['index']);
    }

    /**
     * Роняет яблоко на землю
     */
    public function actionFall($id)
    {
        try {
            $this->appleService->fallApple($id);
            Yii::$app->session->setFlash('success', 'Яблоко упало на землю');
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        return $this->redirect(['index']);
    }

    /**
     * Откусывает часть яблока
     */
    public function actionEat($id)
    {
        $percent = (float)Yii::$app->request->post('percent', 25);

        try {
            $this->appleService->eatApple($id, $percent);
            Yii::$app->session->setFlash('success', "Откушено {$percent}% яблока");
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        } catch (NotFoundHttpException $e) {
            Yii::$app->session->setFlash('success', 'Яблоко съедено полностью');
        }

        return $this->redirect(['index']);
    }

    /**
     * Удаляет яблоко
     */
    public function actionDelete($id)
    {
        try {
            $this->appleService->deleteApple($id);
            Yii::$app->session->setFlash('success', 'Яблоко удалено');
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        return $this->redirect(['index']);
    }
}
