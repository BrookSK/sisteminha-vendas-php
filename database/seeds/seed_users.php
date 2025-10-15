<?php
// Simple seed script to create default users if they don't exist
// Usage: php database/seeds/seed_users.php

$config = require __DIR__ . '/../../app/config/config.php';
$db = $config['db'];
$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $db['host'], $db['port'], $db['database'], $db['charset']);

try {
    $pdo = new PDO($dsn, $db['username'], $db['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $e) {
    fwrite(STDERR, "[seed] Failed to connect to DB: {$e->getMessage()}\n");
    exit(1);
}

function ensure_user(PDO $pdo, string $name, string $email, string $password, string $role, int $ativo = 1): void {
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $row = $stmt->fetch();
    if ($row) {
        echo "[seed] User already exists: {$email}\n";
        return;
    }
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('INSERT INTO usuarios (name, email, password_hash, role, ativo, created_at) VALUES (:name, :email, :hash, :role, :ativo, NOW())');
    $stmt->execute([':name'=>$name, ':email'=>$email, ':hash'=>$hash, ':role'=>$role, ':ativo'=>$ativo]);
    echo "[seed] Created user: {$email} (role={$role})\n";
}

// Defaults
$adminEmail = 'admin@example.com';
$adminPass = 'admin123';
ensure_user($pdo, 'Administrador', $adminEmail, $adminPass, 'admin', 1);

$organicEmail = 'organic@example.com';
$organicPass = 'organico123';
ensure_user($pdo, 'Vendas Orgânicas', $organicEmail, $organicPass, 'organic', 1);

echo "[seed] Done.\n";
