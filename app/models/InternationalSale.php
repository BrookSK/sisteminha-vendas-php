<?php
namespace Models;

use Core\Model;
use PDO;

class InternationalSale extends Model
{
    private const EMBALAGEM_USD_POR_KG = 9.7; // fallback; overridden by settings if present
    private const TEAM_GOAL_USD = 50000.0;

    public function list(int $limit = 50, int $offset = 0, ?int $sellerId = null, ?string $ym = null, ?string $q = null): array
    {
        $where = [];$p = [];
        if ($sellerId) { $where[] = 'vendedor_id = :sid'; $p[':sid'] = $sellerId; }
        if ($ym) { $where[] = "DATE_FORMAT(data_lancamento, '%Y-%m') = :ym"; $p[':ym'] = $ym; }
        if ($q !== null && $q !== '') {
            $where[] = '(vi.numero_pedido LIKE :q OR c.nome LIKE :q OR vi.suite_cliente LIKE :q)';
            $p[':q'] = '%' . $q . '%';
        }
        $sql = 'SELECT vi.*, u.name as vendedor_nome, c.nome as cliente_nome FROM vendas_internacionais vi
                LEFT JOIN usuarios u ON u.id = vi.vendedor_id
                LEFT JOIN clientes c ON c.id = vi.cliente_id';
        if ($where) { $sql .= ' WHERE '.implode(' AND ',$where); }
        $sql .= ' ORDER BY data_lancamento DESC, id DESC LIMIT :lim OFFSET :off';
        $stmt = $this->db->prepare($sql);
        foreach ($p as $k=>$v) { $stmt->bindValue($k, $v); }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function count(?int $sellerId = null, ?string $ym = null, ?string $q = null): int
    {
        $where = [];$p = [];
        if ($sellerId) { $where[] = 'vi.vendedor_id = :sid'; $p[':sid'] = $sellerId; }
        if ($ym) { $where[] = "DATE_FORMAT(vi.data_lancamento, '%Y-%m') = :ym"; $p[':ym'] = $ym; }
        if ($q !== null && $q !== '') {
            $where[] = '(vi.numero_pedido LIKE :q OR c.nome LIKE :q OR vi.suite_cliente LIKE :q)';
            $p[':q'] = '%' . $q . '%';
        }
        $sql = 'SELECT COUNT(*) c FROM vendas_internacionais vi LEFT JOIN clientes c ON c.id = vi.cliente_id';
        if ($where) { $sql .= ' WHERE '.implode(' AND ',$where); }
        $stmt = $this->db->prepare($sql);
        foreach ($p as $k=>$v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return (int)($row['c'] ?? 0);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM vendas_internacionais WHERE id=:id');
        $stmt->execute([':id'=>$id]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    private function compute(array $in): array
    {
        $peso = (float)($in['peso_kg'] ?? 0);
        // Load defaults from settings
        try { $set = new Setting(); } catch (\Throwable $e) { $set = null; }
        $defaultRate = $set ? (float)$set->get('usd_rate', '5.83') : 5.83;
        $embalagemKg = $set ? (float)$set->get('embalagem_usd_por_kg', (string)self::EMBALAGEM_USD_POR_KG) : self::EMBALAGEM_USD_POR_KG;
        $taxa = (float)($in['taxa_dolar'] ?? 0);
        if ($taxa <= 0) { $taxa = $defaultRate; }
        $total_bruto_usd = (float)($in['valor_produto_usd'] ?? 0)
            + (float)($in['frete_ups_usd'] ?? 0)
            + (float)($in['valor_redirecionamento_usd'] ?? 0)
            + (float)($in['servico_compra_usd'] ?? 0);
        $total_bruto_brl = $total_bruto_usd * $taxa;
        $total_liquido_usd = $total_bruto_usd
            - (float)($in['frete_etiqueta_usd'] ?? 0)
            - (float)($in['produtos_compra_usd'] ?? 0)
            - ($peso > 0 ? $embalagemKg : 0);
        $total_liquido_brl = $total_liquido_usd * $taxa;
        return compact('total_bruto_usd','total_bruto_brl','total_liquido_usd','total_liquido_brl') + ['taxa'=>$taxa];
    }

    private function monthOf(string $date): string
    {
        return date('Y-m', strtotime($date));
    }

    private function activeSellersCount(): int
    {
        $row = $this->db->query("SELECT COUNT(*) c FROM usuarios WHERE ativo=1 AND role IN ('seller','manager','admin')")->fetch(PDO::FETCH_ASSOC) ?: [];
        return (int)($row['c'] ?? 0);
    }

    private function monthTotals(string $ym): array
    {
        $stmt = $this->db->prepare("SELECT 
            SUM(total_bruto_usd) team_bruto,
            COUNT(DISTINCT vendedor_id) seller_count
          FROM vendas_internacionais WHERE DATE_FORMAT(data_lancamento,'%Y-%m') = :ym");
        $stmt->execute([':ym'=>$ym]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'team_bruto' => (float)($row['team_bruto'] ?? 0),
            'seller_count' => (int)($row['seller_count'] ?? 0),
        ];
    }

    private function monthSellerBruto(string $ym, int $sellerId): float
    {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(total_bruto_usd),0) b FROM vendas_internacionais WHERE vendedor_id=:sid AND DATE_FORMAT(data_lancamento,'%Y-%m') = :ym");
        $stmt->execute([':sid'=>$sellerId, ':ym'=>$ym]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return (float)($row['b'] ?? 0);
    }

    private function computeCommission(string $ym, int $sellerId, float $liquido_usd, float $liquido_brl): array
    {
        $seller_bruto = $this->monthSellerBruto($ym, $sellerId);
        $percent = $seller_bruto <= 30000.0 ? 0.15 : 0.25; // per spec
        $mt = $this->monthTotals($ym);
        $bonus_brl = 0.0;
        if (($mt['team_bruto'] ?? 0) >= self::TEAM_GOAL_USD) {
            $active = max(1, $this->activeSellersCount());
            $bonus_brl = ($liquido_brl * 0.05) / $active;
        }
        $comissao_brl = ($liquido_brl * $percent) + $bonus_brl;
        $taxa = $liquido_usd > 0 ? ($liquido_brl / $liquido_usd) : 0.0;
        $comissao_usd = $taxa > 0 ? ($comissao_brl / $taxa) : 0.0;
        return compact('comissao_usd','comissao_brl');
    }

    public function create(array $data, int $sellerId, string $sellerName): int
    {
        $calc = $this->compute($data);
        $ym = $this->monthOf($data['data_lancamento']);
        $comm = $this->computeCommission($ym, $sellerId, $calc['total_liquido_usd'], $calc['total_liquido_brl']);
        $stmt = $this->db->prepare('INSERT INTO vendas_internacionais (
            data_lancamento, numero_pedido, cliente_id, suite_cliente, peso_kg,
            valor_produto_usd, frete_ups_usd, valor_redirecionamento_usd, servico_compra_usd,
            frete_etiqueta_usd, produtos_compra_usd, taxa_dolar,
            total_bruto_usd, total_bruto_brl, total_liquido_usd, total_liquido_brl,
            comissao_usd, comissao_brl, observacao, vendedor_id, criado_em,
            exchange_rate_used, gross_usd, gross_brl, liquid_usd, liquid_brl
        ) VALUES (
            :data_lancamento, :numero_pedido, :cliente_id, :suite_cliente, :peso_kg,
            :valor_produto_usd, :frete_ups_usd, :valor_redirecionamento_usd, :servico_compra_usd,
            :frete_etiqueta_usd, :produtos_compra_usd, :taxa_dolar,
            :total_bruto_usd, :total_bruto_brl, :total_liquido_usd, :total_liquido_brl,
            :comissao_usd, :comissao_brl, :observacao, :vendedor_id, NOW(),
            :exchange_rate_used, :gross_usd, :gross_brl, :liquid_usd, :liquid_brl
        )');
        $stmt->execute([
            ':data_lancamento' => $data['data_lancamento'],
            ':numero_pedido' => $data['numero_pedido'] ?? null,
            ':cliente_id' => (int)$data['cliente_id'],
            ':suite_cliente' => $data['suite_cliente'] ?? null,
            ':peso_kg' => (float)($data['peso_kg'] ?? 0),
            ':valor_produto_usd' => (float)($data['valor_produto_usd'] ?? 0),
            ':frete_ups_usd' => (float)($data['frete_ups_usd'] ?? 0),
            ':valor_redirecionamento_usd' => (float)($data['valor_redirecionamento_usd'] ?? 0),
            ':servico_compra_usd' => (float)($data['servico_compra_usd'] ?? 0),
            ':frete_etiqueta_usd' => (float)($data['frete_etiqueta_usd'] ?? 0),
            ':produtos_compra_usd' => (float)($data['produtos_compra_usd'] ?? 0),
            ':taxa_dolar' => (float)($data['taxa_dolar'] ?? 0),
            ':total_bruto_usd' => $calc['total_bruto_usd'],
            ':total_bruto_brl' => $calc['total_bruto_brl'],
            ':total_liquido_usd' => $calc['total_liquido_usd'],
            ':total_liquido_brl' => $calc['total_liquido_brl'],
            ':comissao_usd' => $comm['comissao_usd'],
            ':comissao_brl' => $comm['comissao_brl'],
            ':observacao' => $data['observacao'] ?? null,
            ':vendedor_id' => $sellerId,
            ':exchange_rate_used' => $calc['taxa'],
            ':gross_usd' => $calc['total_bruto_usd'],
            ':gross_brl' => $calc['total_bruto_brl'],
            ':liquid_usd' => $calc['total_liquido_usd'],
            ':liquid_brl' => $calc['total_liquido_brl'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data, string $sellerName, bool $allowDateChange): void
    {
        $existing = $this->find($id);
        if (!$existing) return;
        // protect date change
        if (!$allowDateChange) {
            $data['data_lancamento'] = $existing['data_lancamento'];
        }
        $calc = $this->compute($data);
        $ym = $this->monthOf($data['data_lancamento']);
        $comm = $this->computeCommission($ym, (int)$existing['vendedor_id'], $calc['total_liquido_usd'], $calc['total_liquido_brl']);
        $observacao = $existing['observacao'] ?? '';
        if ($existing['data_lancamento'] !== $data['data_lancamento']) {
            $logLine = sprintf(
                "Vendedor %s editou a data do pedido no dia %s de %s para %s.",
                $sellerName,
                date('Y-m-d'),
                $existing['data_lancamento'],
                $data['data_lancamento']
            );
            $observacao = trim($observacao === '' ? $logLine : ($observacao."\n".$logLine));
        }
        $stmt = $this->db->prepare('UPDATE vendas_internacionais SET
            data_lancamento=:data_lancamento, numero_pedido=:numero_pedido, cliente_id=:cliente_id, suite_cliente=:suite_cliente, peso_kg=:peso_kg,
            valor_produto_usd=:valor_produto_usd, frete_ups_usd=:frete_ups_usd, valor_redirecionamento_usd=:valor_redirecionamento_usd, servico_compra_usd=:servico_compra_usd,
            frete_etiqueta_usd=:frete_etiqueta_usd, produtos_compra_usd=:produtos_compra_usd, taxa_dolar=:taxa_dolar,
            total_bruto_usd=:total_bruto_usd, total_bruto_brl=:total_bruto_brl, total_liquido_usd=:total_liquido_usd, total_liquido_brl=:total_liquido_brl,
            comissao_usd=:comissao_usd, comissao_brl=:comissao_brl, observacao=:observacao,
            exchange_rate_used=:exchange_rate_used, gross_usd=:gross_usd, gross_brl=:gross_brl, liquid_usd=:liquid_usd, liquid_brl=:liquid_brl,
            atualizado_em=NOW()
        WHERE id=:id');
        $stmt->execute([
            ':id' => $id,
            ':data_lancamento' => $data['data_lancamento'],
            ':numero_pedido' => $data['numero_pedido'] ?? null,
            ':cliente_id' => (int)$data['cliente_id'],
            ':suite_cliente' => $data['suite_cliente'] ?? null,
            ':peso_kg' => (float)($data['peso_kg'] ?? 0),
            ':valor_produto_usd' => (float)($data['valor_produto_usd'] ?? 0),
            ':frete_ups_usd' => (float)($data['frete_ups_usd'] ?? 0),
            ':valor_redirecionamento_usd' => (float)($data['valor_redirecionamento_usd'] ?? 0),
            ':servico_compra_usd' => (float)($data['servico_compra_usd'] ?? 0),
            ':frete_etiqueta_usd' => (float)($data['frete_etiqueta_usd'] ?? 0),
            ':produtos_compra_usd' => (float)($data['produtos_compra_usd'] ?? 0),
            ':taxa_dolar' => (float)($data['taxa_dolar'] ?? 0),
            ':total_bruto_usd' => $calc['total_bruto_usd'],
            ':total_bruto_brl' => $calc['total_bruto_brl'],
            ':total_liquido_usd' => $calc['total_liquido_usd'],
            ':total_liquido_brl' => $calc['total_liquido_brl'],
            ':comissao_usd' => $comm['comissao_usd'],
            ':comissao_brl' => $comm['comissao_brl'],
            ':observacao' => $observacao,
            ':exchange_rate_used' => $calc['taxa'],
            ':gross_usd' => $calc['total_bruto_usd'],
            ':gross_brl' => $calc['total_bruto_brl'],
            ':liquid_usd' => $calc['total_liquido_usd'],
            ':liquid_brl' => $calc['total_liquido_brl'],
        ]);
    }

    public function exportCsv(array $filters): string
    {
        $rows = $this->list(10000, 0, $filters['seller_id'] ?? null, $filters['ym'] ?? null);
        $fh = fopen('php://temp','w+');
        fputcsv($fh, ['ID','Data','Pedido','Cliente','Suite','Bruto USD','Bruto BRL','Líquido USD','Líquido BRL','Comissão USD','Comissão BRL']);
        foreach ($rows as $r) {
            fputcsv($fh, [
                $r['id'], $r['data_lancamento'], $r['numero_pedido'], $r['cliente_nome'] ?? $r['cliente_id'], $r['suite_cliente'],
                number_format((float)$r['total_bruto_usd'],2,'.',''),
                number_format((float)$r['total_bruto_brl'],2,'.',''),
                number_format((float)$r['total_liquido_usd'],2,'.',''),
                number_format((float)$r['total_liquido_brl'],2,'.',''),
                number_format((float)$r['comissao_usd'],2,'.',''),
                number_format((float)$r['comissao_brl'],2,'.',''),
            ]);
        }
        rewind($fh);
        return stream_get_contents($fh) ?: '';
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM vendas_internacionais WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
