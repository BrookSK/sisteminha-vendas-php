<?php
namespace Core;

class Auth
{
    public static function init(): void
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(16));
        }
    }

    public static function csrf(): string
    {
        return $_SESSION['_csrf'] ?? '';
    }

    public static function checkCsrf(string $token): bool
    {
        return hash_equals($_SESSION['_csrf'] ?? '', $token);
    }

    public static function login(array $user): void
    {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'] ?? 'User',
            'role' => $user['role'] ?? 'seller',
        ];
    }

    // Alias para compatibilidade com chamadas existentes
    public static function loginAs(array $user): void
    {
        self::login($user);
    }

    public static function logout(): void
    {
        unset($_SESSION['user']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function check(): bool
    {
        return !empty($_SESSION['user']);
    }
}
