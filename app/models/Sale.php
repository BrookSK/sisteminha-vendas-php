<?php
namespace Models;

use Core\Model;
use PDO;

class Sale extends Model
{
    public function dashboardStats(float $rate, ?int $userId = null): array
    {
        // Totais agregados simples (USD/BRL) e contagem, opcionalmente por usuário
        if ($userId) {
            $stmt = $this->db->prepare('SELECT 
                COUNT(*) as total_vendas,
                COALESCE(SUM(bruto_usd),0) as total_bruto_usd,
                COALESCE(SUM(liquido_usd),0) as total_liquido_usd,
                COALESCE(SUM(comissao_usd),0) as total_comissao_usd
            FROM vendas WHERE usuario_id = :uid');
            $stmt->execute([':uid' => $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_vendas'=>0,'total_bruto_usd'=>0,'total_liquido_usd'=>0,'total_comissao_usd'=>0];
        } else {
            $row = $this->db->query('SELECT 
                COUNT(*) as total_vendas,
                COALESCE(SUM(bruto_usd),0) as total_bruto_usd,
                COALESCE(SUM(liquido_usd),0) as total_liquido_usd,
                COALESCE(SUM(comissao_usd),0) as total_comissao_usd
            FROM vendas')->fetch(PDO::FETCH_ASSOC);
        }

        $totalBrutoBRL = (float)$row['total_bruto_usd'] * $rate;
        $totalLiquidoBRL = (float)$row['total_liquido_usd'] * $rate;
        $totalComissaoBRL = (float)$row['total_comissao_usd'] * $rate;

        return [
            'total_vendas' => (int)$row['total_vendas'],
            'total_bruto_usd' => (float)$row['total_bruto_usd'],
            'total_bruto_brl' => $totalBrutoBRL,
            'total_liquido_usd' => (float)$row['total_liquido_usd'],
            'total_liquido_brl' => $totalLiquidoBRL,
            'total_comissao_usd' => (float)$row['total_comissao_usd'],
            'total_comissao_brl' => $totalComissaoBRL,
        ];
    }

    public function recent(int $limit = 10, ?int $userId = null): array
    {
        if ($userId) {
            $stmt = $this->db->prepare('SELECT v.*, c.nome as cliente_nome FROM vendas v 
                LEFT JOIN clientes c ON c.id = v.cliente_id
                WHERE v.usuario_id = :uid
                ORDER BY v.created_at DESC
                LIMIT :lim');
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt = $this->db->prepare('SELECT v.*, c.nome as cliente_nome FROM vendas v 
                LEFT JOIN clientes c ON c.id = v.cliente_id
                ORDER BY v.created_at DESC
                LIMIT :lim');
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function list(int $limit = 100, int $offset = 0, ?int $userId = null): array
    {
        if ($userId) {
            $stmt = $this->db->prepare('SELECT v.*, c.nome as cliente_nome FROM vendas v
                LEFT JOIN clientes c ON c.id = v.cliente_id
                WHERE v.usuario_id = :uid
                ORDER BY v.created_at DESC
                LIMIT :lim OFFSET :off');
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt = $this->db->prepare('SELECT v.*, c.nome as cliente_nome FROM vendas v
                LEFT JOIN clientes c ON c.id = v.cliente_id
                ORDER BY v.created_at DESC
                LIMIT :lim OFFSET :off');
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM vendas WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data, float $rate, ?int $usuarioId, float $embalagemRate = 9.7): int
    {
        $calc = $this->compute($data, $rate, $embalagemRate);
        $stmt = $this->db->prepare('INSERT INTO vendas (
            created_at, numero_pedido, cliente_id, usuario_id, suite, peso_kg,
            valor_produto_usd, taxa_servico_usd, servico_compra_usd, produto_compra_usd,
            frete_brl, frete_usd, embalagem_usd, bruto_usd, bruto_brl, liquido_usd, liquido_brl,
            comissao_usd, comissao_brl,
            produto_link, origem, nc_tax, frete_manual_valor
        ) VALUES (
            :created_at, :numero_pedido, :cliente_id, :usuario_id, :suite, :peso_kg,
            :valor_produto_usd, :taxa_servico_usd, :servico_compra_usd, :produto_compra_usd,
            :frete_brl, :frete_usd, :embalagem_usd, :bruto_usd, :bruto_brl, :liquido_usd, :liquido_brl,
            :comissao_usd, :comissao_brl,
            :produto_link, :origem, :nc_tax, :frete_manual_valor
        )');
        $stmt->execute([
            ':created_at' => $data['created_at'] ?: date('Y-m-d H:i:s'),
            ':numero_pedido' => $data['numero_pedido'] ?? null,
            ':cliente_id' => (int)($data['cliente_id'] ?? 0) ?: null,
            ':usuario_id' => $usuarioId,
            ':suite' => $data['suite'] ?? null,
            ':peso_kg' => (float)($data['peso_kg'] ?? 0),
            ':valor_produto_usd' => (float)($data['valor_produto_usd'] ?? 0),
            ':taxa_servico_usd' => (float)($data['taxa_servico_usd'] ?? 0),
            ':servico_compra_usd' => (float)($data['servico_compra_usd'] ?? 0),
            ':produto_compra_usd' => (float)($data['produto_compra_usd'] ?? 0),
            ':frete_brl' => $calc['frete_brl'],
            ':frete_usd' => $calc['frete_usd'],
            ':embalagem_usd' => $calc['embalagem_usd'],
            ':bruto_usd' => $calc['bruto_usd'],
            ':bruto_brl' => $calc['bruto_brl'],
            ':liquido_usd' => $calc['liquido_usd'],
            ':liquido_brl' => $calc['liquido_brl'],
            ':comissao_usd' => $calc['comissao_usd'],
            ':comissao_brl' => $calc['comissao_brl'],
            ':produto_link' => $data['produto_link'] ?? null,
            ':origem' => $data['origem'] ?? null,
            ':nc_tax' => (int)($data['nc_tax'] ?? 0),
            ':frete_manual_valor' => isset($data['frete_manual_valor']) && $data['frete_manual_valor'] !== '' ? (float)$data['frete_manual_valor'] : null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data, float $rate, float $embalagemRate = 9.7): void
    {
        $calc = $this->compute($data, $rate, $embalagemRate);
        $stmt = $this->db->prepare('UPDATE vendas SET
            created_at = :created_at,
            numero_pedido = :numero_pedido,
            cliente_id = :cliente_id,
            suite = :suite,
            peso_kg = :peso_kg,
            valor_produto_usd = :valor_produto_usd,
            taxa_servico_usd = :taxa_servico_usd,
            servico_compra_usd = :servico_compra_usd,
            produto_compra_usd = :produto_compra_usd,
            frete_brl = :frete_brl,
            frete_usd = :frete_usd,
            embalagem_usd = :embalagem_usd,
            bruto_usd = :bruto_usd,
            bruto_brl = :bruto_brl,
            liquido_usd = :liquido_usd,
            liquido_brl = :liquido_brl,
            comissao_usd = :comissao_usd,
            comissao_brl = :comissao_brl,
            produto_link = :produto_link,
            origem = :origem,
            nc_tax = :nc_tax,
            frete_manual_valor = :frete_manual_valor
        WHERE id = :id');
        $stmt->execute([
            ':id' => $id,
            ':created_at' => $data['created_at'] ?: date('Y-m-d H:i:s'),
            ':numero_pedido' => $data['numero_pedido'] ?? null,
            ':cliente_id' => (int)($data['cliente_id'] ?? 0) ?: null,
            ':suite' => $data['suite'] ?? null,
            ':peso_kg' => (float)($data['peso_kg'] ?? 0),
            ':valor_produto_usd' => (float)($data['valor_produto_usd'] ?? 0),
            ':taxa_servico_usd' => (float)($data['taxa_servico_usd'] ?? 0),
            ':servico_compra_usd' => (float)($data['servico_compra_usd'] ?? 0),
            ':produto_compra_usd' => (float)($data['produto_compra_usd'] ?? 0),
            ':frete_brl' => $calc['frete_brl'],
            ':frete_usd' => $calc['frete_usd'],
            ':embalagem_usd' => $calc['embalagem_usd'],
            ':bruto_usd' => $calc['bruto_usd'],
            ':bruto_brl' => $calc['bruto_brl'],
            ':liquido_usd' => $calc['liquido_usd'],
            ':liquido_brl' => $calc['liquido_brl'],
            ':comissao_usd' => $calc['comissao_usd'],
            ':comissao_brl' => $calc['comissao_brl'],
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM vendas WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function compute(array $data, float $rate, float $embalagemRate = 9.7): array
    {
        $peso = (float)($data['peso_kg'] ?? 0);
        $produto = (float)($data['valor_produto_usd'] ?? 0);
        $taxaServico = (float)($data['taxa_servico_usd'] ?? 0);
        $servicoCompra = (float)($data['servico_compra_usd'] ?? 0);
        $produtoCompra = (float)($data['produto_compra_usd'] ?? 0);

        // Frete Correios (BRL)
        if ($peso <= 0) {
            $freteBRL = 0.0;
        } elseif ($peso <= 1) {
            $freteBRL = 35.0;
        } elseif ($peso <= 2) {
            $freteBRL = 43.0;
        } else { // >2kg (regra descreve >3kg=51; assumimos >2 → 51 como aproximação)
            $freteBRL = 51.0;
        }

        // Frete USD
        $freteUSD = $rate > 0 ? $freteBRL / $rate : 0.0;

        // Embalagem USD (valor fixo por venda; se peso=0, então 0)
        $embalagemUSD = $peso > 0 ? max(0, $embalagemRate) : 0.0;

        // Bruto USD (inclui embalagem cobrada do cliente)
        $brutoUSD = $produto + $freteUSD + $servicoCompra + $taxaServico + $embalagemUSD;
        $brutoBRL = $brutoUSD * $rate;

        // Líquido USD
        if ($peso <= 0) {
            $liquidoUSD = 0.0;
        } else {
            $liquidoUSD = $brutoUSD - $freteUSD - $produtoCompra;
        }
        $liquidoBRL = $liquidoUSD * $rate;

        // Comissão USD (por faixas do Bruto USD)
        if ($brutoUSD <= 30000) {
            $perc = 0.15;
        } elseif ($brutoUSD <= 45000) {
            $perc = 0.25;
        } else {
            $perc = 0.25;
        }
        $comissaoUSD = $liquidoUSD * $perc;
        $comissaoBRL = $comissaoUSD * $rate;

        return [
            'frete_brl' => round($freteBRL, 2),
            'frete_usd' => round($freteUSD, 2),
            'embalagem_usd' => round($embalagemUSD, 2),
            'bruto_usd' => round($brutoUSD, 2),
            'bruto_brl' => round($brutoBRL, 2),
            'liquido_usd' => round($liquidoUSD, 2),
            'liquido_brl' => round($liquidoBRL, 2),
            'comissao_usd' => round($comissaoUSD, 2),
            'comissao_brl' => round($comissaoBRL, 2),
        ];
    }
}
