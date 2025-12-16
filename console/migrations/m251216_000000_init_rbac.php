<?php

use yii\db\Migration;

/**
 * Инициализация RBAC (Role-Based Access Control)
 *
 * Создает таблицы для системы управления ролями и разрешениями
 */
class m251216_000000_init_rbac extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        // Таблица правил
        $this->createTable('{{%auth_rule}}', [
            'name' => $this->string(64)->notNull(),
            'data' => $this->binary(),
            'created_at' => $this->integer(),
            'updated_at' => $this->integer(),
            'PRIMARY KEY ([[name]])',
        ], $tableOptions);

        // Таблица элементов (роли и разрешения)
        $this->createTable('{{%auth_item}}', [
            'name' => $this->string(64)->notNull(),
            'type' => $this->smallInteger()->notNull(),
            'description' => $this->text(),
            'rule_name' => $this->string(64),
            'data' => $this->binary(),
            'created_at' => $this->integer(),
            'updated_at' => $this->integer(),
            'PRIMARY KEY ([[name]])',
        ], $tableOptions);

        $this->createIndex('idx-auth_item-type', '{{%auth_item}}', 'type');
        $this->addForeignKey(
            'fk-auth_item-rule_name',
            '{{%auth_item}}',
            'rule_name',
            '{{%auth_rule}}',
            'name',
            'SET NULL',
            'CASCADE'
        );

        // Таблица связей элементов (наследование ролей и разрешений)
        $this->createTable('{{%auth_item_child}}', [
            'parent' => $this->string(64)->notNull(),
            'child' => $this->string(64)->notNull(),
            'PRIMARY KEY ([[parent]], [[child]])',
        ], $tableOptions);

        $this->addForeignKey(
            'fk-auth_item_child-parent',
            '{{%auth_item_child}}',
            'parent',
            '{{%auth_item}}',
            'name',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-auth_item_child-child',
            '{{%auth_item_child}}',
            'child',
            '{{%auth_item}}',
            'name',
            'CASCADE',
            'CASCADE'
        );

        // Таблица назначений (пользователь -> роль)
        $this->createTable('{{%auth_assignment}}', [
            'item_name' => $this->string(64)->notNull(),
            'user_id' => $this->string(64)->notNull(),
            'created_at' => $this->integer(),
            'PRIMARY KEY ([[item_name]], [[user_id]])',
        ], $tableOptions);

        $this->addForeignKey(
            'fk-auth_assignment-item_name',
            '{{%auth_assignment}}',
            'item_name',
            '{{%auth_item}}',
            'name',
            'CASCADE',
            'CASCADE'
        );

        // Создаем начальные разрешения
        $this->insert('{{%auth_item}}', [
            'name' => 'createApple',
            'type' => 2, // Permission
            'description' => 'Создание яблок',
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->insert('{{%auth_item}}', [
            'name' => 'viewApple',
            'type' => 2,
            'description' => 'Просмотр яблок',
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->insert('{{%auth_item}}', [
            'name' => 'updateApple',
            'type' => 2,
            'description' => 'Обновление яблок (падение, съедение)',
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->insert('{{%auth_item}}', [
            'name' => 'deleteApple',
            'type' => 2,
            'description' => 'Удаление яблок',
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        // Создаем роли
        $this->insert('{{%auth_item}}', [
            'name' => 'guest',
            'type' => 1, // Role
            'description' => 'Гость - только просмотр',
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->insert('{{%auth_item}}', [
            'name' => 'user',
            'type' => 1,
            'description' => 'Пользователь - просмотр и взаимодействие',
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->insert('{{%auth_item}}', [
            'name' => 'manager',
            'type' => 1,
            'description' => 'Менеджер - все операции кроме удаления',
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->insert('{{%auth_item}}', [
            'name' => 'admin',
            'type' => 1,
            'description' => 'Администратор - полный доступ',
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        // Назначаем разрешения ролям

        // Guest - только просмотр
        $this->insert('{{%auth_item_child}}', [
            'parent' => 'guest',
            'child' => 'viewApple',
        ]);

        // User - просмотр + создание + обновление
        $this->insert('{{%auth_item_child}}', [
            'parent' => 'user',
            'child' => 'viewApple',
        ]);
        $this->insert('{{%auth_item_child}}', [
            'parent' => 'user',
            'child' => 'createApple',
        ]);
        $this->insert('{{%auth_item_child}}', [
            'parent' => 'user',
            'child' => 'updateApple',
        ]);

        // Manager - все кроме удаления
        $this->insert('{{%auth_item_child}}', [
            'parent' => 'manager',
            'child' => 'user', // Наследует все права user
        ]);

        // Admin - все права
        $this->insert('{{%auth_item_child}}', [
            'parent' => 'admin',
            'child' => 'manager', // Наследует все права manager
        ]);
        $this->insert('{{%auth_item_child}}', [
            'parent' => 'admin',
            'child' => 'deleteApple',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%auth_assignment}}');
        $this->dropTable('{{%auth_item_child}}');
        $this->dropTable('{{%auth_item}}');
        $this->dropTable('{{%auth_rule}}');
    }
}
