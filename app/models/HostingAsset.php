<?php
namespace Models;

use Core\Model;
use PDO;

class HostingAsset extends Model
{
    public function list(int $limit = 1000, int $offset = 0, ?int $hostingId = null): array
    {
        $sql = 'SELECT a.*, h.server_name, h.server_ip, c.nome as client_name FROM hosting_assets a LEFT JOIN hostings h ON h.id = a.hosting_id LEFT JOIN clientes c ON c.id = a.client_id';
        $params = [];
        if ($hostingId) { $sql .= ' WHERE a.hosting_id = :hid'; $params[':hid'] = $hostingId; }
        $sql .= ' ORDER BY a.type ASC, a.title ASC LIMIT :lim OFFSET :off';
        $st = $this->db->prepare($sql);
        foreach ($params as $k=>$v) { $st->bindValue($k, $v, PDO::PARAM_INT); }
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function getCloudflareSettings(): array
    {
        try {
            $st = $this->db->query("SELECT `key`,`value` FROM app_settings WHERE `key` IN ('cf_api_token','cf_account_email')");
            $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $map = [];
            foreach ($rows as $r) { $map[$r['key']] = $r['value']; }
            $token = $map['cf_api_token'] ?? null;
            $email = $map['cf_account_email'] ?? null;
            if ($token && $email) return [$token, $email];
        } catch (\Throwable $e) { /* ignore */ }
        // fallback to env
        return [getenv('CF_API_TOKEN') ?: null, getenv('CF_ACCOUNT_EMAIL') ?: null];
    }

    public function find(int $id): ?array
    {
        $st = $this->db->prepare('SELECT a.*, h.server_ip FROM hosting_assets a LEFT JOIN hostings h ON h.id = a.hosting_id WHERE a.id=:id');
        $st->execute([':id'=>$id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function create(array $d, ?int $userId = null): int
    {
        $st = $this->db->prepare('INSERT INTO hosting_assets (title,url,hosting_id,type,server_ip,real_ip,pointing_ok,client_id,created_by) VALUES (:title,:url,:hosting_id,:type,:server_ip,:real_ip,:pointing_ok,:client_id,:cb)');
        $st->execute([
            ':title'=>$d['title'],
            ':url'=>$d['url'] ?? null,
            ':hosting_id'=>$d['hosting_id'] ?? null,
            ':type'=>$d['type'] ?? 'site',
            ':server_ip'=>$d['server_ip'] ?? null,
            ':real_ip'=>$d['real_ip'] ?? null,
            ':pointing_ok'=>isset($d['pointing_ok']) ? (int)$d['pointing_ok'] : null,
            ':client_id'=>$d['client_id'] ?? null,
            ':cb'=>$userId,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateRow(int $id, array $d, ?int $userId = null): void
    {
        $st = $this->db->prepare('UPDATE hosting_assets SET title=:title,url=:url,hosting_id=:hosting_id,type=:type,server_ip=:server_ip,real_ip=:real_ip,pointing_ok=:pointing_ok,client_id=:client_id,updated_by=:ub,updated_at=NOW() WHERE id=:id');
        $st->execute([
            ':id'=>$id,
            ':title'=>$d['title'],
            ':url'=>$d['url'] ?? null,
            ':hosting_id'=>$d['hosting_id'] ?? null,
            ':type'=>$d['type'] ?? 'site',
            ':server_ip'=>$d['server_ip'] ?? null,
            ':real_ip'=>$d['real_ip'] ?? null,
            ':pointing_ok'=>isset($d['pointing_ok']) ? (int)$d['pointing_ok'] : null,
            ':client_id'=>$d['client_id'] ?? null,
            ':ub'=>$userId,
        ]);
    }

    public function delete(int $id): void
    {
        $st = $this->db->prepare('DELETE FROM hosting_assets WHERE id=:id');
        $st->execute([':id'=>$id]);
    }

    public function refreshDNS(int $id): ?array
    {
        $asset = $this->find($id);
        if (!$asset) return null;
        $serverIp = $asset['server_ip'] ?? null;
        if (!$serverIp && !empty($asset['hosting_id'])) {
            $h = $this->db->prepare('SELECT server_ip FROM hostings WHERE id=:id');
            $h->execute([':id'=>$asset['hosting_id']]);
            $serverIp = $h->fetchColumn() ?: null;
        }
        $host = $this->extractHostFromUrl((string)($asset['url'] ?? ''));
        $realIp = $this->resolveARecord($host);
        $ok = null;
        if ($serverIp && $realIp) { $ok = (trim($serverIp) === trim($realIp)) ? 1 : 0; }
        $this->updateRow($id, [
            'title'=>$asset['title'],
            'url'=>$asset['url'],
            'hosting_id'=>$asset['hosting_id'],
            'type'=>$asset['type'],
            'server_ip'=>$serverIp,
            'real_ip'=>$realIp,
            'pointing_ok'=>$ok,
            'client_id'=>$asset['client_id'],
        ]);
        // direct SQL to set checked_at
        $st = $this->db->prepare('UPDATE hosting_assets SET checked_at = NOW() WHERE id = :id');
        $st->execute([':id'=>$id]);
        return $this->find($id);
    }

    private function extractHostFromUrl(string $url): ?string
    {
        if ($url === '') return null;
        $u = parse_url($url);
        return $u['host'] ?? $url;
    }

    private function resolveARecord(?string $host): ?string
    {
        if (!$host) return null;
        $ip = $this->resolveViaCloudflare($host);
        if ($ip) return $ip;
        $records = @dns_get_record($host, DNS_A);
        if (!$records || !is_array($records) || count($records) === 0) return null;
        foreach ($records as $r) {
            if (!empty($r['ip'])) return $r['ip'];
        }
        // Try CNAME recursion
        $cname = $this->resolveCname($host);
        if ($cname && $cname !== $host) {
            return $this->resolveARecord($cname);
        }
        return null;
    }

    private function resolveCname(string $host): ?string
    {
        $records = @dns_get_record($host, DNS_CNAME);
        if (!$records || !is_array($records) || count($records) === 0) return null;
        foreach ($records as $r) {
            if (!empty($r['target'])) return rtrim($r['target'], '.');
        }
        return null;
    }

    private function resolveViaCloudflare(string $host): ?string
    {
        [$token, $email] = $this->getCloudflareSettings();
        if (!$token || !$email) return null;
        $zoneId = $this->cfFindZoneId($host, $token, $email);
        if (!$zoneId) return null;
        $name = $host;
        $ip = $this->cfGetARecordIp($zoneId, $name, $token, $email);
        return $ip;
    }

    private function cfFindZoneId(string $host, string $token, string $email): ?string
    {
        $domain = $host;
        $parts = explode('.', $host);
        if (count($parts) > 2) {
            $domain = implode('.', array_slice($parts, -2));
        }
        $url = 'https://api.cloudflare.com/client/v4/zones?name=' . urlencode($domain);
        $resp = $this->httpGetJson($url, $token, $email);
        if (!$resp || empty($resp['success']) || empty($resp['result'][0]['id'])) return null;
        return $resp['result'][0]['id'] ?? null;
    }

    private function cfGetARecordIp(string $zoneId, string $name, string $token, string $email): ?string
    {
        $url = 'https://api.cloudflare.com/client/v4/zones/' . urlencode($zoneId) . '/dns_records?type=A&name=' . urlencode($name);
        $resp = $this->httpGetJson($url, $token, $email);
        if ($resp && !empty($resp['success']) && !empty($resp['result'])) {
            foreach ($resp['result'] as $r) {
                if (!empty($r['content'])) return $r['content'];
            }
        }
        return null;
    }

    private function httpGetJson(string $url, string $token, string $email): ?array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
            'X-Auth-Email: ' . $email,
        ]);
        $out = curl_exec($ch);
        curl_close($ch);
        if (!$out) return null;
        $j = json_decode($out, true);
        return is_array($j) ? $j : null;
    }
}
