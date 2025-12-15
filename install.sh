#!/bin/bash

# Скрипт автоматической установки проекта Apple Management System

echo "========================================="
echo "Apple Management System - Установка"
echo "========================================="
echo ""

# Проверка PHP
if ! command -v php &> /dev/null; then
    echo "❌ PHP не найден. Установите PHP 7.4 или выше."
    exit 1
fi

echo "✅ PHP найден: $(php -v | head -n 1)"

# Проверка Composer
if ! command -v composer &> /dev/null; then
    echo "❌ Composer не найден. Установите Composer."
    exit 1
fi

echo "✅ Composer найден"

# Проверка MySQL
if ! command -v mysql &> /dev/null; then
    echo "⚠️  MySQL клиент не найден. Создайте базу данных вручную."
    DB_CREATED=false
else
    echo "✅ MySQL найден"

    # Запрос данных для подключения к MySQL
    echo ""
    echo "Введите данные для подключения к MySQL:"
    read -p "MySQL пользователь [root]: " DB_USER
    DB_USER=${DB_USER:-root}

    read -sp "MySQL пароль: " DB_PASSWORD
    echo ""

    # Создание базы данных
    echo "Создание базы данных apple_db..."
    if [ -z "$DB_PASSWORD" ]; then
        mysql -u $DB_USER -e "CREATE DATABASE IF NOT EXISTS apple_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null
    else
        mysql -u $DB_USER -p$DB_PASSWORD -e "CREATE DATABASE IF NOT EXISTS apple_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null
    fi

    if [ $? -eq 0 ]; then
        echo "✅ База данных создана"
        DB_CREATED=true

        # Обновление конфигурации
        if [ -f "common/config/main-local.php" ]; then
            if [ ! -z "$DB_PASSWORD" ]; then
                sed -i "s/'password' => ''/'password' => '$DB_PASSWORD'/g" common/config/main-local.php
                echo "✅ Пароль БД обновлен в конфигурации"
            fi
        fi
    else
        echo "❌ Ошибка создания базы данных"
        DB_CREATED=false
    fi
fi

# Применение миграций
if [ "$DB_CREATED" = true ]; then
    echo ""
    echo "Применение миграций..."
    php yii migrate --interactive=0

    if [ $? -eq 0 ]; then
        echo "✅ Миграции применены"
    else
        echo "❌ Ошибка при применении миграций"
        exit 1
    fi
fi

# Создание пользователя
echo ""
echo "Создание администратора..."
php yii user/create-admin

if [ $? -eq 0 ]; then
    echo "✅ Пользователь создан"
else
    echo "❌ Ошибка создания пользователя"
fi

# Завершение
echo ""
echo "========================================="
echo "Установка завершена!"
echo "========================================="
echo ""
echo "Для запуска приложения выполните:"
echo "  php yii serve --docroot=@backend/web --port=8080"
echo ""
echo "Затем откройте в браузере:"
echo "  http://localhost:8080"
echo ""
echo "Данные для входа:"
echo "  Логин: admin"
echo "  Пароль: admin123"
echo ""
