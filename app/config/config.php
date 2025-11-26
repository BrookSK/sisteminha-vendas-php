<?php
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

// >>> AQUI: bancos de PRODUTOS separados por ambiente
$dbProductsProd = [
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'sisteminha_produtos_prod', // ex: produtos da produção
    'username' => 'sistema_vendas_produtos_prod',      // ou outro usuário, se preferir
    'password' => 'BZRVdb202503',
    'charset' => 'utf8mb4',
];

$dbProductsDev = [
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'sisteminha_produtos_dev', // ex: produtos de desenvolvimento
    'username' => 'sistema_vendas_produtos',         // ou outro usuário, se preferir
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

    // Banco exclusivo de produtos do simulador, também separado por env
    'db_products' => ($appEnv === 'prod') ? $dbProductsProd : $dbProductsDev,
];