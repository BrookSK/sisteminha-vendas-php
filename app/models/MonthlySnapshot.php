<?php
namespace Models;

use Core\Model;
use PDO;

class MonthlySnapshot extends Model
{
    public function findByPeriod(string $from, string $to): array
    {
        $stmt = $this->db->prepare('SELECT * FROM monthly_snapshots WHERE period_from = :f AND period_to = :t ORDER BY scope, seller_id');
        $stmt->execute([':f' => $from, ':t' => $to]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function hasCompanyRow(string $from, string $to): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM monthly_snapshots WHERE period_from = :f AND period_to = :t AND scope = 'company' LIMIT 1");
        $stmt->execute([':f' => $from, ':t' => $to]);
        return (bool)$stmt->fetchColumn();
    }

    public function insertSnapshotRow(array $data): void
    {
        $sql = 'INSERT INTO monthly_snapshots (
            period_key, period_from, period_to, scope, seller_id, seller_name, seller_role,
            active_users, sales_count, atendimentos, atendimentos_concluidos,
            bruto_total_usd, liquido_total_usd, liquido_apurado_usd,
            custos_usd, custos_percentuais, lucro_liquido_usd,
            comissao_usd, company_cash_usd, company_cash_brl,
            prolabore_pct, prolabore_usd,
            frozen_by_user_id, extra_json, created_at
        ) VALUES (
            :period_key, :period_from, :period_to, :scope, :seller_id, :seller_name, :seller_role,
            :active_users, :sales_count, :atendimentos, :atendimentos_concluidos,
            :bruto_total_usd, :liquido_total_usd, :liquido_apurado_usd,
            :custos_usd, :custos_percentuais, :lucro_liquido_usd,
            :comissao_usd, :company_cash_usd, :company_cash_brl,
            :prolabore_pct, :prolabore_usd,
            :frozen_by_user_id, :extra_json, NOW()
        )';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':period_key' => $data['period_key'] ?? '',
            ':period_from' => $data['period_from'] ?? null,
            ':period_to' => $data['period_to'] ?? null,
            ':scope' => $data['scope'] ?? 'company',
            ':seller_id' => $data['seller_id'] ?? null,
            ':seller_name' => $data['seller_name'] ?? null,
            ':seller_role' => $data['seller_role'] ?? null,
            ':active_users' => $data['active_users'] ?? null,
            ':sales_count' => $data['sales_count'] ?? null,
            ':atendimentos' => $data['atendimentos'] ?? null,
            ':atendimentos_concluidos' => $data['atendimentos_concluidos'] ?? null,
            ':bruto_total_usd' => $data['bruto_total_usd'] ?? 0,
            ':liquido_total_usd' => $data['liquido_total_usd'] ?? 0,
            ':liquido_apurado_usd' => $data['liquido_apurado_usd'] ?? 0,
            ':custos_usd' => $data['custos_usd'] ?? 0,
            ':custos_percentuais' => $data['custos_percentuais'] ?? 0,
            ':lucro_liquido_usd' => $data['lucro_liquido_usd'] ?? 0,
            ':comissao_usd' => $data['comissao_usd'] ?? 0,
            ':company_cash_usd' => $data['company_cash_usd'] ?? 0,
            ':company_cash_brl' => $data['company_cash_brl'] ?? 0,
            ':prolabore_pct' => $data['prolabore_pct'] ?? 0,
            ':prolabore_usd' => $data['prolabore_usd'] ?? 0,
            ':frozen_by_user_id' => $data['frozen_by_user_id'] ?? null,
            ':extra_json' => $data['extra_json'] ?? null,
        ]);
    }

    public function loadCompanyForPeriod(string $from, string $to): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM monthly_snapshots WHERE period_from = :f AND period_to = :t AND scope = 'company' LIMIT 1");
        $stmt->execute([':f' => substr($from,0,10), ':t' => substr($to,0,10)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function loadSellersForPeriod(string $from, string $to): array
    {
        $stmt = $this->db->prepare("SELECT * FROM monthly_snapshots WHERE period_from = :f AND period_to = :t AND scope = 'seller' ORDER BY seller_name");
        $stmt->execute([':f' => substr($from,0,10), ':t' => substr($to,0,10)]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function freezePeriod(string $from, string $to, int $userId = 0, string $source = 'manual'): bool
    {
        $from = substr($from, 0, 10);
        $to = substr($to, 0, 10);
        if ($this->hasCompanyRow($from, $to)) {
            return false;
        }

        $periodKey = substr($from, 0, 7);

        $comm = new \Models\Commission();
        $report = new \Models\Report();
        $costModel = new \Models\Cost();
        $userModel = new \Models\User();
        $setting = new \Models\Setting();

        $fromTs = $from . ' 00:00:00';
        $toTs = $to . ' 23:59:59';
        $calc = $comm->computeRange($fromTs, $toTs);
        $items = $calc['items'] ?? [];
        $team = $calc['team'] ?? [];

        $newComm = new \Models\NewCommission();
        $newCalc = $newComm->computeRange($fromTs, $toTs, null);
        $newCompany = $newCalc['company'] ?? null;
        $newItems = $newCalc['items'] ?? [];
        $newByUser = [];
        foreach ($newItems as $it) {
            $uid = (int)($it['seller_id'] ?? 0);
            if ($uid <= 0) continue;
            $newByUser[$uid] = $it;
        }

        $summary = $report->summary($from, $to, null);
        $bySeller = $report->bySeller($from, $to);
        $prolaborePct = (float)$costModel->sumProLaborePercentInPeriod($from, $to);
        $rate = (float)$setting->get('usd_rate', '5.83');
        $metaEquipeBrl = (float)($team['meta_equipe_brl'] ?? 0);
        $teamBrutoBrl = (float)($team['team_bruto_total_brl'] ?? 0);
        $metaAtingida = $metaEquipeBrl > 0 && $teamBrutoBrl >= $metaEquipeBrl;

        $activeUsers = array_filter($userModel->allBasic(), function($u) {
            $r = (string)($u['role'] ?? '');
            return (int)($u['ativo'] ?? 0) === 1 && $r !== 'admin';
        });
        $activeCount = count($activeUsers);

        $sumCommissionsUsd = (float)($team['sum_commissions_usd'] ?? 0);
        $sumCommissionsBrl = (float)($team['sum_commissions_brl'] ?? 0);
        $companyCashUsd = (float)($team['company_cash_usd'] ?? 0);
        $companyCashBrl = (float)($team['company_cash_brl'] ?? 0);
        $teamCostTotal = (float)($team['team_cost_total'] ?? 0);
        $costRate = (float)($team['team_cost_settings_rate'] ?? 0);
        $metaEquipeUsd = 50000.0;

        $companyRow = [
            'period_key' => $periodKey,
            'period_from' => $from,
            'period_to' => $to,
            'scope' => 'company',
            'seller_id' => null,
            'seller_name' => null,
            'seller_email' => null,
            'seller_role' => null,
            'seller_ativo' => null,
            'active_users' => $activeCount,
            'sales_count' => (int)$report->countInPeriodAll($from, $to, null),
            'atendimentos' => (int)($summary['atendimentos'] ?? 0),
            'atendimentos_concluidos' => (int)($summary['atendimentos_concluidos'] ?? 0),
            'bruto_total_usd' => (float)($summary['total_bruto_usd'] ?? 0),
            'liquido_total_usd' => (float)($summary['total_liquido_usd'] ?? 0),
            'liquido_apurado_usd' => (float)($team['sum_rateado_usd'] ?? 0),
            'custos_usd' => (float)($summary['custos_usd'] ?? 0),
            'custos_percentuais' => (float)($summary['custos_percentuais_usd'] ?? 0),
            'custo_config_rate' => $costRate,
            'team_cost_total_usd' => $teamCostTotal,
            'lucro_liquido_usd' => (float)($summary['lucro_liquido_usd'] ?? 0),
            'comissao_usd' => $sumCommissionsUsd,
            'comissao_brl' => $sumCommissionsBrl,
            'company_cash_usd' => $companyCashUsd,
            'company_cash_brl' => $companyCashBrl,
            'usd_rate' => $rate,
            'meta_equipe_usd' => $metaEquipeUsd,
            'meta_equipe_brl' => $metaEquipeBrl,
            'meta_atingida' => $metaAtingida ? 1 : 0,
            'prolabore_pct' => $prolaborePct,
            'prolabore_usd' => ((float)($team['team_bruto_total'] ?? 0)) * ($prolaborePct / 100.0),
            'frozen_by_user_id' => $userId ?: null,
            'frozen_by_user_name' => null,
            'frozen_source' => $source,
            'extra_json' => $newCompany ? json_encode([
                'new_commission' => [
                    'bruto_total_brl' => (float)($newCompany['bruto_total_brl'] ?? 0),
                    'liquido_novo_brl' => (float)($newCompany['liquido_novo_brl'] ?? 0),
                    'comissao_brl' => (float)($newCompany['comissao_brl'] ?? 0),
                ],
            ]) : null,
        ];

        if ($userId > 0) {
            $u = $userModel->findById($userId);
            if ($u) {
                $companyRow['frozen_by_user_name'] = (string)($u['name'] ?? $u['email'] ?? '');
            }
        }

        $this->insertSnapshotRow($companyRow);

        $bySellerMap = [];
        foreach ($bySeller as $row) {
            $uid = (int)($row['usuario_id'] ?? 0);
            if ($uid <= 0) continue;
            $bySellerMap[$uid] = $row;
        }

        $itemsByUser = [];
        foreach ($items as $it) {
            $uid = (int)($it['vendedor_id'] ?? 0);
            if ($uid <= 0) continue;
            $itemsByUser[$uid] = $it;
        }

        foreach ($userModel->allBasic() as $u) {
            $uid = (int)($u['id'] ?? 0);
            if ($uid <= 0) continue;
            $role = (string)($u['role'] ?? 'seller');
            if ($role === 'admin') continue;

            $it = $itemsByUser[$uid] ?? null;
            $bs = $bySellerMap[$uid] ?? null;

            $row = [
                'period_key' => $periodKey,
                'period_from' => $from,
                'period_to' => $to,
                'scope' => 'seller',
                'seller_id' => $uid,
                'seller_name' => (string)($u['name'] ?? ''),
                'seller_email' => (string)($u['email'] ?? ''),
                'seller_role' => $role,
                'seller_ativo' => (int)($u['ativo'] ?? 0),
                'active_users' => null,
                'sales_count' => $bs ? (int)($bs['atendimentos'] ?? 0) : 0,
                'atendimentos' => $bs ? (int)($bs['atendimentos'] ?? 0) : 0,
                'atendimentos_concluidos' => null,
                'bruto_total_usd' => $it ? (float)($it['bruto_total'] ?? 0) : ($bs ? (float)($bs['total_bruto_usd'] ?? 0) : 0),
                'liquido_total_usd' => $it ? (float)($it['liquido_total'] ?? 0) : ($bs ? (float)($bs['total_liquido_usd'] ?? 0) : 0),
                'liquido_apurado_usd' => $it ? (float)($it['liquido_apurado'] ?? 0) : 0,
                'custos_usd' => null,
                'custos_percentuais' => null,
                'custo_config_rate' => $costRate,
                'team_cost_total_usd' => null,
                'lucro_liquido_usd' => null,
                'comissao_usd' => $it ? (float)($it['comissao_final'] ?? 0) : 0,
                'comissao_brl' => $it ? (float)($it['comissao_final_brl'] ?? 0) : 0,
                'company_cash_usd' => null,
                'company_cash_brl' => null,
                'usd_rate' => $rate,
                'meta_equipe_usd' => $metaEquipeUsd,
                'meta_equipe_brl' => $metaEquipeBrl,
                'meta_atingida' => $metaAtingida ? 1 : 0,
                'prolabore_pct' => $prolaborePct,
                'prolabore_usd' => null,
                'frozen_by_user_id' => $userId ?: null,
                'frozen_by_user_name' => $companyRow['frozen_by_user_name'] ?? null,
                'frozen_source' => $source,
                'extra_json' => isset($newByUser[$uid]) ? json_encode([
                    'new_commission' => [
                        'bruto_total_brl' => (float)($newByUser[$uid]['bruto_total_brl'] ?? 0),
                        'liquido_novo_brl' => (float)($newByUser[$uid]['liquido_novo_brl'] ?? 0),
                        'percent' => (float)($newByUser[$uid]['percent'] ?? 0),
                        'comissao_brl' => (float)($newByUser[$uid]['comissao_brl'] ?? 0),
                    ],
                ]) : null,
            ];

            $this->insertSnapshotRow($row);
        }

        return true;
    }
}
