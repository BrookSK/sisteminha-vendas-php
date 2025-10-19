<?php
use Core\Auth;
$csrf = Auth::csrf();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>Configurações DNS / Cloudflare</h3>
  <a class="btn btn-outline-secondary" href="/admin">Voltar</a>
</div>
<div class="row g-3">
  <div class="col-lg-6">
    <div class="card"><div class="card-body">
      <h6 class="mb-3">Credenciais Cloudflare</h6>
      <form method="post" action="/admin/settings/dns/save" class="row g-2">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
        <div class="col-12">
          <label class="form-label">API Token (Bearer)</label>
          <input type="password" name="cf_api_token" class="form-control" value="<?= htmlspecialchars((string)($cf_api_token ?? '')) ?>" placeholder="cf_api_token">
        </div>
        <div class="col-12">
          <label class="form-label">E-mail da Conta Cloudflare</label>
          <input type="email" name="cf_account_email" class="form-control" value="<?= htmlspecialchars((string)($cf_account_email ?? '')) ?>" placeholder="email@dominio.com">
        </div>
        <div class="col-12 d-grid">
          <button class="btn btn-primary" type="submit">Salvar</button>
        </div>
      </form>
    </div></div>
  </div>
  <div class="col-lg-6">
    <div class="card"><div class="card-body">
      <h6 class="mb-3">Como obter o API Token</h6>
      <ol class="small mb-0">
        <li>Acesse <a href="https://dash.cloudflare.com/profile/api-tokens" target="_blank" rel="noopener">Cloudflare > API Tokens</a>.</li>
        <li>Clique em "Create Token" e escolha o template "Read all resources" ou crie um token com permissão de <b>Zone.DNS Read</b>.</li>
        <li>Salve o token e cole no campo "API Token" acima. Informe também o e-mail da sua conta.</li>
        <li>Opcionalmente, você pode usar variáveis de ambiente: <code>CF_API_TOKEN</code> e <code>CF_ACCOUNT_EMAIL</code>. As configurações da tela têm prioridade.</li>
      </ol>
      <div class="alert alert-info mt-3 small">
        A verificação de DNS dos Ativos tentará primeiro o Cloudflare; se não configurado, usará DNS público via <code>dns_get_record</code>. Ativos também podem ter o IP do servidor preenchido manualmente.
      </div>
    </div></div>
  </div>
</div>
