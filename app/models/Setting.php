<?php
namespace Models;

use Core\Model;
use PDO;

class Setting extends Model
{
    public function get(string $key, $default = null)
    {
        $stmt = $this->db->prepare('SELECT value FROM settings WHERE `key` = :k LIMIT 1');
        $stmt->execute([':k' => $key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['value'] ?? $default;
    }

    public function set(string $key, string $value): void
    {
        $stmt = $this->db->prepare('INSERT INTO settings(`key`, `value`) VALUES (:k, :v)
            ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)');
        $stmt->execute([':k' => $key, ':v' => $value]);
    }

    /** Returns [from, to] dates (Y-m-d) for the current operational period. */
    public function currentPeriod(): array
    {
        $from = (string)$this->get('current_period_start', '');
        $to = (string)$this->get('current_period_end', '');
        if ($from && $to) {
            return [$from, $to];
        }
        // Default rolling window: 10th -> 9th containing today
        $today = new \DateTimeImmutable('today');
        $day = (int)$today->format('j');
        if ($day >= 10) {
            $fromDate = $today->setDate((int)$today->format('Y'), (int)$today->format('n'), 10);
            // to = 9th of next month
            $firstNext = $today->modify('first day of next month');
            $toDate = $firstNext->setDate((int)$firstNext->format('Y'), (int)$firstNext->format('n'), 9);
        } else {
            // from = 10th of previous month
            $firstPrev = $today->modify('first day of previous month');
            $fromDate = $firstPrev->setDate((int)$firstPrev->format('Y'), (int)$firstPrev->format('n'), 10);
            // to = 9th of current month
            $toDate = $today->setDate((int)$today->format('Y'), (int)$today->format('n'), 9);
        }
        return [$fromDate->format('Y-m-d'), $toDate->format('Y-m-d')];
    }

    /** Returns [from, to] datetimes (Y-m-d H:i:s) for the current period boundaries. */
    public function currentPeriodDateTime(): array
    {
        [$from, $to] = $this->currentPeriod();
        return [$from . ' 00:00:00', $to . ' 23:59:59'];
    }

    /** Checks if a Y-m-d date string is within the current period (inclusive). */
    public function isInCurrentPeriodDate(string $dateYmd): bool
    {
        if ($dateYmd === '') return false;
        [$from, $to] = $this->currentPeriod();
        return ($dateYmd >= $from) && ($dateYmd <= $to);
    }

    /** Checks if a Y-m-d H:i:s datetime string is within the current period (inclusive). */
    public function isInCurrentPeriodDateTime(string $dateTime): bool
    {
        if ($dateTime === '') return false;
        [$fromDt, $toDt] = $this->currentPeriodDateTime();
        return ($dateTime >= $fromDt) && ($dateTime <= $toDt);
    }
}
