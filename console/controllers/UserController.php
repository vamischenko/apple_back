<?php

namespace console\controllers;

use common\models\User;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Управление пользователями
 */
class UserController extends Controller
{
    /**
     * Создать нового пользователя
     *
     * @param string $username Имя пользователя
     * @param string $email Email пользователя
     * @param string $password Пароль
     * @return int Exit code
     */
    public function actionCreate($username, $email, $password)
    {
        $user = new User();
        $user->username = $username;
        $user->email = $email;
        $user->setPassword($password);
        $user->generateAuthKey();
        $user->generateEmailVerificationToken();
        $user->status = User::STATUS_ACTIVE;

        if ($user->save()) {
            $this->stdout("✓ Пользователь успешно создан!\n", Console::FG_GREEN);
            $this->stdout("Логин: {$username}\n");
            $this->stdout("Email: {$email}\n");
            $this->stdout("Пароль: {$password}\n");
            return ExitCode::OK;
        } else {
            $this->stderr("✗ Ошибка при создании пользователя:\n", Console::FG_RED);
            foreach ($user->errors as $attribute => $errors) {
                foreach ($errors as $error) {
                    $this->stderr("  - {$attribute}: {$error}\n", Console::FG_RED);
                }
            }
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Создать тестового администратора (admin/admin123)
     *
     * @return int Exit code
     */
    public function actionCreateAdmin()
    {
        return $this->actionCreate('admin', 'admin@example.com', 'admin123');
    }
}
