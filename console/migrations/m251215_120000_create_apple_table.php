<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%apple}}`.
 */
class m251215_120000_create_apple_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%apple}}', [
            'id' => $this->primaryKey(),
            'color' => $this->string(50)->notNull()->comment('Цвет яблока'),
            'created_at' => $this->integer()->notNull()->comment('Дата появления (unix timestamp)'),
            'fell_at' => $this->integer()->null()->comment('Дата падения (unix timestamp)'),
            'status' => $this->string(20)->notNull()->defaultValue('on_tree')->comment('Статус: on_tree, fallen, rotten'),
            'eaten_percent' => $this->decimal(5, 2)->notNull()->defaultValue(0)->comment('Процент съеденного (0-100)'),
        ]);

        $this->createIndex(
            'idx-apple-status',
            '{{%apple}}',
            'status'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%apple}}');
    }
}
