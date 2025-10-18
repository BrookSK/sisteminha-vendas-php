<?php
use Core\Auth;
$csrf = Auth::csrf();
$doc = $doc ?? null;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3><?= $doc ? 'Editar Documentação' : 'Nova Documentação' ?></h3>
  <a class="btn btn-outline-secondary" href="/admin/documentations">Voltar</a>
</div>
<div class="card">
  <div class="card-body">
    <form method="post" action="<?= $doc ? '/admin/documentations/update' : '/admin/documentations/create' ?>">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
      <?php if ($doc): ?>
        <input type="hidden" name="id" value="<?= (int)$doc['id'] ?>">
      <?php endif; ?>
      <div class="row g-3">
        <div class="col-md-8">
          <label class="form-label">Título</label>
          <input type="text" name="title" class="form-control" required value="<?= htmlspecialchars($doc['title'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <?php
            $statuses = ['nao_iniciada'=>'Não iniciada','em_andamento'=>'Em andamento','em_revisao'=>'Em revisão','concluida'=>'Concluída','arquivada'=>'Arquivada'];
            $cur = $doc['status'] ?? 'nao_iniciada';
            foreach ($statuses as $k=>$v): ?>
              <option value="<?= $k ?>" <?= $cur===$k?'selected':'' ?>><?= $v ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Projeto (ID opcional)</label>
          <input type="number" name="project_id" class="form-control" value="<?= htmlspecialchars($doc['project_id'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Área Técnica</label>
          <select name="area_id" class="form-select">
            <option value="">Selecione</option>
            <?php $curA = (int)($doc['area_id'] ?? 0); foreach (($areas ?? []) as $a): ?>
              <option value="<?= (int)$a['id'] ?>" <?= $curA===(int)$a['id']?'selected':'' ?>><?= htmlspecialchars($a['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Permissão Interna</label>
          <select name="internal_visibility" class="form-select">
            <?php $vis = $doc['internal_visibility'] ?? 'all';
            $opts = ['admin'=>'Apenas Admin','manager'=>'Gerentes e Admin','seller'=>'Vendedores/Gerentes/Admin','all'=>'Todos'];
            foreach ($opts as $k=>$v): ?>
              <option value="<?= $k ?>" <?= $vis===$k?'selected':'' ?>><?= $v ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12">
          <label class="form-label">Conteúdo (cole do Word com formatação)</label>
          <textarea name="content" id="docContent" rows="12" class="form-control"><?= htmlspecialchars($doc['content'] ?? '') ?></textarea>
        </div>
      </div>
      <div class="mt-3 d-flex gap-2">
        <button class="btn btn-primary" type="submit">Salvar</button>
        <a class="btn btn-outline-secondary" href="/admin/documentations">Cancelar</a>
      </div>
    </form>
  </div>
</div>
<!-- CKEditor 5 Classic with Paste from Office -->
<script src="https://cdn.ckeditor.com/ckeditor5/41.0.0/classic/ckeditor.js"></script>
<script>
  ClassicEditor.create(document.querySelector('#docContent'), {
    toolbar: {
      items: [
        'undo','redo','|','heading','|','bold','italic','underline','strikethrough','|','bulletedList','numberedList','|','link','blockQuote','insertTable'
      ]
    }
  }).catch(console.error);
</script>
