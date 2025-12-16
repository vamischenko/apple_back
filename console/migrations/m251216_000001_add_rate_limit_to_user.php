<?php

use yii\db\Migration;

/**
 * Добавление полей для Rate Limiting в таблицу user
 */
class m251216_000001_add_rate_limit_to_user extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%user}}', 'allowance', $this->integer()->defaultValue(0)->comment('Текущий лимит запросов'));
        $this->addColumn('{{%user}}', 'allowance_updated_at', $this->integer()->defaultValue(0)->comment('Время последнего обновления лимита'));

        $this->createIndex(
            'idx-user-allowance_updated_at',
            '{{%user}}',
            'allowance_updated_at'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('idx-user-allowance_updated_at', '{{%user}}');
        $this->dropColumn('{{%user}}', 'allowance_updated_at');
        $this->dropColumn('{{%user}}', 'allowance');
    }
}
