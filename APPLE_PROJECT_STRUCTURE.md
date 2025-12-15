# Структура проекта управления яблоками

## Обзор архитектуры

Проект построен на **Yii2 Advanced Template** с разделением на **backend** и **frontend** приложения.

```
apple_back/
├── backend/              # Backend приложение (REST API)
│   ├── controllers/
│   │   ├── api/         # REST API контроллеры
│   │   │   └── AppleController.php   # API для управления яблоками
│   │   └── AppleController.php       # Web контроллер (существующий)
│   ├── config/
│   │   └── main.php     # Настройки URL rules для API
│   └── web/
│       └── .htaccess    # Настройки для красивых URL
│
├── frontend/            # Frontend приложение (Web UI)
│   ├── controllers/
│   │   └── AppleController.php       # Контроллер для веб-интерфейса
│   ├── views/
│   │   └── apple/
│   │       ├── index.php   # Список яблок
│   │       └── view.php    # Просмотр яблока
│   └── web/
│       └── .htaccess    # Настройки для красивых URL
│
├── common/              # Общие компоненты
│   ├── models/
│   │   └── Apple.php    # Модель яблока
│   ├── services/
│   │   └── AppleService.php    # Бизнес-логика
│   └── repositories/
│       └── AppleRepository.php  # Работа с БД
│
└── console/
    └── migrations/
        └── m251215_120000_create_apple_table.php
```

---

## Компоненты системы

### 1. Backend (REST API)

**Файл:** [backend/controllers/api/AppleController.php](backend/controllers/api/AppleController.php)

**Назначение:** REST API для программного доступа к управлению яблоками

**Endpoints:**
- `GET /api/apples` - список яблок
- `GET /api/apples/{id}` - получить яблоко
- `POST /api/apples` - создать яблоко
- `POST /api/apples/generate` - сгенерировать несколько яблок
- `POST /api/apples/{id}/fall` - уронить яблоко
- `POST /api/apples/{id}/eat` - откусить от яблока
- `DELETE /api/apples/{id}` - удалить яблоко

**Особенности:**
- Возвращает данные в формате JSON
- Использует стандартные HTTP коды ответов
- Аутентификация отключена (можно включить при необходимости)

---

### 2. Frontend (Web Interface)

**Контроллер:** [frontend/controllers/AppleController.php](frontend/controllers/AppleController.php)

**Представления:**
- [frontend/views/apple/index.php](frontend/views/apple/index.php) - таблица со всеми яблоками
- [frontend/views/apple/view.php](frontend/views/apple/view.php) - детальный просмотр яблока

**Назначение:** Веб-интерфейс для работы с яблоками через браузер

**Функции:**
- Просмотр списка яблок в таблице
- Детальный просмотр каждого яблока
- Визуальное отображение статусов и прогресс-баров
- Интерактивные формы для действий

---

### 3. Общие компоненты (common/)

#### Модель Apple
**Файл:** [common/models/Apple.php](common/models/Apple.php)

**Свойства:**
- `id` - идентификатор
- `color` - цвет (red, green, yellow)
- `status` - статус (on_tree, fallen, rotten)
- `created_at` - дата появления
- `fell_at` - дата падения
- `eaten_percent` - процент съеденности (0-100)

**Методы:**
- `createRandomApple()` - создать случайное яблоко
- `fallToGround()` - уронить яблоко
- `eat($percent)` - откусить процент
- `updateRottenStatus()` - обновить статус гниения
- `getSize()` - получить текущий размер
- `getStatusLabel()` - получить название статуса

#### Сервис AppleService
**Файл:** [common/services/AppleService.php](common/services/AppleService.php)

**Назначение:** Бизнес-логика управления яблоками

**Методы:**
- `getAllApples()` - получить все яблоки с обновлением статусов
- `generateRandomApples($count)` - генерация яблок
- `fallApple($id)` - уронить яблоко
- `eatApple($id, $percent)` - откусить
- `deleteApple($id)` - удалить
- `findApple($id)` - найти по ID

#### Репозиторий AppleRepository
**Файл:** [common/repositories/AppleRepository.php](common/repositories/AppleRepository.php)

**Назначение:** Работа с базой данных

---

## Различия между Backend и Frontend

| Аспект | Backend API | Frontend Web |
|--------|-------------|--------------|
| **Формат ответа** | JSON | HTML страницы |
| **Использование** | Программный доступ, мобильные приложения | Браузер, веб-интерфейс |
| **Контроллер** | `backend/controllers/api/AppleController.php` | `frontend/controllers/AppleController.php` |
| **URL примеры** | `/api/apples`, `/api/apples/1/fall` | `/apple/index`, `/apple/view?id=1` |
| **Аутентификация** | Может использовать токены | Session-based |

---

## Бизнес-логика

### Жизненный цикл яблока

```
[Создание] → on_tree (На дереве)
    ↓ fallToGround()
fallen (Упало на землю)
    ↓ eat(percent)
[Частично съедено] → eaten_percent увеличивается
    ↓ eat(100% total)
[Удаление из БД]

Альтернативный путь:
fallen (> 5 часов) → rotten (Гнилое) → [Нельзя съесть]
```

### Правила

1. **Падение**: Только яблоки со статусом `on_tree` могут упасть
2. **Откусывание**:
   - Можно есть только `fallen` яблоки
   - Нельзя есть `on_tree` (еще на дереве)
   - Нельзя есть `rotten` (испорченные)
   - При достижении 100% яблоко удаляется
3. **Гниение**: Автоматическое через 5 часов после падения
4. **Удаление**: Возможно в любом статусе

---

## Настройки URL

### Backend
**Файл:** [backend/config/main.php](backend/config/main.php:40-60)

Использует `yii\rest\UrlRule` для REST API endpoints.

### Frontend
Использует стандартные Yii2 URL rules.

### .htaccess
Оба приложения имеют `.htaccess` для включения красивых URL (без `index.php`).

---

## Использование

### Через API (Backend)

```bash
# Создать яблоко
curl -X POST http://localhost/backend/web/api/apples

# Получить список
curl http://localhost/backend/web/api/apples

# Уронить
curl -X POST http://localhost/backend/web/api/apples/1/fall

# Откусить
curl -X POST http://localhost/backend/web/api/apples/1/eat -d "percent=25"
```

### Через веб-интерфейс (Frontend)

Откройте в браузере:
```
http://localhost/frontend/web/apple/index
```

---

## База данных

### Таблица apple

```sql
CREATE TABLE `apple` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `color` varchar(50) NOT NULL,
  `created_at` int(11) NOT NULL,
  `fell_at` int(11) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'on_tree',
  `eaten_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Миграция:** [console/migrations/m251215_120000_create_apple_table.php](console/migrations/m251215_120000_create_apple_table.php)

---

## Документация API

Полная документация API доступна в файле [API_DOCUMENTATION.md](API_DOCUMENTATION.md)

---

## Расширение функциональности

### Добавление аутентификации в API

В [backend/controllers/api/AppleController.php](backend/controllers/api/AppleController.php:54) закомментирована строка:

```php
unset($behaviors['authenticator']);
```

Удалите эту строку, чтобы включить аутентификацию через Bearer токен.

### Добавление валидации

Валидация уже встроена в модель [Apple.php](common/models/Apple.php:40-50) через метод `rules()`.

### Тестирование

Тесты можно добавить в:
- `backend/tests/` - для API тестов
- `frontend/tests/` - для UI тестов
- `common/tests/` - для unit тестов моделей и сервисов
