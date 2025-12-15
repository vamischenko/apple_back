-- Создание базы данных для Apple Management System

CREATE DATABASE IF NOT EXISTS apple_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE apple_db;

-- Таблица миграций
CREATE TABLE IF NOT EXISTS `migration` (
  `version` varchar(180) NOT NULL,
  `apply_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Таблица пользователей
CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `auth_key` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password_reset_token` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `status` smallint(6) NOT NULL DEFAULT 10,
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) NOT NULL,
  `verification_token` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `password_reset_token` (`password_reset_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Таблица яблок
CREATE TABLE IF NOT EXISTS `apple` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `color` varchar(50) NOT NULL COMMENT 'Цвет яблока',
  `created_at` int(11) NOT NULL COMMENT 'Дата появления (unix timestamp)',
  `fell_at` int(11) DEFAULT NULL COMMENT 'Дата падения (unix timestamp)',
  `status` varchar(20) NOT NULL DEFAULT 'on_tree' COMMENT 'Статус: on_tree, fallen, rotten',
  `eaten_percent` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Процент съеденного (0-100)',
  PRIMARY KEY (`id`),
  KEY `idx-apple-status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
