<?php
namespace Models;

use Core\Config;
use Core\Database;
use ZipArchive;

class BackupService
{
    private string $backupDir;

    public function __construct()
    {
        $base = dirname(__DIR__, 2); // project root
        $this->backupDir = $base . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'backups';
        if (!is_dir($this->backupDir)) {
            @mkdir($this->backupDir, 0775, true);
        }
    }

    public function backupDir(): string
    {
        return $this->backupDir;
    }

    public function listBackups(): array
    {
        $files = glob($this->backupDir . DIRECTORY_SEPARATOR . 'backup_*.zip') ?: [];
        $items = [];
        foreach ($files as $f) {
            $items[] = [
                'name' => basename($f),
                'path' => $f,
                'size' => filesize($f) ?: 0,
                'mtime' => filemtime($f) ?: 0,
            ];
        }
        usort($items, fn($a,$b) => $b['mtime'] <=> $a['mtime']);
        return $items;
    }

    public function createFullBackup(): array
    {
        $timestamp = date('Ymd_His');
        $zipPath = $this->backupDir . DIRECTORY_SEPARATOR . "backup_{$timestamp}.zip";
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Não foi possível criar o arquivo ZIP de backup.');
        }

        // 1) Dump do banco de dados
        $dbSql = $this->dumpDatabase();
        $zip->addFromString('db.sql', $dbSql);

        // 2) Arquivos do sistema (excluindo alguns diretórios)
        $root = dirname(__DIR__, 2); // project root
        $this->addDirectoryToZip($zip, $root, 'files/', [
            'vendor', '.git', 'node_modules', 'storage/backups', 'storage/cache'
        ]);

        $zip->close();

        return [
            'name' => basename($zipPath),
            'path' => $zipPath,
        ];
    }

    private function addDirectoryToZip(ZipArchive $zip, string $basePath, string $zipPrefix = '', array $excludeDirs = []): void
    {
        $basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
        $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS));
        foreach ($rii as $file) {
            /** @var \SplFileInfo $file */
            $pathName = $file->getPathname();
            $rel = ltrim(str_replace($basePath, '', $pathName), DIRECTORY_SEPARATOR);
            // Exclude directories
            $parts = explode(DIRECTORY_SEPARATOR, $rel);
            $skip = false;
            foreach ($excludeDirs as $ex) {
                $exParts = explode('/', str_replace('\\', '/', $ex));
                $sub = implode('/', array_slice($parts, 0, count($exParts)));
                if ($sub === implode('/', $exParts)) { $skip = true; break; }
            }
            if ($skip) continue;
            // Add files only
            if ($file->isFile()) {
                $localname = $zipPrefix . str_replace(DIRECTORY_SEPARATOR, '/', $rel);
                $zip->addFile($pathName, $localname);
            }
        }
    }

    private function dumpDatabase(): string
    {
        $host = Config::get('db.host');
        $port = (int)Config::get('db.port', 3306);
        $db   = Config::get('db.database');
        $user = Config::get('db.username');
        $pass = Config::get('db.password');

        // Prefer mysqldump if available
        $mysqldump = $this->findBinary('mysqldump');
        if ($mysqldump) {
            $cmd = sprintf('"%s" --host=%s --port=%d --user=%s --password=%s --single-transaction --quick --routines --triggers %s 2>&1',
                $mysqldump,
                escapeshellarg($host),
                $port,
                escapeshellarg($user),
                escapeshellarg($pass),
                escapeshellarg($db)
            );
            $out = shell_exec($cmd);
            if (is_string($out) && str_contains($out, '-- Dump completed') || strlen($out) > 100) {
                return $out;
            }
            // fallthrough if failed
        }

        // Fallback: gerar dump via PDO (simplificado)
        $pdo = Database::pdo();
        $sql = [];
        $sql[] = "SET FOREIGN_KEY_CHECKS=0;";
        $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $row = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
            $create = $row['Create Table'] ?? '';
            if ($create) {
                $sql[] = "DROP TABLE IF EXISTS `{$table}`;";
                $sql[] = $create . ';';
            }
            $stmt = $pdo->query("SELECT * FROM `{$table}`");
            while ($r = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $cols = array_map(fn($c) => "`" . str_replace("`", "``", $c) . "`", array_keys($r));
                $vals = array_map(function($v) use ($pdo) {
                    if ($v === null) return 'NULL';
                    return $pdo->quote((string)$v);
                }, array_values($r));
                $sql[] = "INSERT INTO `{$table}` (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ");";
            }
        }
        $sql[] = "SET FOREIGN_KEY_CHECKS=1;";
        return implode("\n", $sql) . "\n";
    }

    private function findBinary(string $name): ?string
    {
        $paths = explode(PATH_SEPARATOR, getenv('PATH') ?: '');
        foreach ($paths as $p) {
            $cand = rtrim($p, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name . (stripos(PHP_OS, 'WIN') === 0 ? '.exe' : '');
            if (is_file($cand) && is_executable($cand)) return $cand;
        }
        return null;
    }

    public function enforceRetention(int $keep): void
    {
        if ($keep <= 0) return;
        $items = $this->listBackups();
        $toDelete = array_slice($items, $keep);
        foreach ($toDelete as $it) {
            @unlink($it['path']);
        }
    }

    public function restoreDatabaseFromZip(string $zipFile): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== true) {
            throw new \RuntimeException('Não foi possível abrir o arquivo de backup.');
        }
        $content = $zip->getFromName('db.sql');
        $zip->close();
        if ($content === false) {
            throw new \RuntimeException('Arquivo db.sql não encontrado no backup.');
        }
        $pdo = Database::pdo();
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach ($this->splitSqlStatements($content) as $stmt) {
            if (trim($stmt) === '') continue;
            $pdo->exec($stmt);
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    private function splitSqlStatements(string $sql): array
    {
        // Basic split on semicolons not inside quotes
        $statements = [];
        $buffer = '';
        $inString = false;
        $stringChar = '';
        $len = strlen($sql);
        for ($i=0; $i<$len; $i++) {
            $ch = $sql[$i];
            $buffer .= $ch;
            if (($ch === "'" || $ch === '"') && ($i === 0 || $sql[$i-1] !== '\\')) {
                if (!$inString) { $inString = true; $stringChar = $ch; }
                elseif ($stringChar === $ch) { $inString = false; $stringChar = ''; }
            }
            if ($ch === ';' && !$inString) {
                $statements[] = $buffer;
                $buffer = '';
            }
        }
        if (trim($buffer) !== '') $statements[] = $buffer;
        return $statements;
    }
}
