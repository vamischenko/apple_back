<?php

namespace common\services;

use Yii;
use common\models\Apple;
use common\repositories\AppleRepository;

/**
 * Сервис для сбора метрик и расширенной статистики по яблокам
 *
 * Предоставляет детальную аналитику по яблокам, включая:
 * - Количество созданных яблок
 * - Количество испорченных яблок
 * - Статистику по цветам
 * - Временные метрики
 */
class AppleMetricsService
{
    /**
     * @var AppleRepository Репозиторий для работы с данными яблок
     */
    private AppleRepository $repository;

    /**
     * Конструктор сервиса метрик
     *
     * @param AppleRepository $repository Репозиторий яблок
     */
    public function __construct(AppleRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Получить расширенную статистику по яблокам
     *
     * @return array Массив с детальной статистикой
     */
    public function getExtendedStatistics(): array
    {
        $baseStats = $this->repository->getStatistics();

        return [
            'total_count' => $baseStats['total'],
            'status_distribution' => [
                'on_tree' => $baseStats['on_tree'],
                'fallen' => $baseStats['fallen'],
                'rotten' => $baseStats['rotten'],
            ],
            'color_distribution' => $baseStats['by_color'],
            'percentage_distribution' => [
                'on_tree_percent' => $baseStats['total'] > 0 ? round(($baseStats['on_tree'] / $baseStats['total']) * 100, 2) : 0,
                'fallen_percent' => $baseStats['total'] > 0 ? round(($baseStats['fallen'] / $baseStats['total']) * 100, 2) : 0,
                'rotten_percent' => $baseStats['total'] > 0 ? round(($baseStats['rotten'] / $baseStats['total']) * 100, 2) : 0,
            ],
            'eating_metrics' => $this->getEatingMetrics(),
            'age_metrics' => $this->getAgeMetrics(),
            'timestamp' => time(),
        ];
    }

    /**
     * Получить метрики по съеденным яблокам
     *
     * @return array Статистика по съеденным частям
     */
    public function getEatingMetrics(): array
    {
        $apples = $this->repository->findAll();

        $totalEaten = 0;
        $partiallyEaten = 0;
        $untouched = 0;
        $avgEatenPercent = 0;

        foreach ($apples as $apple) {
            $totalEaten += $apple->eaten_percent;

            if ($apple->eaten_percent > 0) {
                $partiallyEaten++;
            } else {
                $untouched++;
            }
        }

        $count = count($apples);
        $avgEatenPercent = $count > 0 ? round($totalEaten / $count, 2) : 0;

        return [
            'partially_eaten_count' => $partiallyEaten,
            'untouched_count' => $untouched,
            'average_eaten_percent' => $avgEatenPercent,
            'total_eaten_percent' => round($totalEaten, 2),
        ];
    }

    /**
     * Получить метрики по возрасту яблок
     *
     * @return array Статистика по возрасту
     */
    public function getAgeMetrics(): array
    {
        $apples = $this->repository->findAll();

        $currentTime = time();
        $totalAge = 0;
        $oldestAge = 0;
        $newestAge = PHP_INT_MAX;

        foreach ($apples as $apple) {
            $age = $currentTime - $apple->created_at;
            $totalAge += $age;

            if ($age > $oldestAge) {
                $oldestAge = $age;
            }

            if ($age < $newestAge) {
                $newestAge = $age;
            }
        }

        $count = count($apples);
        $avgAge = $count > 0 ? round($totalAge / $count) : 0;

        return [
            'average_age_seconds' => $avgAge,
            'average_age_days' => round($avgAge / 86400, 2),
            'oldest_age_seconds' => $oldestAge,
            'oldest_age_days' => round($oldestAge / 86400, 2),
            'newest_age_seconds' => $newestAge === PHP_INT_MAX ? 0 : $newestAge,
            'newest_age_days' => $newestAge === PHP_INT_MAX ? 0 : round($newestAge / 86400, 2),
        ];
    }

    /**
     * Получить метрики по созданию яблок
     *
     * Возвращает общее количество созданных яблок
     *
     * @return array Метрики создания
     */
    public function getCreationMetrics(): array
    {
        $total = $this->repository->count();

        return [
            'total_created' => $total,
            'created_today' => $this->getCreatedToday(),
            'created_this_week' => $this->getCreatedThisWeek(),
            'created_this_month' => $this->getCreatedThisMonth(),
        ];
    }

    /**
     * Получить количество созданных яблок сегодня
     *
     * @return int Количество яблок
     */
    private function getCreatedToday(): int
    {
        $startOfDay = strtotime('today midnight');
        return Apple::find()
            ->where(['>=', 'created_at', $startOfDay])
            ->count();
    }

    /**
     * Получить количество созданных яблок за неделю
     *
     * @return int Количество яблок
     */
    private function getCreatedThisWeek(): int
    {
        $startOfWeek = strtotime('monday this week');
        return Apple::find()
            ->where(['>=', 'created_at', $startOfWeek])
            ->count();
    }

    /**
     * Получить количество созданных яблок за месяц
     *
     * @return int Количество яблок
     */
    private function getCreatedThisMonth(): int
    {
        $startOfMonth = strtotime('first day of this month midnight');
        return Apple::find()
            ->where(['>=', 'created_at', $startOfMonth])
            ->count();
    }

    /**
     * Получить метрики по испорченным яблокам
     *
     * @return array Метрики порчи
     */
    public function getRottenMetrics(): array
    {
        $rottenCount = $this->repository->count(Apple::STATUS_ROTTEN);
        $fallenCount = $this->repository->count(Apple::STATUS_FALLEN);

        // Яблоки, которые скоро испортятся (меньше часа до порчи)
        $soonToRotCount = 0;
        $fallenApples = $this->repository->findByStatus(Apple::STATUS_FALLEN);

        foreach ($fallenApples as $apple) {
            if ($apple->fell_at !== null) {
                $timeSinceFall = time() - $apple->fell_at;
                $timeUntilRotten = Apple::ROTTEN_TIME - $timeSinceFall;

                if ($timeUntilRotten > 0 && $timeUntilRotten < 3600) {
                    $soonToRotCount++;
                }
            }
        }

        return [
            'total_rotten' => $rottenCount,
            'total_fallen' => $fallenCount,
            'soon_to_rot' => $soonToRotCount,
            'rotten_rate' => ($rottenCount + $fallenCount) > 0
                ? round(($rottenCount / ($rottenCount + $fallenCount)) * 100, 2)
                : 0,
        ];
    }

    /**
     * Получить полный отчет по метрикам
     *
     * @return array Полный отчет
     */
    public function getFullReport(): array
    {
        Yii::info('Generating full metrics report', 'apple');

        return [
            'statistics' => $this->getExtendedStatistics(),
            'creation_metrics' => $this->getCreationMetrics(),
            'rotten_metrics' => $this->getRottenMetrics(),
            'generated_at' => date('Y-m-d H:i:s'),
        ];
    }
}
