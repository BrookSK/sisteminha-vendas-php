<?php ?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h3 class="mb-0"><?= htmlspecialchars($title ?? 'Desempenho Individual') ?></h3>
</div>

<form method="get" class="row g-2 mb-3">
  <div class="col-auto">
    <label class="form-label">De</label>
    <input type="date" name="from" class="form-control" value="<?= htmlspecialchars($from) ?>">
  </div>
  <div class="col-auto">
    <label class="form-label">Até</label>
    <input type="date" name="to" class="form-control" value="<?= htmlspecialchars($to) ?>">
  </div>
  <div class="col-auto align-self-end">
    <button class="btn btn-primary">Filtrar</button>
  </div>
</form>

<?php if ($is_admin): ?>
  <div class="accordion" id="accPerf">
    <?php $idx = 0; foreach ($dataByUser as $uid => $d): $idx++; ?>
      <?php $headingId = 'h'.$idx; $collapseId = 'c'.$idx; ?>
      <div class="accordion-item">
        <h2 class="accordion-header" id="<?= $headingId ?>">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>" aria-expanded="false" aria-controls="<?= $collapseId ?>">
            <?= htmlspecialchars($d['user']['name'] ?? $d['user']['email'] ?? ('Usuário #'.$uid)) ?>
            <span class="ms-2 badge bg-secondary text-uppercase">
              <?= htmlspecialchars($d['role'] ?? '') ?>
            </span>
          </button>
        </h2>
        <div id="<?= $collapseId ?>" class="accordion-collapse collapse" aria-labelledby="<?= $headingId ?>" data-bs-parent="#accPerf">
          <div class="accordion-body">
            <?php include __DIR__ . '/partials_user_blocks.php'; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <?php $d = current($dataByUser) ?: []; ?>
  <?php include __DIR__ . '/partials_user_blocks.php'; ?>
<?php endif; ?>
