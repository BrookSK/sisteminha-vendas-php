<?php
namespace Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $pdo = null;
    private static ?PDO $pdoProducts = null;

    public static function init(): void
    {
        if (self::$pdo) return;
        $host = Config::get('db.host');
        $port = (int)Config::get('db.port');
        $db   = Config::get('db.database');
        $user = Config::get('db.username');
        $pass = Config::get('db.password');
        $charset = Config::get('db.charset', 'utf8mb4');
        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        try {
            self::$pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            exit('Erro de conexão ao banco: ' . ($e->getMessage()));
        }
    }

    public static function pdo(): PDO
    {
        if (!self::$pdo) self::init();
        return self::$pdo;
    }

    /**
     * Conexão dedicada ao banco de dados exclusivo de produtos do simulador.
     *
     * Usa as mesmas credenciais/host/porta do banco principal, mas permite
     * configurar um "db_products.database" diferente em config.php.
     */
    public static function pdoProducts(): PDO
    {
        if (self::$pdoProducts) {
            return self::$pdoProducts;
        }

        $host = Config::get('db_products.host', Config::get('db.host'));
        $port = (int)Config::get('db_products.port', Config::get('db.port'));
        $db   = Config::get('db_products.database', Config::get('db.database'));
        $user = Config::get('db_products.username', Config::get('db.username'));
        $pass = Config::get('db_products.password', Config::get('db.password'));
        $charset = Config::get('db_products.charset', Config::get('db.charset', 'utf8mb4'));

        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            self::$pdoProducts = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            exit('Erro de conexão ao banco de produtos: ' . ($e->getMessage()));
        }

        return self::$pdoProducts;
    }
}
