<?php

use yii\db\Migration;

/**
 * Добавление индексов для оптимизации запросов к таблице apple
 */
class m251216_000002_add_indexes_to_apple_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Индекс на fell_at для оптимизации поиска гнилых яблок
        // Используется в методе updateRottenStatus() для проверки времени падения
        $this->createIndex(
            'idx-apple-fell_at',
            '{{%apple}}',
            'fell_at'
        );

        // Индекс на status для быстрой фильтрации по статусу
        // Используется в методах findByStatus() и общей статистике
        $this->createIndex(
            'idx-apple-status',
            '{{%apple}}',
            'status'
        );

        // Индекс на color для фильтрации по цвету
        // Используется в методе findByColor()
        $this->createIndex(
            'idx-apple-color',
            '{{%apple}}',
            'color'
        );

        // Составной индекс для оптимизации запросов поиска гнилых яблок
        // Используется при одновременной проверке статуса и времени падения
        $this->createIndex(
            'idx-apple-status-fell_at',
            '{{%apple}}',
            ['status', 'fell_at']
        );

        // Индекс на created_at для сортировки по времени создания
        $this->createIndex(
            'idx-apple-created_at',
            '{{%apple}}',
            'created_at'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('idx-apple-created_at', '{{%apple}}');
        $this->dropIndex('idx-apple-status-fell_at', '{{%apple}}');
        $this->dropIndex('idx-apple-color', '{{%apple}}');
        $this->dropIndex('idx-apple-status', '{{%apple}}');
        $this->dropIndex('idx-apple-fell_at', '{{%apple}}');
    }
}
