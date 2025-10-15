<?php
namespace Core;

abstract class Model
{
    protected \PDO $db;

    public function __construct()
    {
        $this->db = Database::pdo();
    }
}
