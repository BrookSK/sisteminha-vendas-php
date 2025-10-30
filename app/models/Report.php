<?php
namespace Models;

use Core\Model;
use PDO;

class Report extends Model
{
    public function summary(string $from, string $to, ?int $sellerId = null): array
    {
        return $this->periodSummaryInternal($from, $to, $sellerId);
    }
    private function periodSummaryInternal(string $from, string $to, ?int $sellerId = null): array
    {
        // Vendas (tabela vendas) no período
        $sqlV = "SELECT
            COALESCE(SUM(v.bruto_usd),0) as total_bruto_usd,
            COALESCE(SUM(v.liquido_usd),0) as total_liquido_usd
        FROM vendas v
        WHERE v.created_at BETWEEN :from AND :to" . ($sellerId ? " AND v.usuario_id = :sid" : "");
        $stmt = $this->db->prepare($sqlV);
        $stmt->bindValue(':from', $from.' 00:00:00');
        $stmt->bindValue(':to', $to.' 23:59:59');
        if ($sellerId) $stmt->bindValue(':sid', $sellerId, PDO::PARAM_INT);
        $stmt->execute();
        $v = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // Vendas Internacionais (tabela vendas_internacionais) no período
        $sqlI = "SELECT
            COALESCE(SUM(vi.total_bruto_usd),0) as total_bruto_usd,
            COALESCE(SUM(vi.total_liquido_usd),0) as total_liquido_usd
        FROM vendas_internacionais vi
        WHERE vi.data_lancamento BETWEEN :from AND :to" . ($sellerId ? " AND vi.vendedor_id = :sid" : "");
        $stmt2 = $this->db->prepare($sqlI);
        $stmt2->bindValue(':from', $from);
        $stmt2->bindValue(':to', $to);
        if ($sellerId) $stmt2->bindValue(':sid', $sellerId, PDO::PARAM_INT);
        $stmt2->execute();
        $i = $stmt2->fetch(PDO::FETCH_ASSOC) ?: [];

        // Vendas Nacionais (tabela vendas_nacionais) no período
        $sqlN = "SELECT
            COALESCE(SUM(vn.total_bruto_usd),0) as total_bruto_usd,
            COALESCE(SUM(vn.total_liquido_usd),0) as total_liquido_usd
        FROM vendas_nacionais vn
        WHERE vn.data_lancamento BETWEEN :from AND :to" . ($sellerId ? " AND vn.vendedor_id = :sid" : "");
        $stmt3 = $this->db->prepare($sqlN);
        $stmt3->bindValue(':from', $from);
        $stmt3->bindValue(':to', $to);
        if ($sellerId) $stmt3->bindValue(':sid', $sellerId, PDO::PARAM_INT);
        $stmt3->execute();
        $n = $stmt3->fetch(PDO::FETCH_ASSOC) ?: [];

        $total_bruto_usd = (float)($v['total_bruto_usd'] ?? 0) + (float)($i['total_bruto_usd'] ?? 0) + (float)($n['total_bruto_usd'] ?? 0);
        $total_liquido_usd = (float)($v['total_liquido_usd'] ?? 0) + (float)($i['total_liquido_usd'] ?? 0) + (float)($n['total_liquido_usd'] ?? 0);

        // Atendimentos no período
        $sqlA = "SELECT COALESCE(SUM(total_atendimentos),0) as atendimentos,
                        COALESCE(SUM(total_concluidos),0) as atendimentos_concluidos
                 FROM atendimentos
                 WHERE data BETWEEN :fromD AND :toD";
        $stmtA = $this->db->prepare($sqlA);
        $stmtA->bindValue(':fromD', $from);
        $stmtA->bindValue(':toD', $to);
        $a = $stmtA->execute() ? ($stmtA->fetch(PDO::FETCH_ASSOC) ?: []) : [];

        // Custos externos no período
        $sqlC = "SELECT COALESCE(SUM(valor_usd),0) as custos_externos FROM custos
                 WHERE data BETWEEN :fromD AND :toD";
        $stmtC = $this->db->prepare($sqlC);
        $stmtC->bindValue(':fromD', $from);
        $stmtC->bindValue(':toD', $to);
        $c = $stmtC->execute() ? ($stmtC->fetch(PDO::FETCH_ASSOC) ?: []) : [];

        // Custos de Containers no período (USD)
        try { $container = new Container(); $containerCostsUSD = $container->costsInPeriodUSD($from, $to); }
        catch (\Throwable $e) { $containerCostsUSD = 0.0; }

        // Custos internos aproximados = bruto - líquido
        $custos_internos_usd = max(0, $total_bruto_usd - $total_liquido_usd);
        // Apply global team cost rate on gross (admin-configured)
        try { $set = new Setting(); } catch (\Throwable $e) { $set = null; }
        $costRate = $set ? (float)$set->get('cost_rate', '0.15') : 0.15;
        if ($costRate < 0) $costRate = 0; if ($costRate > 1) $costRate = 1;
        $custos_percentuais = $total_bruto_usd * $costRate;
        $custosTotais = $custos_internos_usd + (float)($c['custos_externos'] ?? 0) + (float)$containerCostsUSD + $custos_percentuais;
        return [
            'atendimentos' => (int)($a['atendimentos'] ?? 0),
            'atendimentos_concluidos' => (int)($a['atendimentos_concluidos'] ?? 0),
            'total_bruto_usd' => $total_bruto_usd,
            'total_liquido_usd' => $total_liquido_usd,
            'custos_usd' => $custosTotais,
            'custos_percentuais_usd' => $custos_percentuais,
            'lucro_liquido_usd' => $total_bruto_usd - $custosTotais,
        ];
    }

    public function countSalesTodayAll(?int $sellerId = null): int
    {
        $today = date('Y-m-d');
        // Legacy
        $sqlV = 'SELECT COUNT(*) c FROM vendas WHERE created_at BETWEEN :f AND :t' . ($sellerId ? ' AND usuario_id = :sid' : '');
        $stV = $this->db->prepare($sqlV);
        $stV->bindValue(':f', $today.' 00:00:00');
        $stV->bindValue(':t', $today.' 23:59:59');
        if ($sellerId) $stV->bindValue(':sid', $sellerId, PDO::PARAM_INT);
        $stV->execute(); $cV = (int)($stV->fetchColumn() ?: 0);
        // Intl
        $sqlI = 'SELECT COUNT(*) c FROM vendas_internacionais WHERE data_lancamento = :d' . ($sellerId ? ' AND vendedor_id = :sid' : '');
        $stI = $this->db->prepare($sqlI);
        $stI->bindValue(':d', $today);
        if ($sellerId) $stI->bindValue(':sid', $sellerId, PDO::PARAM_INT);
        $stI->execute(); $cI = (int)($stI->fetchColumn() ?: 0);
        // Nat
        $sqlN = 'SELECT COUNT(*) c FROM vendas_nacionais WHERE data_lancamento = :d' . ($sellerId ? ' AND vendedor_id = :sid' : '');
        $stN = $this->db->prepare($sqlN);
        $stN->bindValue(':d', $today);
        if ($sellerId) $stN->bindValue(':sid', $sellerId, PDO::PARAM_INT);
        $stN->execute(); $cN = (int)($stN->fetchColumn() ?: 0);
        return $cV + $cI + $cN;
    }

    public function recentTodayAll(int $limit = 10, ?int $sellerId = null): array
    {
        $today = date('Y-m-d');
        $lim = max(1, (int)$limit);
        // Build UNION ALL with optional seller constraint (distinct placeholders per subquery)
        $condV = $sellerId ? ' AND v.usuario_id = :sid1' : '';
        $condI = $sellerId ? ' AND vi.vendedor_id = :sid2' : '';
        $condN = $sellerId ? ' AND vn.vendedor_id = :sid3' : '';
        $sql = "(
            SELECT v.created_at AS dt, v.numero_pedido, v.bruto_usd, v.liquido_usd, v.comissao_usd, c.nome AS cliente_nome
            FROM vendas v LEFT JOIN clientes c ON c.id = v.cliente_id
            WHERE v.created_at BETWEEN :f AND :t $condV
        )
        UNION ALL
        (
            SELECT CONCAT(vi.data_lancamento,' 00:00:00') AS dt, vi.numero_pedido, vi.total_bruto_usd AS bruto_usd, vi.total_liquido_usd AS liquido_usd, 0 AS comissao_usd, c.nome AS cliente_nome
            FROM vendas_internacionais vi LEFT JOIN clientes c ON c.id = vi.cliente_id
            WHERE vi.data_lancamento = :d1 $condI
        )
        UNION ALL
        (
            SELECT CONCAT(vn.data_lancamento,' 00:00:00') AS dt, vn.numero_pedido, vn.total_bruto_usd AS bruto_usd, vn.total_liquido_usd AS liquido_usd, 0 AS comissao_usd, c.nome AS cliente_nome
            FROM vendas_nacionais vn LEFT JOIN clientes c ON c.id = vn.cliente_id
            WHERE vn.data_lancamento = :d2 $condN
        )
        ORDER BY dt DESC
        LIMIT $lim";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':f', $today.' 00:00:00');
        $stmt->bindValue(':t', $today.' 23:59:59');
        $stmt->bindValue(':d1', $today);
        $stmt->bindValue(':d2', $today);
        if ($sellerId) {
            $stmt->bindValue(':sid1', $sellerId, PDO::PARAM_INT);
            $stmt->bindValue(':sid2', $sellerId, PDO::PARAM_INT);
            $stmt->bindValue(':sid3', $sellerId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countInPeriodAll(string $from, string $to, ?int $sellerId = null): int
    {
        // Legacy table by timestamp
        $sqlV = 'SELECT COUNT(*) c FROM vendas WHERE created_at BETWEEN :f AND :t' . ($sellerId ? ' AND usuario_id = :sid' : '');
        $stV = $this->db->prepare($sqlV);
        $stV->bindValue(':f', $from.' 00:00:00');
        $stV->bindValue(':t', $to.' 23:59:59');
        if ($sellerId) $stV->bindValue(':sid', $sellerId, PDO::PARAM_INT);
        $stV->execute(); $cV = (int)($stV->fetchColumn() ?: 0);
        // International by date
        $sqlI = 'SELECT COUNT(*) c FROM vendas_internacionais WHERE data_lancamento BETWEEN :f AND :t' . ($sellerId ? ' AND vendedor_id = :sid' : '');
        $stI = $this->db->prepare($sqlI);
        $stI->bindValue(':f', $from);
        $stI->bindValue(':t', $to);
        if ($sellerId) $stI->bindValue(':sid', $sellerId, PDO::PARAM_INT);
        $stI->execute(); $cI = (int)($stI->fetchColumn() ?: 0);
        // National by date
        $sqlN = 'SELECT COUNT(*) c FROM vendas_nacionais WHERE data_lancamento BETWEEN :f AND :t' . ($sellerId ? ' AND vendedor_id = :sid' : '');
        $stN = $this->db->prepare($sqlN);
        $stN->bindValue(':f', $from);
        $stN->bindValue(':t', $to);
        if ($sellerId) $stN->bindValue(':sid', $sellerId, PDO::PARAM_INT);
        $stN->execute(); $cN = (int)($stN->fetchColumn() ?: 0);
        return $cV + $cI + $cN;
    }

    public function weekSummary(): array
    {
        // ISO week: from Monday to Sunday
        $monday = date('Y-m-d', strtotime('monday this week'));
        $sunday = date('Y-m-d', strtotime('sunday this week'));
        return $this->periodSummaryInternal($monday, $sunday, null);
    }

    public function lastMonthsComparison(int $months = 3): array
    {
        // Últimos N meses + atual. Agrega vendas (domésticas+internacionais) e custos por mês sem FULL JOIN.
        $sql = "WITH s AS (
            SELECT ym, SUM(total_bruto_usd) AS total_bruto_usd, SUM(total_liquido_usd) AS total_liquido_usd FROM (
                SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym,
                       COALESCE(SUM(bruto_usd),0) AS total_bruto_usd,
                       COALESCE(SUM(liquido_usd),0) AS total_liquido_usd
                FROM vendas
                WHERE created_at >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL :m1 MONTH), '%Y-%m-01')
                GROUP BY ym
                UNION ALL
                SELECT DATE_FORMAT(data_lancamento, '%Y-%m') AS ym,
                       COALESCE(SUM(total_bruto_usd),0) AS total_bruto_usd,
                       COALESCE(SUM(total_liquido_usd),0) AS total_liquido_usd
                FROM vendas_internacionais
                WHERE data_lancamento >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL :m2 MONTH), '%Y-%m-01')
                GROUP BY ym
                UNION ALL
                SELECT DATE_FORMAT(data_lancamento, '%Y-%m') AS ym,
                       COALESCE(SUM(total_bruto_usd),0) AS total_bruto_usd,
                       COALESCE(SUM(total_liquido_usd),0) AS total_liquido_usd
                FROM vendas_nacionais
                WHERE data_lancamento >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL :m3 MONTH), '%Y-%m-01')
                GROUP BY ym
            ) x
            GROUP BY ym
        ), c AS (
            SELECT DATE_FORMAT(data, '%Y-%m') AS ym,
                   COALESCE(SUM(valor_usd),0) AS custos_externos_usd
            FROM custos
            WHERE data >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL :m4 MONTH), '%Y-%m-01')
            GROUP BY ym
        ), all_months AS (
            SELECT ym FROM s
            UNION
            SELECT ym FROM c
        )
        SELECT am.ym,
               COALESCE(s.total_bruto_usd,0) AS total_bruto_usd,
               COALESCE(s.total_liquido_usd,0) AS total_liquido_usd,
               GREATEST(COALESCE(s.total_bruto_usd,0) - COALESCE(s.total_liquido_usd,0),0) + COALESCE(c.custos_externos_usd,0) AS custos_usd
        FROM all_months am
        LEFT JOIN s ON s.ym = am.ym
        LEFT JOIN c ON c.ym = am.ym
        ORDER BY am.ym ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':m1', $months, PDO::PARAM_INT);
        $stmt->bindValue(':m2', $months, PDO::PARAM_INT);
        $stmt->bindValue(':m3', $months, PDO::PARAM_INT);
        $stmt->bindValue(':m4', $months, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        // Apply global cost rate to each month
        try { $set = new Setting(); } catch (\Throwable $e) { $set = null; }
        $costRate = $set ? (float)$set->get('cost_rate', '0.15') : 0.15;
        if ($costRate < 0) $costRate = 0; if ($costRate > 1) $costRate = 1;
        foreach ($rows as &$r) {
            $bruto = (float)($r['total_bruto_usd'] ?? 0);
            $custosBase = (float)($r['custos_usd'] ?? 0);
            $custosPerc = $bruto * $costRate;
            $r['custos_percentuais_usd'] = $custosPerc;
            $r['custos_usd'] = $custosBase + $custosPerc;
            $r['lucro_liquido_usd'] = $bruto - $r['custos_usd'];
        }
        return $rows;
    }

    public function bySeller(?string $from = null, ?string $to = null): array
    {
        if (!$from || !$to) {
            // default current month
            $from = date('Y-m-01');
            $to = date('Y-m-t');
        }
        // Aggregates vendas + vendas_internacionais + vendas_nacionais per seller
        $sql = "WITH s AS (
            SELECT u.id, u.name, u.email, u.role FROM usuarios u WHERE u.role <> 'admin'
        ), a AS (
            SELECT usuario_id as uid, COUNT(*) as atend_v, COALESCE(SUM(bruto_usd),0) as bruto_v, COALESCE(SUM(liquido_usd),0) as liquido_v
            FROM vendas WHERE created_at BETWEEN :fromTs AND :toTs GROUP BY usuario_id
        ), b AS (
            SELECT vendedor_id as uid, COUNT(*) as atend_vi, COALESCE(SUM(total_bruto_usd),0) as bruto_vi, COALESCE(SUM(total_liquido_usd),0) as liquido_vi
            FROM vendas_internacionais WHERE data_lancamento BETWEEN :fromD AND :toD GROUP BY vendedor_id
        ), c AS (
            SELECT vendedor_id as uid, COUNT(*) as atend_vn, COALESCE(SUM(total_bruto_usd),0) as bruto_vn, COALESCE(SUM(total_liquido_usd),0) as liquido_vn
            FROM vendas_nacionais WHERE data_lancamento BETWEEN :fromD AND :toD GROUP BY vendedor_id
        )
        SELECT s.id as usuario_id, s.name, s.email, s.role,
               COALESCE(a.atend_v,0) + COALESCE(b.atend_vi,0) + COALESCE(c.atend_vn,0) as atendimentos,
               COALESCE(a.bruto_v,0) + COALESCE(b.bruto_vi,0) + COALESCE(c.bruto_vn,0) as total_bruto_usd,
               COALESCE(a.liquido_v,0) + COALESCE(b.liquido_vi,0) + COALESCE(c.liquido_vn,0) as total_liquido_usd
        FROM s
        LEFT JOIN a ON a.uid = s.id
        LEFT JOIN b ON b.uid = s.id
        LEFT JOIN c ON c.uid = s.id
        ORDER BY s.name";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':fromTs', $from.' 00:00:00');
        $stmt->bindValue(':toTs', $to.' 23:59:59');
        $stmt->bindValue(':fromD', $from);
        $stmt->bindValue(':toD', $to);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // Public API used by ReportsController@costAllocationCsv
    public function salesInPeriod(string $from, string $to): array
    {
        $stmt = $this->db->prepare("SELECT id, created_at, cliente_id, bruto_usd FROM vendas WHERE created_at BETWEEN :f AND :t ORDER BY created_at ASC");
        $stmt->execute([':f'=>$from.' 00:00:00', ':t'=>$to.' 23:59:59']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function externalCostsInPeriod(string $from, string $to): float
    {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(valor_usd),0) v FROM custos WHERE data BETWEEN :f AND :t");
        $stmt->execute([':f'=>$from, ':t'=>$to]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return (float)($row['v'] ?? 0);
    }
}
