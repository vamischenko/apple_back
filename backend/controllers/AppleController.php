<?php

namespace backend\controllers;

use Yii;
use common\services\AppleService;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * Контроллер для управления яблоками
 *
 * Реализует CRUD операции для модели Apple через сервисный слой
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
     * Настройка поведений контроллера
     *
     * Настраивает RBAC контроль доступа и разрешенные HTTP методы для действий
     *
     * @return array Массив настроек поведений
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['index'],
                        'allow' => true,
                        'roles' => ['viewApple'], // Разрешение на просмотр
                    ],
                    [
                        'actions' => ['generate'],
                        'allow' => true,
                        'roles' => ['createApple'], // Разрешение на создание
                    ],
                    [
                        'actions' => ['fall', 'eat'],
                        'allow' => true,
                        'roles' => ['updateApple'], // Разрешение на обновление
                    ],
                    [
                        'actions' => ['delete'],
                        'allow' => true,
                        'roles' => ['deleteApple'], // Разрешение на удаление
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'fall' => ['POST'],
                    'eat' => ['POST'],
                    'generate' => ['POST'],
                ],
            ],
            'rateLimiter' => [
                'class' => \yii\filters\RateLimiter::class,
                'only' => ['generate'],
                'user' => Yii::$app->user,
            ],
        ];
    }

    /**
     * Отображает список всех яблок
     *
     * Получает все яблоки с автоматическим обновлением статуса гнилости
     *
     * @return string HTML страница со списком яблок
     */
    public function actionIndex()
    {
        $apples = $this->appleService->getAllApples();

        return $this->render('index', [
            'apples' => $apples,
        ]);
    }

    /**
     * Генерирует случайные яблоки
     *
     * Создает указанное количество яблок со случайными параметрами.
     * Количество ограничено диапазоном от 1 до 50.
     *
     * @return \yii\web\Response Перенаправление на страницу списка
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
     *
     * Изменяет статус яблока с "на дереве" на "упало" и фиксирует время падения.
     * Только яблоки на дереве могут упасть.
     *
     * @param int $id Идентификатор яблока
     * @return \yii\web\Response Перенаправление на страницу списка
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
     *
     * Уменьшает размер яблока на указанный процент.
     * Можно есть только упавшие и не гнилые яблоки.
     * При достижении 100% съеденности яблоко удаляется.
     *
     * @param int $id Идентификатор яблока
     * @return \yii\web\Response Перенаправление на страницу списка
     */
    public function actionEat($id)
    {
        $percent = (float)Yii::$app->request->post('percent', 25);

        try {
            $this->appleService->eatApple($id, $percent);
            Yii::$app->session->setFlash('success', "Откушено {$percent}% яблока");
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        return $this->redirect(['index']);
    }

    /**
     * Удаляет яблоко
     *
     * Полностью удаляет яблоко из базы данных.
     *
     * @param int $id Идентификатор яблока
     * @return \yii\web\Response Перенаправление на страницу списка
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
