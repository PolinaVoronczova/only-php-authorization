<?php

namespace Controllers\User;

use Controllers\User\User;
class UserValidator
{
    private ?array $errors = null;

    private $user;

    public function __construct(\PDO $pdo) 
    {
        $this->user = new User($pdo);
    }

    public function uniqueName(string $name)
    {
        if ($this->user->findBy('name', $name, false)) {
            $this->errors['name'][] = 'Такое имя пользователя уже зарегистрированно.';
        }

        return $this;
    }

    public function uniquePhone(string $phone)
    {
        if ($this->user->findBy('phone', $phone, false)) {
            $this->errors['phone'][] = 'Такой номер телефона уже зарегистрирован.';
        }

        return $this;
    }

    public function validPhone(string $phone)
    {
        $pattern = '/^(\+7|8)[\s\-]?\(?\d{3}\)?[\s\-]?\d{3}[\s\-]?\d{2}[\s\-]?\d{2}$/';
        if (!preg_match($pattern, $phone)) {
            $this->errors['phone'][] = 'Некорректный номер.';
        }

        return $this;
    }

    public function uniqueEmail(string $email)
    {
        if ($this->user->findBy('email', $email, false)) {
            $this->errors['email'][] = 'Такая почта уже зарегистрированна.';
        }

        return $this;
    }
    public function validEmail(string $email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors['email'][] = 'Некорректная почта.';
        }

        return $this;
    }
    public function validPassword(string $password, string $comfimPassword)
    {
        if ($password !== $comfimPassword) {
            $this->errors['password'][] = 'Введенные пароли отличаются друг от друга.';
        }
        return $this;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}