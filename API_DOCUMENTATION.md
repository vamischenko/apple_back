# API Документация для управления яблоками

## Backend REST API

Backend предоставляет REST API для управления яблоками.

### Базовый URL
```
http://your-domain/backend/web/api/apples
```

---

## API Endpoints

### 1. Получить список всех яблок
**GET** `/api/apples`

**Ответ:**
```json
[
  {
    "id": 1,
    "color": "red",
    "status": "on_tree",
    "status_label": "На дереве",
    "created_at": 1734264000,
    "created_at_formatted": "15.12.2025 12:00",
    "fell_at": null,
    "fell_at_formatted": "-",
    "eaten_percent": 0,
    "size": 1
  }
]
```

---

### 2. Получить яблоко по ID
**GET** `/api/apples/{id}`

**Параметры:**
- `id` - ID яблока

**Ответ:**
```json
{
  "id": 1,
  "color": "red",
  "status": "on_tree",
  "status_label": "На дереве",
  "created_at": 1734264000,
  "created_at_formatted": "15.12.2025 12:00",
  "fell_at": null,
  "fell_at_formatted": "-",
  "eaten_percent": 0,
  "size": 1
}
```

**Ошибка 404:**
```json
{
  "error": "Яблоко не найдено"
}
```

---

### 3. Создать случайное яблоко
**POST** `/api/apples`

**Ответ (201):**
```json
{
  "id": 2,
  "color": "green",
  "status": "on_tree",
  "status_label": "На дереве",
  "created_at": 1734264100,
  "created_at_formatted": "15.12.2025 12:01",
  "fell_at": null,
  "fell_at_formatted": "-",
  "eaten_percent": 0,
  "size": 1
}
```

**Пример curl:**
```bash
curl -X POST http://your-domain/backend/web/api/apples
```

---

### 4. Сгенерировать несколько яблок
**POST** `/api/apples/generate`

**Параметры (POST):**
- `count` - количество яблок для генерации (1-50, по умолчанию 1)

**Ответ:**
```json
{
  "success": true,
  "generated": 5,
  "message": "Сгенерировано яблок: 5"
}
```

**Пример curl:**
```bash
curl -X POST http://your-domain/backend/web/api/apples/generate -d "count=10"
```

---

### 5. Уронить яблоко
**POST** `/api/apples/{id}/fall`

**Параметры:**
- `id` - ID яблока

**Ответ:**
```json
{
  "success": true,
  "message": "Яблоко упало на землю",
  "apple": {
    "id": 1,
    "color": "red",
    "status": "fallen",
    "status_label": "Упало",
    "created_at": 1734264000,
    "created_at_formatted": "15.12.2025 12:00",
    "fell_at": 1734264200,
    "fell_at_formatted": "15.12.2025 12:03",
    "eaten_percent": 0,
    "size": 1
  }
}
```

**Ошибка (400):**
```json
{
  "success": false,
  "error": "Яблоко уже не на дереве"
}
```

**Пример curl:**
```bash
curl -X POST http://your-domain/backend/web/api/apples/1/fall
```

---

### 6. Откусить от яблока
**POST** `/api/apples/{id}/eat`

**Параметры:**
- `id` - ID яблока
- `percent` - процент для откусывания (POST параметр, по умолчанию 25)

**Ответ (яблоко еще не съедено полностью):**
```json
{
  "success": true,
  "message": "Откушено 25% яблока",
  "apple": {
    "id": 1,
    "color": "red",
    "status": "fallen",
    "status_label": "Упало",
    "created_at": 1734264000,
    "created_at_formatted": "15.12.2025 12:00",
    "fell_at": 1734264200,
    "fell_at_formatted": "15.12.2025 12:03",
    "eaten_percent": 25,
    "size": 0.75
  }
}
```

**Ответ (яблоко съедено полностью):**
```json
{
  "success": true,
  "message": "Яблоко съедено полностью и удалено",
  "apple": null
}
```

**Ошибка (400):**
```json
{
  "success": false,
  "error": "Съесть нельзя, яблоко на дереве"
}
```

**Пример curl:**
```bash
curl -X POST http://your-domain/backend/web/api/apples/1/eat -d "percent=50"
```

---

### 7. Удалить яблоко
**DELETE** `/api/apples/{id}`

**Параметры:**
- `id` - ID яблока

**Ответ (204):**
```json
{
  "success": true,
  "message": "Яблоко удалено"
}
```

**Ошибка (400):**
```json
{
  "success": false,
  "error": "Яблоко не найдено"
}
```

**Пример curl:**
```bash
curl -X DELETE http://your-domain/backend/web/api/apples/1
```

---

## Frontend Web Interface

Frontend предоставляет веб-интерфейс для управления яблоками.

### URL
```
http://your-domain/frontend/web/apple
```

### Доступные страницы

1. **Список яблок** - `/apple/index`
   - Показывает все яблоки в таблице
   - Кнопки для создания и генерации яблок
   - Действия: просмотр, уронить, откусить, удалить

2. **Просмотр яблока** - `/apple/view?id={id}`
   - Детальная информация о яблоке
   - Интерактивные кнопки для действий
   - Форма для указания процента откусывания

3. **Создать яблоко** - `/apple/create`
   - Создает случайное яблоко и перенаправляет на его просмотр

---

## Статусы яблок

- **on_tree** (На дереве) - яблоко висит на дереве, можно уронить
- **fallen** (Упало) - яблоко на земле, можно съесть
- **rotten** (Гнилое) - яблоко испортилось (более 5 часов на земле), нельзя съесть

---

## Бизнес-правила

1. **Создание**: Яблоко создается со случайным цветом (red, green, yellow) и датой появления
2. **Падение**: Только яблоки на дереве могут упасть
3. **Откусывание**:
   - Можно есть только упавшие яблоки
   - Нельзя есть гнилые яблоки
   - При достижении 100% съеденности яблоко удаляется
4. **Гниение**: Яблоки на земле портятся через 5 часов
5. **Удаление**: Можно удалить яблоко в любом статусе

---

## Примеры использования API

### Полный сценарий работы с яблоком

```bash
# 1. Создать яблоко
curl -X POST http://localhost/backend/web/api/apples

# Ответ: {"id": 1, "color": "red", "status": "on_tree", ...}

# 2. Уронить яблоко
curl -X POST http://localhost/backend/web/api/apples/1/fall

# 3. Откусить 25%
curl -X POST http://localhost/backend/web/api/apples/1/eat -d "percent=25"

# 4. Откусить еще 50%
curl -X POST http://localhost/backend/web/api/apples/1/eat -d "percent=50"

# 5. Съесть остаток (25%)
curl -X POST http://localhost/backend/web/api/apples/1/eat -d "percent=25"
# Яблоко будет удалено автоматически
```

### Массовая генерация

```bash
# Создать 10 яблок
curl -X POST http://localhost/backend/web/api/apples/generate -d "count=10"

# Получить список всех яблок
curl http://localhost/backend/web/api/apples
```

---

## Коды ответов

- **200 OK** - успешный GET запрос
- **201 Created** - успешное создание яблока
- **204 No Content** - успешное удаление
- **400 Bad Request** - ошибка валидации или бизнес-логики
- **404 Not Found** - яблоко не найдено
