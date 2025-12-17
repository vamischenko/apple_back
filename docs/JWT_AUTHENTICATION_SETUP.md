# Настройка JWT аутентификации для API

## 1. Установка зависимости

```bash
composer require firebase/php-jwt
```

или добавьте в composer.json:
```json
{
    "require": {
        "firebase/php-jwt": "^6.0"
    }
}
```

## 2. Добавление поля access_token в таблицу user

Создайте миграцию:

```bash
php yii migrate/create add_access_token_to_user
```

Содержимое миграции:

```php
<?php

use yii\db\Migration;

class m251217_000000_add_access_token_to_user extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%user}}', 'access_token', $this->string(500)->after('auth_key'));
        $this->createIndex('idx-user-access_token', '{{%user}}', 'access_token');
    }

    public function safeDown()
    {
        $this->dropIndex('idx-user-access_token', '{{%user}}');
        $this->dropColumn('{{%user}}', 'access_token');
    }
}
```

Примените миграцию:
```bash
php yii migrate
```

## 3. Обновление модели User

Добавьте метод для генерации JWT токена в `common/models/User.php`:

```php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class User extends ActiveRecord implements IdentityInterface
{
    /**
     * Генерация JWT токена для пользователя
     *
     * @param int $expirationTime Время жизни токена в секундах (по умолчанию 7 дней)
     * @return string JWT токен
     */
    public function generateJwtToken(int $expirationTime = 604800): string
    {
        $jwtSecret = Yii::$app->params['jwtSecret'] ?? 'your-secret-key-change-this';

        $payload = [
            'iss' => Yii::$app->request->hostInfo, // Издатель
            'aud' => Yii::$app->request->hostInfo, // Аудитория
            'iat' => time(), // Время создания
            'exp' => time() + $expirationTime, // Время истечения
            'uid' => $this->id, // ID пользователя
            'username' => $this->username, // Имя пользователя
        ];

        $token = JWT::encode($payload, $jwtSecret, 'HS256');

        // Сохраняем токен в БД
        $this->access_token = $token;
        $this->save(false);

        return $token;
    }

    /**
     * Проверка и декодирование JWT токена
     *
     * @param string $token JWT токен
     * @return object|null Декодированные данные токена или null при ошибке
     */
    public static function validateJwtToken(string $token): ?object
    {
        try {
            $jwtSecret = Yii::$app->params['jwtSecret'] ?? 'your-secret-key-change-this';
            return JWT::decode($token, new Key($jwtSecret, 'HS256'));
        } catch (\Exception $e) {
            Yii::error("JWT validation failed: {$e->getMessage()}", 'jwt');
            return null;
        }
    }

    /**
     * Finds user by access token
     *
     * @param string $token
     * @param mixed $type
     * @return User|null
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        // Проверяем JWT токен
        $decoded = self::validateJwtToken($token);

        if ($decoded === null) {
            return null;
        }

        return static::findOne(['id' => $decoded->uid, 'status' => self::STATUS_ACTIVE]);
    }
}
```

## 4. Добавление секретного ключа JWT

В `common/config/params.php` или `common/config/params-local.php`:

```php
return [
    // ... другие параметры
    'jwtSecret' => getenv('JWT_SECRET') ?: 'change-this-secret-key-in-production',
];
```

В `.env`:
```
JWT_SECRET=your-super-secret-jwt-key-min-256-bits
```

## 5. Создание контроллера для аутентификации

Создайте `backend/controllers/api/AuthController.php`:

```php
<?php

namespace backend\controllers\api;

use Yii;
use yii\rest\Controller;
use yii\filters\ContentNegotiator;
use yii\web\Response;
use common\models\User;
use common\models\LoginForm;

class AuthController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['contentNegotiator'] = [
            'class' => ContentNegotiator::class,
            'formats' => [
                'application/json' => Response::FORMAT_JSON,
            ],
        ];

        return $behaviors;
    }

    /**
     * POST /api/auth/login - получение JWT токена
     *
     * Body: {"username": "admin", "password": "admin123"}
     */
    public function actionLogin()
    {
        $model = new LoginForm();

        if ($model->load(Yii::$app->request->post(), '') && $model->login()) {
            $user = $model->getUser();
            $token = $user->generateJwtToken();

            return [
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                ],
            ];
        }

        Yii::$app->response->statusCode = 401;
        return [
            'success' => false,
            'errors' => $model->errors,
            'message' => 'Неверные учетные данные',
        ];
    }

    /**
     * POST /api/auth/refresh - обновление токена
     */
    public function actionRefresh()
    {
        $user = Yii::$app->user->identity;

        if (!$user) {
            Yii::$app->response->statusCode = 401;
            return [
                'success' => false,
                'message' => 'Требуется аутентификация',
            ];
        }

        $token = $user->generateJwtToken();

        return [
            'success' => true,
            'token' => $token,
        ];
    }

    /**
     * POST /api/auth/logout - выход (инвалидация токена)
     */
    public function actionLogout()
    {
        $user = Yii::$app->user->identity;

        if ($user) {
            // Удаляем токен из БД
            $user->access_token = null;
            $user->save(false);
        }

        return [
            'success' => true,
            'message' => 'Успешный выход',
        ];
    }
}
```

## 6. Включение JWT аутентификации в AppleController

В `backend/controllers/api/AppleController.php`:

```php
use yii\filters\auth\HttpBearerAuth;

public function behaviors()
{
    $behaviors = parent::behaviors();

    // Включаем Bearer аутентификацию
    $behaviors['authenticator'] = [
        'class' => HttpBearerAuth::class,
    ];

    // Исключаем некоторые действия из аутентификации (опционально)
    $behaviors['authenticator']['except'] = ['index', 'view']; // Список можно читать без токена

    $behaviors['contentNegotiator'] = [
        'class' => ContentNegotiator::class,
        'formats' => [
            'application/json' => Response::FORMAT_JSON,
        ],
    ];

    return $behaviors;
}
```

## 7. Настройка роутинга

В `backend/config/main.php`:

```php
'urlManager' => [
    'enablePrettyUrl' => true,
    'showScriptName' => false,
    'rules' => [
        // ... существующие правила

        // Аутентификация
        'POST api/auth/login' => 'api/auth/login',
        'POST api/auth/refresh' => 'api/auth/refresh',
        'POST api/auth/logout' => 'api/auth/logout',
    ],
],
```

## 8. Тестирование API с JWT

### Получение токена:

```bash
curl -X POST http://localhost:21080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'
```

Ответ:
```json
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 1,
    "username": "admin",
    "email": "admin@example.com"
  }
}
```

### Использование токена для запросов:

```bash
curl -X GET http://localhost:21080/api/apples \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."
```

```bash
curl -X POST http://localhost:21080/api/apples/generate \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..." \
  -H "Content-Type: application/json" \
  -d '{"count":5}'
```

### Без токена (для публичных endpoint'ов):

```bash
curl -X GET http://localhost:21080/api/apples
```

## 9. Обработка ошибок аутентификации

Yii2 автоматически вернет HTTP 401 при неверном или отсутствующем токене:

```json
{
  "name": "Unauthorized",
  "message": "Your request was made with invalid credentials.",
  "code": 0,
  "status": 401
}
```

## 10. Безопасность

1. **Секретный ключ**: Используйте сложный ключ длиной минимум 256 бит
2. **HTTPS**: В production всегда используйте HTTPS
3. **Время жизни токена**: Настройте разумное время (например, 1 день для API)
4. **Refresh токены**: Реализуйте механизм обновления токенов
5. **Blacklist**: Ведите черный список отозванных токенов для критичных операций

## 11. Frontend интеграция

JavaScript пример:

```javascript
// Вход и получение токена
async function login(username, password) {
  const response = await fetch('/api/auth/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ username, password })
  });

  const data = await response.json();

  if (data.success) {
    localStorage.setItem('jwt_token', data.token);
    return data;
  }
  throw new Error(data.message);
}

// Запрос с токеном
async function fetchApples() {
  const token = localStorage.getItem('jwt_token');

  const response = await fetch('/api/apples', {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });

  return await response.json();
}

// Создание яблок
async function generateApples(count) {
  const token = localStorage.getItem('jwt_token');

  const response = await fetch('/api/apples/generate', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ count })
  });

  return await response.json();
}
```

## Итого

После выполнения всех шагов:
- ✅ JWT токены генерируются при входе
- ✅ API защищен Bearer аутентификацией
- ✅ Публичные endpoint'ы остаются доступны без токена
- ✅ Токены хранятся в БД и могут быть инвалидированы
- ✅ Есть endpoint'ы для login/logout/refresh
