<?php
namespace Models;

use Core\Model;
use PDO;

class CostsRecurrence extends Model
{
    public static function addMonthSafe(string $date): string
    {
        $dt = new \DateTime($date);
        $day = (int)$dt->format('d');
        $dt->modify('first day of next month');
        $last = (int)$dt->format('t');
        $dt->setDate((int)$dt->format('Y'), (int)$dt->format('m'), min($day, $last));
        return $dt->format('Y-m-d');
    }

    /** Generate due occurrences up to $today (inclusive). Returns number of generated cost rows. */
    public function runDue(string $today): int
    {
        $today = date('Y-m-d', strtotime($today));
        $sql = "SELECT * FROM custos WHERE recorrente_ativo = 1 AND recorrente_tipo <> 'none' AND recorrente_proxima_data IS NOT NULL AND recorrente_proxima_data <= :d ORDER BY recorrente_proxima_data ASC, id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':d' => $today]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $generated = 0;
        $cost = new Cost();
        foreach ($rows as $r) {
            $type = $r['recorrente_tipo'];
            $nextDate = $r['recorrente_proxima_data'];
            if (!$nextDate) { continue; }

            $desc = (string)($r['descricao'] ?? '');
            $parcelasTotal = $r['parcelas_total'] !== null ? (int)$r['parcelas_total'] : null;
            $parcelaAtual = $r['parcela_atual'] !== null ? (int)$r['parcela_atual'] : null;

            if ($type === 'installments') {
                // Next parcela is parcela_atual + 1
                $num = ($parcelaAtual ?? 0) + 1;
                $suffix = ' (Parcela ' . $num . '/' . ($parcelasTotal ?: $num) . ')';
                $descOut = ($desc === '' ? '' : ($desc . '')) . $suffix;
                // Create the parcela cost row preserving original value type/fields
                $cost->createFull([
                    'data' => $nextDate,
                    'categoria' => $r['categoria'],
                    'descricao' => $descOut,
                    'valor_usd' => (float)$r['valor_usd'],
                    'valor_tipo' => $r['valor_tipo'] ?? 'usd',
                    'valor_brl' => (($r['valor_tipo'] ?? 'usd') === 'brl') ? (float)($r['valor_brl'] ?? 0) : null,
                    'valor_percent' => (($r['valor_tipo'] ?? 'usd') === 'percent') ? (float)($r['valor_percent'] ?? 0) : null,
                    'recorrente_tipo' => 'none',
                    'recorrente_ativo' => 0,
                    'recorrente_proxima_data' => null,
                    'parcelas_total' => null,
                    'parcela_atual' => null,
                ]);
                $generated++;
                // Update master: increment parcela_atual and schedule next or deactivate
                $done = $parcelasTotal !== null && $num >= $parcelasTotal;
                $fields = [
                    'parcela_atual' => $num,
                    'recorrente_ativo' => $done ? 0 : 1,
                    'recorrente_proxima_data' => $done ? null : self::addMonthSafe($nextDate),
                ];
                $cost->updateRecurrence((int)$r['id'], $fields);
            } else {
                // weekly/monthly/yearly: clone cost row for nextDate, preserving original value type/fields
                $cost->createFull([
                    'data' => $nextDate,
                    'categoria' => $r['categoria'],
                    'descricao' => $desc,
                    'valor_usd' => (float)$r['valor_usd'],
                    'valor_tipo' => $r['valor_tipo'] ?? 'usd',
                    'valor_brl' => (($r['valor_tipo'] ?? 'usd') === 'brl') ? (float)($r['valor_brl'] ?? 0) : null,
                    'valor_percent' => (($r['valor_tipo'] ?? 'usd') === 'percent') ? (float)($r['valor_percent'] ?? 0) : null,
                    'recorrente_tipo' => 'none',
                    'recorrente_ativo' => 0,
                    'recorrente_proxima_data' => null,
                    'parcelas_total' => null,
                    'parcela_atual' => null,
                ]);
                $generated++;
                // Schedule next occurrence
                if ($type === 'weekly') {
                    $newNext = date('Y-m-d', strtotime($nextDate . ' +7 days'));
                } elseif ($type === 'monthly') {
                    $newNext = self::addMonthSafe($nextDate);
                } else { // yearly
                    $newNext = date('Y-m-d', strtotime($nextDate . ' +1 year'));
                }
                $cost->updateRecurrence((int)$r['id'], ['recorrente_proxima_data' => $newNext]);
            }
        }
        return $generated;
    }
}
