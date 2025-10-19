<?php
use Core\Auth;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title ?? 'Sisteminha') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/assets/css/style.css" rel="stylesheet">
  <!-- DataTables (CDN) -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="/admin">Sistema Brasiliana</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto gap-2">
        <?php if (Auth::check()): ?>
          <?php $role = (string) (Auth::user()['role'] ?? 'seller'); ?>
          <?php if ($role === 'seller'): ?>
            <li class="nav-item"><a class="nav-link" href="/admin">Dashboard de Vendas</a></li>
            <!-- Cadastros (seller) -->
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="menuCadastrosSeller" role="button" data-bs-toggle="dropdown" aria-expanded="false">Cadastros</a>
              <ul class="dropdown-menu" aria-labelledby="menuCadastrosSeller">
                <li><a class="dropdown-item" href="/admin/clients">Clientes</a></li>
                <li><a class="dropdown-item" href="/admin/attendances">Atendimentos</a></li>
              </ul>
            </li>
          <?php elseif ($role === 'trainee'): ?>
            <li class="nav-item"><a class="nav-link" href="/admin">Dashboard de Vendas</a></li>
            <!-- Cadastros (trainee) -->
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="menuCadastrosTrainee" role="button" data-bs-toggle="dropdown" aria-expanded="false">Cadastros</a>
              <ul class="dropdown-menu" aria-labelledby="menuCadastrosTrainee">
                <li><a class="dropdown-item" href="/admin/clients">Clientes</a></li>
                <li><a class="dropdown-item" href="/admin/attendances">Atendimentos</a></li>
              </ul>
            </li>
            <!-- Vendas (trainee) -->
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="menuVendasTrainee" role="button" data-bs-toggle="dropdown" aria-expanded="false">Vendas</a>
              <ul class="dropdown-menu" aria-labelledby="menuVendasTrainee">
                <li><a class="dropdown-item" href="/admin/international-sales">Internacionais</a></li>
                <li><a class="dropdown-item" href="/admin/national-sales">Nacionais</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="/admin/sales-simulator">Simulador de Cálculo</a></li>
              </ul>
            </li>
            <!-- Comissões (trainee) -->
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="menuComissoesTrainee" role="button" data-bs-toggle="dropdown" aria-expanded="false">Comissões</a>
              <ul class="dropdown-menu" aria-labelledby="menuComissoesTrainee">
                <li><a class="dropdown-item" href="/admin/commissions/me">Minhas Comissões</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="/admin/settings/calculations-simple">Entenda os Cálculos</a></li>
              </ul>
            </li>
            <!-- Metas (trainee) -->
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="menuMetasTrainee" role="button" data-bs-toggle="dropdown" aria-expanded="false">Metas</a>
              <ul class="dropdown-menu" aria-labelledby="menuMetasTrainee">
                <li><a class="dropdown-item" href="/admin/my/goals">Minhas Metas</a></li>
              </ul>
            </li>
            <!-- Vendas (seller) -->
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="menuVendasSeller" role="button" data-bs-toggle="dropdown" aria-expanded="false">Vendas</a>
              <ul class="dropdown-menu" aria-labelledby="menuVendasSeller">
                <li><a class="dropdown-item" href="/admin/international-sales">Internacionais</a></li>
                <li><a class="dropdown-item" href="/admin/national-sales">Nacionais</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="/admin/sales-simulator">Simulador de Cálculo</a></li>
              </ul>
            </li>
            <!-- Comissões (seller) -->
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="menuComissoesSeller" role="button" data-bs-toggle="dropdown" aria-expanded="false">Comissões</a>
              <ul class="dropdown-menu" aria-labelledby="menuComissoesSeller">
                <li><a class="dropdown-item" href="/admin/commissions/me">Minhas Comissões</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="/admin/settings/calculations-simple">Entenda os Cálculos</a></li>
              </ul>
            </li>
            <!-- Metas (seller) -->
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="menuMetasSeller" role="button" data-bs-toggle="dropdown" aria-expanded="false">Metas</a>
              <ul class="dropdown-menu" aria-labelledby="menuMetasSeller">
                <li><a class="dropdown-item" href="/admin/my/goals">Minhas Metas</a></li>
              </ul>
            </li>
          <?php elseif ($role === 'organic'): ?>
            <li class="nav-item"><a class="nav-link" href="/admin">Dashboard de Vendas</a></li>
            <!-- Cadastros (orgânico) -->
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="menuCadastrosOrganic" role="button" data-bs-toggle="dropdown" aria-expanded="false">Cadastros</a>
              <ul class="dropdown-menu" aria-labelledby="menuCadastrosOrganic">
                <li><a class="dropdown-item" href="/admin/clients">Clientes</a></li>
                <li><a class="dropdown-item" href="/admin/attendances">Atendimentos</a></li>
              </ul>
            </li>
            <!-- Vendas (orgânico) -->
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="menuVendasOrganic" role="button" data-bs-toggle="dropdown" aria-expanded="false">Vendas</a>
              <ul class="dropdown-menu" aria-labelledby="menuVendasOrganic">
                <li><a class="dropdown-item" href="/admin/international-sales">Internacionais</a></li>
                <li><a class="dropdown-item" href="/admin/national-sales">Nacionais</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="/admin/sales-simulator">Simulador de Cálculo</a></li>
              </ul>
            </li>
            <!-- Metas (orgânico) -->
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="menuMetasOrganic" role="button" data-bs-toggle="dropdown" aria-expanded="false">Metas</a>
              <ul class="dropdown-menu" aria-labelledby="menuMetasOrganic">
                <li><a class="dropdown-item" href="/admin/my/goals">Minhas Metas</a></li>
              </ul>
            </li>
          <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="/admin">Dashboard de Vendas</a></li>
          <?php $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
            $isDemands = str_starts_with($path, '/admin/demands') || str_starts_with($path, '/admin/projects');
            $isHostings = str_starts_with($path, '/admin/hostings') || str_starts_with($path, '/admin/hosting-assets') || str_starts_with($path, '/admin/site-clients') || str_starts_with($path, '/admin/settings/dns'); ?>

          <?php if ($isDemands): ?>
            <!-- Contexto: Demandas -->
            <li class="nav-item"><a class="nav-link" href="/admin/demands/dashboard">Dashboard de demandas</a></li>
            <li class="nav-item"><a class="nav-link" href="/admin/demands">Listagem</a></li>
            <li class="nav-item"><a class="nav-link" href="/admin/projects">Projetos</a></li>
            <li class="nav-item"><a class="nav-link" href="/admin/notifications">Avisos</a></li>
          <?php elseif ($isHostings): ?>
            <!-- Contexto: Hospedagens -->
            <li class="nav-item"><a class="nav-link" href="/admin/hostings">Dashboard de Hospedagens</a></li>
            <li class="nav-item"><a class="nav-link" href="/admin/hosting-assets">Ativos</a></li>
            <li class="nav-item"><a class="nav-link" href="/admin/site-clients">Clientes</a></li>
          <?php else: ?>
            <!-- Contexto: Vendas/Admin (padrão) -->
            <!-- Cadastros -->
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="menuCadastros" role="button" data-bs-toggle="dropdown" aria-expanded="false">Cadastros</a>
              <ul class="dropdown-menu" aria-labelledby="menuCadastros">
                <li><a class="dropdown-item" href="/admin/clients">Clientes</a></li>
                <li><a class="dropdown-item" href="/admin/attendances">Atendimentos</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="/admin/costs">Custos</a></li>
                <li><a class="dropdown-item" href="/admin/purchases">Compras</a></li>
                <li><a class="dropdown-item" href="/admin/containers">Containers</a></li>
              </ul>
            </li>

            <!-- Vendas -->
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="menuVendas" role="button" data-bs-toggle="dropdown" aria-expanded="false">Vendas</a>
              <ul class="dropdown-menu" aria-labelledby="menuVendas">
                <li><a class="dropdown-item" href="/admin/international-sales">Internacionais</a></li>
                <li><a class="dropdown-item" href="/admin/national-sales">Nacionais</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="/admin/sales-simulator">Simulador de Cálculo</a></li>
              </ul>
            </li>

            <?php if ($role === 'admin'): ?>
              <!-- Webhooks (admin only) -->
              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="menuWebhooks" role="button" data-bs-toggle="dropdown" aria-expanded="false">Webhooks</a>
                <ul class="dropdown-menu" aria-labelledby="menuWebhooks">
                  <li><a class="dropdown-item" href="/admin/webhooks/logs">Logs</a></li>
                  <li><a class="dropdown-item" href="/admin/webhooks/guide">Guia</a></li>
                </ul>
              </li>
            <?php endif; ?>

            <!-- Comissões -->
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="menuComissoes" role="button" data-bs-toggle="dropdown" aria-expanded="false">Comissões</a>
              <ul class="dropdown-menu" aria-labelledby="menuComissoes">
                <li><a class="dropdown-item" href="/admin/commissions/me">Minhas Comissões</a></li>
                <li><a class="dropdown-item" href="/admin/commissions">Comissões Empresa</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="/admin/settings/calculations-simple">Entenda os Cálculos</a></li>
              </ul>
            </li>

            <!-- Metas -->
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="menuMetas" role="button" data-bs-toggle="dropdown" aria-expanded="false">Metas</a>
              <ul class="dropdown-menu" aria-labelledby="menuMetas">
                <li><a class="dropdown-item" href="/admin/my/goals">Minhas Metas</a></li>
                <li><a class="dropdown-item" href="/admin/goals">Metas da Equipe</a></li>
              </ul>
            </li>

            <!-- Administração -->
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="menuAdmin" role="button" data-bs-toggle="dropdown" aria-expanded="false">Administração</a>
              <ul class="dropdown-menu" aria-labelledby="menuAdmin">
                <li><a class="dropdown-item" href="/admin/reports">Relatórios</a></li>
                <li><a class="dropdown-item" href="/admin/users">Usuários</a></li>
                <li><a class="dropdown-item" href="/admin/approvals">Aprovações</a></li>
                <li><a class="dropdown-item" href="/admin/donations">Doações</a></li>
                <li><a class="dropdown-item" href="/admin/documentation-areas">Áreas Técnicas</a></li>
                <?php if ($role === 'admin'): ?>
                  <li><a class="dropdown-item" href="/admin/logs">Logs</a></li>
                  <li><a class="dropdown-item" href="/admin/settings/dns">Configurações DNS</a></li>
                  <li><hr class="dropdown-divider"></li>
                  <li><a class="dropdown-item" href="/admin/settings">Configurações</a></li>
                <?php endif; ?>
              </ul>
            </li>
          <?php endif; ?>
          <?php endif; ?>
        <?php endif; ?>
      </ul>
      <ul class="navbar-nav gap-2">
        <?php if (Auth::check()): ?>
          <?php 
          $unread = 0; 
          try { $notif = new \Models\Notification(); $unread = $notif->unreadCount((int)(Auth::user()['id'] ?? 0)); } catch (\Throwable $e) {}
          ?>
          <li class="nav-item">
            <a class="nav-link position-relative" href="/admin/notifications" title="Notificações">
              <span class="me-1">🔔</span>
              <?php if ($unread > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= (int)$unread ?></span>
              <?php endif; ?>
            </a>
          </li>
          <li class="nav-item"><a class="nav-link me-1" href="/admin/account">Olá, <?= htmlspecialchars(Auth::user()['name'] ?? Auth::user()['email']) ?></a></li>
          <li class="nav-item"><a class="nav-link" href="/logout">Sair</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="/login">Entrar</a></li>
        <?php endif; ?>
      </ul>
  </div>
</nav>
<main class="container my-4">
  <?php if (!empty($_SESSION['flash'])): ?>
    <?php foreach (($_SESSION['flash'] ?? []) as $f): ?>
      <div class="alert alert-<?= htmlspecialchars($f['type'] ?? 'info') ?> alert-dismissible fade show" role="alert">
        <?php $__msg = (string)($f['message'] ?? ''); echo htmlspecialchars($__msg); ?>
        <?php
          $copyUrl = null;
          if (preg_match('/https?:\/\/[^\s"\']+/i', $__msg, $m)) { $copyUrl = $m[0]; }
        ?>
        <?php if ($copyUrl): ?>
          <div class="mt-2 small d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary copy-link-btn" data-link="<?= htmlspecialchars($copyUrl) ?>">Copiar link</button>
            <span class="text-muted text-truncate" style="max-width: 100%; overflow:hidden;"><?= htmlspecialchars($copyUrl) ?></span>
          </div>
        <?php endif; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endforeach; unset($_SESSION['flash']); ?>
    <script>
      (function(){
        document.querySelectorAll('.copy-link-btn').forEach(function(btn){
          btn.addEventListener('click', function(){
            const link = this.getAttribute('data-link') || '';
            if (!link) return;
            if (navigator.clipboard && navigator.clipboard.writeText) {
              navigator.clipboard.writeText(link).then(()=>{
                this.classList.remove('btn-outline-secondary');
                this.classList.add('btn-success');
                this.textContent = 'Copiado!';
                setTimeout(()=>{
                  this.classList.add('btn-outline-secondary');
                  this.classList.remove('btn-success');
                  this.textContent = 'Copiar link';
                }, 2000);
              });
            } else {
              const ta = document.createElement('textarea');
              ta.value = link; document.body.appendChild(ta); ta.select();
              try { document.execCommand('copy'); } catch(e) {}
              document.body.removeChild(ta);
            }
          });
        });
      })();
    </script>
  <?php endif; ?>
  <?= $content ?>
</main>
<footer class="bg-light py-3 mt-4 border-top">
  <div class="container d-flex flex-wrap justify-content-between align-items-center small text-muted">
    <span>Desenvolvido por <a href="https://lrvweb.com.br/" target="_blank" rel="noopener">LRV Web</a></span>
    <span>© <?= date('Y') ?> Braziliana Shop. Todos os direitos reservados.</span>
  </div>
  
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery (required by DataTables) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<!-- DataTables (CDN) -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="/assets/js/app.js"></script>
</body>
</html>
