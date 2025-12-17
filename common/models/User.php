<?php

namespace common\models;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\filters\RateLimitInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * User model
 *
 * @property integer $id
 * @property string $username
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $verification_token
 * @property string $email
 * @property string $auth_key
 * @property integer $status
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $password write-only password
 * @property integer $allowance Текущий лимит запросов
 * @property integer $allowance_updated_at Время последнего обновления лимита
 */
class User extends ActiveRecord implements IdentityInterface, RateLimitInterface
{
    const STATUS_DELETED = 0;
    const STATUS_INACTIVE = 9;
    const STATUS_ACTIVE = 10;


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['status', 'default', 'value' => self::STATUS_INACTIVE],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_DELETED]],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * {@inheritdoc}
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

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token)
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }

        return static::findOne([
            'password_reset_token' => $token,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Finds user by verification email token
     *
     * @param string $token verify email token
     * @return static|null
     */
    public static function findByVerificationToken($token) {
        return static::findOne([
            'verification_token' => $token,
            'status' => self::STATUS_INACTIVE
        ]);
    }

    /**
     * Finds out if password reset token is valid
     *
     * @param string $token password reset token
     * @return bool
     */
    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }

        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        return $timestamp + $expire >= time();
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Generates new token for email verification
     */
    public function generateEmailVerificationToken()
    {
        $this->verification_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }

    /**
     * Возвращает максимальное количество разрешенных запросов и временной период
     *
     * @param \yii\web\Request $request
     * @param \yii\base\Action $action
     * @return array Массив [количество запросов, временной период в секундах]
     */
    public function getRateLimit($request, $action)
    {
        // 10 запросов в минуту для генерации яблок
        return [10, 60];
    }

    /**
     * Загружает текущий лимит запросов пользователя
     *
     * @param \yii\web\Request $request
     * @param \yii\base\Action $action
     * @return array Массив [количество оставшихся запросов, timestamp последнего запроса]
     */
    public function loadAllowance($request, $action)
    {
        return [$this->allowance, $this->allowance_updated_at];
    }

    /**
     * Сохраняет текущий лимит запросов пользователя
     *
     * @param \yii\web\Request $request
     * @param \yii\base\Action $action
     * @param int $allowance Количество оставшихся запросов
     * @param int $timestamp Timestamp последнего запроса
     */
    public function saveAllowance($request, $action, $allowance, $timestamp)
    {
        $this->allowance = $allowance;
        $this->allowance_updated_at = $timestamp;
        $this->save(false);
    }

    /**
     * Генерация JWT токена для пользователя
     *
     * @param int $expirationTime Время жизни токена в секундах (по умолчанию 7 дней)
     * @return string JWT токен
     */
    public function generateJwtToken(int $expirationTime = 604800): string
    {
        $jwtSecret = Yii::$app->params['jwtSecret'] ?? 'your-secret-key-change-this-in-production';

        $payload = [
            'iss' => Yii::$app->request->hostInfo ?? 'apple-api', // Издатель
            'aud' => Yii::$app->request->hostInfo ?? 'apple-api', // Аудитория
            'iat' => time(), // Время создания
            'exp' => time() + $expirationTime, // Время истечения
            'uid' => $this->id, // ID пользователя
            'username' => $this->username, // Имя пользователя
        ];

        $token = JWT::encode($payload, $jwtSecret, 'HS256');

        Yii::info("Generated JWT token for user #{$this->id} ({$this->username})", 'jwt');

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
            $jwtSecret = Yii::$app->params['jwtSecret'] ?? 'your-secret-key-change-this-in-production';
            return JWT::decode($token, new Key($jwtSecret, 'HS256'));
        } catch (\Exception $e) {
            Yii::error("JWT validation failed: {$e->getMessage()}", 'jwt');
            return null;
        }
    }
}
