-- Инициализация базы данных для проекта Apple
-- Этот скрипт выполняется автоматически при первом запуске MySQL контейнера

-- Создание базы данных (уже создана через переменные окружения)
USE apple_db;

-- Установка кодировки по умолчанию
ALTER DATABASE apple_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Дополнительные настройки (если нужны)
SET NAMES utf8mb4;

-- После запуска контейнера нужно будет выполнить миграции:
-- docker-compose exec php php yii migrate
