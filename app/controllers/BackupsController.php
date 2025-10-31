<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\BackupService;
use Models\Setting;

class BackupsController extends Controller
{
    public function index(): void
    {
        $this->requireRole(['admin']);
        $svc = new BackupService();
        $set = new Setting();
        $recurrence = (string)$set->get('backup_recurrence', 'mensal'); // mensal|semanal|diario
        $time = (string)$set->get('backup_time', '02:00');
        $retention = (int)$set->get('backup_retention', '3');
        $items = $svc->listBackups();
        $cronToken = (string)$set->get('backup_cron_token', '');
        $this->render('backups/index', [
            'title' => 'Backups do Sistema',
            'backups' => $items,
            'recurrence' => $recurrence,
            'time' => $time,
            'retention' => $retention,
            'backup_dir' => $svc->backupDir(),
            'cron_token' => $cronToken,
        ]);
    }

    public function run(): void
    {
        $this->requireRole(['admin']);
        $this->csrfCheck();
        $svc = new BackupService();
        $set = new Setting();
        $retention = (int)$set->get('backup_retention', '3');
        try {
            $res = $svc->createFullBackup();
            $svc->enforceRetention(max(1, $retention));
            $this->flash('success', 'Backup criado: ' . $res['name']);
        } catch (\Throwable $e) {
            $this->flash('danger', 'Falha ao criar backup: ' . $e->getMessage());
        }
        $this->redirect('/admin/backups');
    }

    public function delete(): void
    {
        $this->requireRole(['admin']);
        $this->csrfCheck();
        $name = basename((string)($_POST['name'] ?? ''));
        if ($name === '') { $this->redirect('/admin/backups'); }
        $svc = new BackupService();
        $path = $svc->backupDir() . DIRECTORY_SEPARATOR . $name;
        if (is_file($path)) {
            @unlink($path);
            $this->flash('success', 'Backup excluído: ' . $name);
        }
        $this->redirect('/admin/backups');
    }

    public function restore(): void
    {
        $this->requireRole(['admin']);
        $this->csrfCheck();
        $name = basename((string)($_POST['name'] ?? ''));
        $what = (string)($_POST['what'] ?? 'db'); // db|files
        $svc = new BackupService();
        $path = $svc->backupDir() . DIRECTORY_SEPARATOR . $name;
        if (!is_file($path)) { $this->redirect('/admin/backups'); }
        if ($what === 'db') {
            try {
                $svc->restoreDatabaseFromZip($path);
                $this->flash('success', 'Banco de dados restaurado a partir do backup: ' . $name);
            } catch (\Throwable $e) {
                $this->flash('danger', 'Falha ao restaurar o banco: ' . $e->getMessage());
            }
        } else {
            $this->flash('warning', 'Para restaurar arquivos do sistema, utilize o Git (revert no Pull Request correspondente no GitHub). Este sistema apenas armazena o backup de arquivos.');
        }
        $this->redirect('/admin/backups');
    }

    public function saveSettings(): void
    {
        $this->requireRole(['admin']);
        $this->csrfCheck();
        $recurrence = (string)($_POST['backup_recurrence'] ?? 'mensal');
        if (!in_array($recurrence, ['mensal','semanal','diario'], true)) { $recurrence = 'mensal'; }
        $time = (string)($_POST['backup_time'] ?? '02:00');
        if (!preg_match('/^\d{2}:\d{2}$/', $time)) { $time = '02:00'; }
        $retention = (int)($_POST['backup_retention'] ?? 3);
        if ($retention < 1) $retention = 1; if ($retention > 50) $retention = 50;
        $cronToken = trim((string)($_POST['backup_cron_token'] ?? ''));
        if ($cronToken !== '' && strlen($cronToken) < 8) { $cronToken = ''; }
        $set = new Setting();
        $set->set('backup_recurrence', $recurrence);
        $set->set('backup_time', $time);
        $set->set('backup_retention', (string)$retention);
        if ($cronToken !== '') { $set->set('backup_cron_token', $cronToken); }
        $this->flash('success', 'Configurações de backup salvas.');
        $this->redirect('/admin/backups');
    }

    public function cronRun(): void
    {
        // Public endpoint protected by token
        $token = (string)($_GET['token'] ?? '');
        $set = new Setting();
        $expected = (string)$set->get('backup_cron_token', '');
        if ($expected === '' || !hash_equals($expected, $token)) {
            http_response_code(403);
            echo 'forbidden';
            return;
        }
        $recurrence = (string)$set->get('backup_recurrence', 'mensal');
        $time = (string)$set->get('backup_time', '02:00');
        $lastRun = (int)$set->get('backup_last_run', '0');
        if (!$this->isDue($recurrence, $time, $lastRun)) {
            echo 'not-due';
            return;
        }
        $svc = new BackupService();
        $retention = (int)$set->get('backup_retention', '3');
        try {
            $svc->createFullBackup();
            $svc->enforceRetention(max(1, $retention));
            $set->set('backup_last_run', (string)time());
            echo 'ok';
        } catch (\Throwable $e) {
            http_response_code(500);
            echo 'error: ' . $e->getMessage();
        }
    }

    private function isDue(string $recurrence, string $time, int $lastRunTs): bool
    {
        $now = new \DateTimeImmutable('now');
        [$hh,$mm] = array_map('intval', explode(':', $time . ':00'));
        $todayAt = $now->setTime($hh, $mm, 0);
        $last = ($lastRunTs > 0) ? (new \DateTimeImmutable('@' . $lastRunTs))->setTimezone($now->getTimezone()) : null;
        switch ($recurrence) {
            case 'diario':
                // due once per day after configured time
                if ($now < $todayAt) return false;
                if (!$last) return true;
                return $last->format('Y-m-d') < $now->format('Y-m-d');
            case 'semanal':
                if ($now < $todayAt) return false;
                if (!$last) return true;
                $diff = $now->getTimestamp() - $last->getTimestamp();
                return $diff >= 6*24*3600; // ~7 days window; allows any weekday
            case 'mensal':
            default:
                if ($now < $todayAt) return false;
                if (!$last) return true;
                // if not run in current month yet
                return $last->format('Y-m') < $now->format('Y-m');
        }
    }
}
