<?php
use Core\Auth;
$csrf = Auth::csrf();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>Hospedagens</h3>
  <a class="btn btn-outline-secondary" href="/admin">Voltar</a>
</div>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card"><div class="card-body">
      <h6 class="mb-3">Nova Hospedagem</h6>
      <form method="post" action="/admin/hostings/create" class="row g-2">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
        <div class="col-6">
          <label class="form-label">Provedor</label>
          <input type="text" name="provider" class="form-control" placeholder="Plesk, Hostinger, ..." required>
        </div>
        <div class="col-6">
          <label class="form-label">Servidor</label>
          <input type="text" name="server_name" class="form-control" placeholder="srv-br01" required>
        </div>
        <div class="col-6">
          <label class="form-label">Plano</label>
          <input type="text" name="plan_name" class="form-control">
        </div>
        <div class="col-6">
          <label class="form-label">Preço</label>
          <input type="number" step="0.01" name="price" class="form-control">
        </div>
        <div class="col-6">
          <label class="form-label">Vencimento (data)</label>
          <input type="date" name="due_date" class="form-control">
        </div>
        <div class="col-6">
          <label class="form-label">Dia do venc.</label>
          <input type="number" name="due_day" class="form-control" min="1" max="31">
        </div>
        <div class="col-6">
          <label class="form-label">Ciclo</label>
          <select name="billing_cycle" class="form-select">
            <?php $cycles=['mensal','bimestral','trimestral','semestral','anual','bienal','trienal','outro']; foreach($cycles as $c): ?>
              <option value="<?= $c ?>"><?= ucfirst($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6">
          <label class="form-label">IP do Servidor</label>
          <input type="text" name="server_ip" class="form-control" placeholder="186.209.xx.xx">
        </div>
        <div class="col-6">
          <label class="form-label">Login (e-mail)</label>
          <input type="email" name="login_email" class="form-control">
        </div>
        <div class="col-6">
          <label class="form-label">Responsável Pagamento</label>
          <input type="text" name="payer_responsible" class="form-control">
        </div>
        <div class="col-6">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <?php $statuses=['ativo','em_contratacao_recusada','nao_contratado','em_cancelamento','em_contratacao','cancelado']; foreach($statuses as $s): ?>
              <option value="<?= $s ?>"><?= str_replace('_',' ', ucfirst($s)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6 d-flex align-items-center">
          <div class="form-check mt-4">
            <input class="form-check-input" type="checkbox" name="auto_payment" value="1" id="autoPayNew">
            <label class="form-check-label" for="autoPayNew">Pagamento automático</label>
          </div>
        </div>
        <div class="col-12">
          <label class="form-label">Descrição</label>
          <textarea name="description" class="form-control" rows="2"></textarea>
        </div>
        <div class="col-12 d-grid">
          <button class="btn btn-primary" type="submit">Adicionar</button>
        </div>
      </form>
    </div></div>
  </div>
  <div class="col-lg-8">
    <div class="card"><div class="card-body">
      <div class="table-responsive">
        <table class="table table-striped align-middle">
          <thead><tr>
            <th>Provedor</th>
            <th>Servidor</th>
            <th>Plano</th>
            <th>Preço</th>
            <th>Ciclo</th>
            <th>Status</th>
            <th>IP</th>
            <th style="width: 220px;">Ações</th>
          </tr></thead>
          <tbody>
            <?php foreach (($hostings ?? []) as $h): ?>
              <tr>
                <td><?= htmlspecialchars($h['provider']) ?></td>
                <td><?= htmlspecialchars($h['server_name']) ?></td>
                <td><?= htmlspecialchars($h['plan_name'] ?? '') ?></td>
                <td><?= htmlspecialchars(number_format((float)($h['price'] ?? 0),2,',','.')) ?></td>
                <td><?= htmlspecialchars($h['billing_cycle'] ?? '') ?></td>
                <td><?= htmlspecialchars($h['status'] ?? '') ?></td>
                <td><?= htmlspecialchars($h['server_ip'] ?? '') ?></td>
                <td>
                  <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#edit<?= (int)$h['id'] ?>">Editar</button>
                  <form method="post" action="/admin/hostings/delete" class="d-inline" onsubmit="return confirm('Excluir esta hospedagem?');">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="id" value="<?= (int)$h['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger" type="submit">Excluir</button>
                  </form>
                </td>
              </tr>
              <tr class="collapse" id="edit<?= (int)$h['id'] ?>"><td colspan="8">
                <form method="post" action="/admin/hostings/update" class="row g-2">
                  <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                  <input type="hidden" name="id" value="<?= (int)$h['id'] ?>">
                  <div class="col-4">
                    <label class="form-label">Provedor</label>
                    <input type="text" name="provider" class="form-control" value="<?= htmlspecialchars($h['provider']) ?>">
                  </div>
                  <div class="col-4">
                    <label class="form-label">Servidor</label>
                    <input type="text" name="server_name" class="form-control" value="<?= htmlspecialchars($h['server_name']) ?>">
                  </div>
                  <div class="col-4">
                    <label class="form-label">Plano</label>
                    <input type="text" name="plan_name" class="form-control" value="<?= htmlspecialchars($h['plan_name'] ?? '') ?>">
                  </div>
                  <div class="col-3">
                    <label class="form-label">Preço</label>
                    <input type="number" step="0.01" name="price" class="form-control" value="<?= htmlspecialchars((string)($h['price'] ?? '')) ?>">
                  </div>
                  <div class="col-3">
                    <label class="form-label">Venc. (data)</label>
                    <input type="date" name="due_date" class="form-control" value="<?= htmlspecialchars((string)($h['due_date'] ?? '')) ?>">
                  </div>
                  <div class="col-2">
                    <label class="form-label">Dia</label>
                    <input type="number" name="due_day" class="form-control" min="1" max="31" value="<?= htmlspecialchars((string)($h['due_day'] ?? '')) ?>">
                  </div>
                  <div class="col-4">
                    <label class="form-label">Ciclo</label>
                    <select name="billing_cycle" class="form-select">
                      <?php $cycles=['mensal','bimestral','trimestral','semestral','anual','bienal','trienal','outro']; foreach($cycles as $c): ?>
                        <option value="<?= $c ?>" <?= (($h['billing_cycle'] ?? '')===$c)?'selected':'' ?>><?= ucfirst($c) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-4">
                    <label class="form-label">IP do Servidor</label>
                    <input type="text" name="server_ip" class="form-control" value="<?= htmlspecialchars($h['server_ip'] ?? '') ?>">
                  </div>
                  <div class="col-4">
                    <label class="form-label">Login (e-mail)</label>
                    <input type="email" name="login_email" class="form-control" value="<?= htmlspecialchars($h['login_email'] ?? '') ?>">
                  </div>
                  <div class="col-4">
                    <label class="form-label">Responsável Pagamento</label>
                    <input type="text" name="payer_responsible" class="form-control" value="<?= htmlspecialchars($h['payer_responsible'] ?? '') ?>">
                  </div>
                  <div class="col-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                      <?php $statuses=['ativo','em_contratacao_recusada','nao_contratado','em_cancelamento','em_contratacao','cancelado']; foreach($statuses as $s): ?>
                        <option value="<?= $s ?>" <?= (($h['status'] ?? '')===$s)?'selected':'' ?>><?= str_replace('_',' ', ucfirst($s)) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-4 d-flex align-items-center">
                    <div class="form-check mt-4">
                      <input class="form-check-input" type="checkbox" name="auto_payment" value="1" id="autoPay<?= (int)$h['id'] ?>" <?= ((int)($h['auto_payment'] ?? 0)===1)?'checked':'' ?>>
                      <label class="form-check-label" for="autoPay<?= (int)$h['id'] ?>">Pagamento automático</label>
                    </div>
                  </div>
                  <div class="col-12">
                    <label class="form-label">Descrição</label>
                    <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($h['description'] ?? '') ?></textarea>
                  </div>
                  <div class="col-12 d-grid">
                    <button class="btn btn-outline-primary" type="submit">Salvar</button>
                  </div>
                </form>
              </td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div></div>
  </div>
</div>
