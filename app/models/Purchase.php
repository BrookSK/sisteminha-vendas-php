<?php
namespace Models;

use Core\Model;
use PDO;

class Purchase extends Model
{
    /** Upsert purchase row from a sale id, pulling client info */
    public function upsertFromSale(int $saleId): void
    {
        $sql = 'SELECT v.id as venda_id, v.suite, v.produto_link, v.bruto_usd as valor_usd, v.nc_tax,
                       (v.frete_manual_valor IS NOT NULL) as frete_aplicado, v.frete_manual_valor,
                       c.nome as cliente_nome, c.telefone as cliente_contato
                FROM vendas v
                LEFT JOIN clientes c ON c.id = v.cliente_id
                WHERE v.id = :id';
        $row = $this->db->prepare($sql);
        $row->execute([':id' => $saleId]);
        $s = $row->fetch(PDO::FETCH_ASSOC);
        if (!$s || empty($s['produto_link'])) { return; }

        $stmt = $this->db->prepare('INSERT INTO compras (
                venda_id, suite, cliente_nome, cliente_contato, produto_link, valor_usd, nc_tax,
                frete_aplicado, frete_valor, comprado, data_compra, responsavel_id, observacoes, created_at, updated_at
            ) VALUES (
                :venda_id, :suite, :cliente_nome, :cliente_contato, :produto_link, :valor_usd, :nc_tax,
                :frete_aplicado, :frete_valor, 0, NULL, NULL, NULL, NOW(), NOW()
            )
            ON DUPLICATE KEY UPDATE
                suite=VALUES(suite), cliente_nome=VALUES(cliente_nome), cliente_contato=VALUES(cliente_contato),
                produto_link=VALUES(produto_link), valor_usd=VALUES(valor_usd), nc_tax=VALUES(nc_tax),
                frete_aplicado=VALUES(frete_aplicado), frete_valor=VALUES(frete_valor), updated_at=NOW()');
        $stmt->execute([
            ':venda_id' => (int)$s['venda_id'],
            ':suite' => $s['suite'] ?? null,
            ':cliente_nome' => $s['cliente_nome'] ?? null,
            ':cliente_contato' => $s['cliente_contato'] ?? null,
            ':produto_link' => $s['produto_link'],
            ':valor_usd' => (float)($s['valor_usd'] ?? 0),
            ':nc_tax' => (int)($s['nc_tax'] ?? 0),
            ':frete_aplicado' => (int)($s['frete_aplicado'] ?? 0),
            ':frete_valor' => isset($s['frete_manual_valor']) ? (float)$s['frete_manual_valor'] : null,
        ]);
    }

    /** Upsert from vendas_internacionais */
    public function upsertFromIntl(int $intlId): void
    {
        $row = $this->db->prepare('SELECT vi.id as venda_id, vi.suite_cliente as suite, vi.numero_pedido, vi.total_bruto_usd as valor_usd,
                                           c.nome as cliente_nome, c.telefone as cliente_contato
                                    FROM vendas_internacionais vi
                                    LEFT JOIN clientes c ON c.id = vi.cliente_id
                                    WHERE vi.id = :id');
        $row->execute([':id'=>$intlId]);
        $s = $row->fetch(PDO::FETCH_ASSOC);
        if (!$s) return;
        // Schema atual exige compras.venda_id NOT NULL com FK para `vendas(id)` (legacy).
        // Para vendas internacionais não há venda legacy correspondente.
        // Evitar inserir para não violar NOT NULL/FK até migração suportar intl/nat.
        return;
        $stmt = $this->db->prepare('INSERT INTO compras (
                venda_id, suite, cliente_nome, cliente_contato, produto_link, valor_usd, nc_tax,
                frete_aplicado, frete_valor, comprado, data_compra, responsavel_id, observacoes, created_at, updated_at
            ) VALUES (
                :venda_id, :suite, :cliente_nome, :cliente_contato, :produto_link, :valor_usd, 0,
                0, NULL, 0, NULL, NULL, NULL, NOW(), NOW()
            )
            ON DUPLICATE KEY UPDATE
                suite=VALUES(suite), cliente_nome=VALUES(cliente_nome), cliente_contato=VALUES(cliente_contato),
                produto_link=VALUES(produto_link), valor_usd=VALUES(valor_usd), updated_at=NOW()');
        $stmt->execute([
            // compras.venda_id referencia apenas a tabela legacy `vendas`. Para internacionais, manter NULL.
            ':venda_id' => null,
            ':suite' => $s['suite'] ?? null,
            ':cliente_nome' => $s['cliente_nome'] ?? null,
            ':cliente_contato' => $s['cliente_contato'] ?? null,
            ':produto_link' => $s['numero_pedido'] ? ('Pedido '.$s['numero_pedido']) : null,
            ':valor_usd' => (float)($s['valor_usd'] ?? 0),
        ]);
    }

    /** Upsert from vendas_nacionais */
    public function upsertFromNat(int $natId): void
    {
        $row = $this->db->prepare('SELECT vn.id as venda_id, vn.suite_cliente as suite, vn.numero_pedido, vn.total_bruto_usd as valor_usd,
                                           c.nome as cliente_nome, c.telefone as cliente_contato
                                    FROM vendas_nacionais vn
                                    LEFT JOIN clientes c ON c.id = vn.cliente_id
                                    WHERE vn.id = :id');
        $row->execute([':id'=>$natId]);
        $s = $row->fetch(PDO::FETCH_ASSOC);
        if (!$s) return;
        // Schema atual exige compras.venda_id NOT NULL com FK para `vendas(id)` (legacy).
        // Para vendas nacionais não há venda legacy correspondente.
        // Evitar inserir para não violar NOT NULL/FK até migração suportar intl/nat.
        return;
        $stmt = $this->db->prepare('INSERT INTO compras (
                venda_id, suite, cliente_nome, cliente_contato, produto_link, valor_usd, nc_tax,
                frete_aplicado, frete_valor, comprado, data_compra, responsavel_id, observacoes, created_at, updated_at
            ) VALUES (
                :venda_id, :suite, :cliente_nome, :cliente_contato, :produto_link, :valor_usd, 0,
                0, NULL, 0, NULL, NULL, NULL, NOW(), NOW()
            )
            ON DUPLICATE KEY UPDATE
                suite=VALUES(suite), cliente_nome=VALUES(cliente_nome), cliente_contato=VALUES(cliente_contato),
                produto_link=VALUES(produto_link), valor_usd=VALUES(valor_usd), updated_at=NOW()');
        $stmt->execute([
            // compras.venda_id referencia apenas a tabela legacy `vendas`. Para nacionais, manter NULL.
            ':venda_id' => null,
            ':suite' => $s['suite'] ?? null,
            ':cliente_nome' => $s['cliente_nome'] ?? null,
            ':cliente_contato' => $s['cliente_contato'] ?? null,
            ':produto_link' => $s['numero_pedido'] ? ('Pedido '.$s['numero_pedido']) : null,
            ':valor_usd' => (float)($s['valor_usd'] ?? 0),
        ]);
    }

    public function list(int $limit = 50, int $offset = 0, ?int $responsavelId = null, ?string $status = null, ?string $from = null, ?string $to = null): array
    {
        $where = [];$params = [];
        if ($responsavelId) { $where[] = 'responsavel_id = :rid'; $params[':rid'] = $responsavelId; }
        if ($status === 'pendente') { $where[] = 'comprado = 0'; }
        if ($status === 'comprado') { $where[] = 'comprado = 1'; }
        if ($from) { $where[] = '(created_at >= :from)'; $params[':from'] = $from.' 00:00:00'; }
        if ($to) { $where[] = '(created_at <= :to)'; $params[':to'] = $to.' 23:59:59'; }
        $sql = 'SELECT * FROM compras';
        if ($where) { $sql .= ' WHERE '.implode(' AND ',$where); }
        $sql .= ' ORDER BY comprado ASC, created_at DESC LIMIT :lim OFFSET :off';
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function updateRow(array $data): void
    {
        $stmt = $this->db->prepare('UPDATE compras SET
            nc_tax=:nc_tax, frete_aplicado=:frete_aplicado, frete_valor=:frete_valor,
            comprado=:comprado, data_compra=:data_compra, responsavel_id=:responsavel_id,
            observacoes=:observacoes, updated_at=NOW()
        WHERE id=:id');
        $stmt->execute([
            ':id' => (int)$data['id'],
            ':nc_tax' => (int)($data['nc_tax'] ?? 0),
            ':frete_aplicado' => (int)($data['frete_aplicado'] ?? 0),
            ':frete_valor' => isset($data['frete_valor']) && $data['frete_valor'] !== '' ? (float)$data['frete_valor'] : null,
            ':comprado' => (int)($data['comprado'] ?? 0),
            ':data_compra' => !empty($data['data_compra']) ? $data['data_compra'] : null,
            ':responsavel_id' => !empty($data['responsavel_id']) ? (int)$data['responsavel_id'] : null,
            ':observacoes' => $data['observacoes'] ?? null,
        ]);
    }

    /** Create a manual purchase record */
    public function createManual(array $data): int
    {
        $sql = 'INSERT INTO compras (
            venda_id, suite, cliente_nome, cliente_contato, produto_link, valor_usd, nc_tax,
            frete_aplicado, frete_valor, comprado, data_compra, responsavel_id, observacoes, created_at, updated_at
        ) VALUES (
            :venda_id, :suite, :cliente_nome, :cliente_contato, :produto_link, :valor_usd, :nc_tax,
            :frete_aplicado, :frete_valor, :comprado, :data_compra, :responsavel_id, :observacoes, NOW(), NOW()
        )';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':venda_id' => !empty($data['venda_id']) ? (int)$data['venda_id'] : null,
            ':suite' => $data['suite'] !== '' ? $data['suite'] : null,
            ':cliente_nome' => $data['cliente_nome'] !== '' ? $data['cliente_nome'] : null,
            ':cliente_contato' => $data['cliente_contato'] !== '' ? $data['cliente_contato'] : null,
            ':produto_link' => $data['produto_link'] !== '' ? $data['produto_link'] : null,
            ':valor_usd' => isset($data['valor_usd']) ? (float)$data['valor_usd'] : 0,
            ':nc_tax' => (int)($data['nc_tax'] ?? 0),
            ':frete_aplicado' => (int)($data['frete_aplicado'] ?? 0),
            ':frete_valor' => isset($data['frete_valor']) && $data['frete_valor'] !== '' ? (float)$data['frete_valor'] : null,
            ':comprado' => (int)($data['comprado'] ?? 0),
            ':data_compra' => !empty($data['data_compra']) ? $data['data_compra'] : null,
            ':responsavel_id' => !empty($data['responsavel_id']) ? (int)$data['responsavel_id'] : null,
            ':observacoes' => $data['observacoes'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }
}
