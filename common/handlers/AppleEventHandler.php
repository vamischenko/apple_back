<?php

namespace common\handlers;

use Yii;
use common\models\Apple;
use yii\base\Event;
use yii\base\Component;
use yii\base\BootstrapInterface;

/**
 * Обработчик событий модели Apple
 *
 * Обрабатывает события жизненного цикла яблока:
 * - appleFallen - когда яблоко падает с дерева
 * - appleRotten - когда яблоко испортилось
 */
class AppleEventHandler extends Component implements BootstrapInterface
{
    /**
     * Bootstrap method для автоматической регистрации обработчиков
     * @param \yii\base\Application $app
     */
    public function bootstrap($app)
    {
        self::register();
    }
    /**
     * Обработчик события падения яблока
     *
     * @param Event $event
     */
    public static function onAppleFallen(Event $event)
    {
        /** @var Apple $apple */
        $apple = $event->sender;

        Yii::info([
            'event' => 'apple_fallen',
            'apple_id' => $apple->id,
            'color' => $apple->color,
            'created_at' => $apple->created_at,
            'fell_at' => $apple->fell_at,
            'time_on_tree' => $apple->fell_at - $apple->created_at,
        ], 'apple_events');

        // Здесь можно добавить дополнительную логику:
        // - отправку уведомлений
        // - запись в отдельную таблицу событий
        // - обновление метрик в реальном времени
        // - webhook вызовы
    }

    /**
     * Обработчик события порчи яблока
     *
     * @param Event $event
     */
    public static function onAppleRotten(Event $event)
    {
        /** @var Apple $apple */
        $apple = $event->sender;

        $timeOnGround = time() - $apple->fell_at;

        Yii::info([
            'event' => 'apple_rotten',
            'apple_id' => $apple->id,
            'color' => $apple->color,
            'fell_at' => $apple->fell_at,
            'time_on_ground' => $timeOnGround,
            'eaten_percent' => $apple->eaten_percent,
        ], 'apple_events');

        // Здесь можно добавить:
        // - уведомление о порче
        // - автоматическое удаление через определенное время
        // - сохранение статистики порчи
    }

    /**
     * Регистрация всех обработчиков событий
     */
    public static function register()
    {
        Event::on(
            Apple::class,
            Apple::EVENT_APPLE_FALLEN,
            [self::class, 'onAppleFallen']
        );

        Event::on(
            Apple::class,
            Apple::EVENT_APPLE_ROTTEN,
            [self::class, 'onAppleRotten']
        );
    }
}
