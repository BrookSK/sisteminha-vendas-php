<?php
// Configurações globais do sistema
// Define env padrão aqui; pode ser sobrescrito por variável de ambiente APP_ENV
$appEnv = getenv('APP_ENV') ?: 'dev';

$app = [
    'name' => 'Sistema de Vendas Braziliana',
    'base_url' => '/', // ajuste se não estiver na raiz (ex: /sisteminha-vendas-php/)
    'env' => $appEnv, // dev|prod
    'csrf_key' => 'change-this-secret-key',
];

$dbProd = [
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'sistema_vendas_prod',
    'username' => 'sistema_vendas_prod',
    'password' => 'BZRVdb202503',
    'charset' => 'utf8mb4',
];

$dbDev = [
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'sistema_vendas',
    'username' => 'sistema_vendas',
    'password' => 'BZRVdb202503',
    'charset' => 'utf8mb4',
];

return [
    'app' => $app,
    'db' => ($appEnv === 'prod') ? $dbProd : $dbDev,
    'security' => [
        'session_name' => 'sv_session',
        'session_lifetime' => 28800, // 8 hours
        'cookie_secure' => false, // true se usar HTTPS
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ],

    // NOVO: banco exclusivo de produtos do simulador
    'db_products' => [
        // se for o mesmo host/usuário/porta, pode só mudar o database
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'sisteminha_produtos', // <- novo schema
        'username' => 'user',
        'password' => 'senha',
        'charset' => 'utf8mb4',
    ],
];
