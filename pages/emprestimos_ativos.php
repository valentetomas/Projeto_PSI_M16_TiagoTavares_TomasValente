<?php
require_once __DIR__ . "/auth/guard.php";
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/includes/navbar.php";
 
// regra simples de atraso (dias)
$limite_atraso = 7;
 
// filtros
$search = trim($_GET["search"] ?? "");
$categoria = trim($_GET["categoria"] ?? "");
$localizacao = trim($_GET["localizacao"] ?? "");
$so_atrasados = (int)($_GET["so_atrasados"] ?? 0);
 
// ordenação
$sort = $_GET["sort"] ?? "desde";
$dir = strtolower($_GET["dir"] ?? "desc");
$dir = $dir === "asc" ? "asc" : "desc";
 
$cats = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome")->fetchAll();
$locs = $pdo->query("SELECT id, nome FROM localizacoes ORDER BY nome")->fetchAll();
 
$sql = "
SELECT
  m.id,
  m.codigo,
  m.nome,
  m.estado,
  m.emprestado_a,
  m.emprestado_em,
  c.nome AS categoria,
  l.nome AS localizacao
FROM materiais m
JOIN categorias c ON c.id = m.categoria_id
JOIN localizacoes l ON l.id = m.localizacao_id
WHERE m.estado = 'Emprestado'
";
 
$params = [];
 
if ($search !== "") {
  $sql .= " AND (m.nome LIKE ? OR m.codigo LIKE ? OR m.emprestado_a LIKE ?) ";
  $params[] = "%{$search}%";
  $params[] = "%{$search}%";
  $params[] = "%{$search}%";
}
 
if ($categoria !== "") {
  $sql .= " AND m.categoria_id = ? ";
  $params[] = $categoria;
}
 
if ($localizacao !== "") {
  $sql .= " AND m.localizacao_id = ? ";
  $params[] = $localizacao;
}
 
// map de ordenação permitido (evita SQL injection)
$sort_map = [
  "desde" => "m.emprestado_em",
  "nome" => "m.nome",
  "codigo" => "m.codigo",
  "responsavel" => "m.emprestado_a"
];
$order_by = $sort_map[$sort] ?? "m.emprestado_em";
 
$sql .= " ORDER BY {$order_by} {$dir}, m.nome, m.codigo ";
 
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
 
// filtrar apenas atrasados (feito em PHP para manter simples)
if ($so_atrasados) {
  $rows = array_values(array_filter($rows, function($r) use ($limite_atraso) {
    if (empty($r["emprestado_em"])) return false;
    $dias = (int)floor((time() - strtotime($r["emprestado_em"])) / 86400);
    if ($dias < 0) $dias = 0;
    return $dias >= $limite_atraso;
  }));
}
 
// contadores (com base no que está a ser mostrado)
$total = count($rows);
$atrasados = 0;
foreach ($rows as $r) {
  if (!empty($r["emprestado_em"])) {
    $diasTmp = (int)floor((time() - strtotime($r["emprestado_em"])) / 86400);
    if ($diasTmp < 0) $diasTmp = 0;
    if ($diasTmp >= $limite_atraso) $atrasados++;
  }
}
 
// helper para links mantendo filtros
function build_qs($overrides = []) {
  $q = array_merge($_GET, $overrides);
  foreach ($q as $k => $v) {
    if ($v === "" || $v === null) unset($q[$k]);
  }
  return http_build_query($q);
}
 
// helper dir toggle
function toggle_dir($current_dir) {
  return $current_dir === "asc" ? "desc" : "asc";
}
?>
 
<div class="container mt-4">
<h3>Empréstimos ativos</h3>
 
  <div class="alert alert-info mt-3">
<strong><?= (int)$total ?></strong> materiais emprestados |
<strong><?= (int)$atrasados ?></strong> atrasados
<span class="text-muted" style="margin-left:10px;">(Regra de atraso: <?= (int)$limite_atraso ?> dias)</span>
</div>
 
  <form class="row g-2 mt-3 mb-3" method="get">
<div class="col-md-4">
<input class="form-control" name="search" placeholder="Pesquisar (material ou responsável)" value="<?= htmlspecialchars($search) ?>">
</div>
 
    <div class="col-md-3">
<select class="form-select" name="categoria">
<option value="">Todas as categorias</option>
<?php foreach ($cats as $c): ?>
<option value="<?= (int)$c["id"] ?>" <?= ((string)$categoria === (string)$c["id"]) ? "selected" : "" ?>>
<?= htmlspecialchars($c["nome"]) ?>
</option>
<?php endforeach; ?>
</select>
</div>
 
    <div class="col-md-3">
<select class="form-select" name="localizacao">
<option value="">Todas as localizações</option>
<?php foreach ($locs as $l): ?>
<option value="<?= (int)$l["id"] ?>" <?= ((string)$localizacao === (string)$l["id"]) ? "selected" : "" ?>>
<?= htmlspecialchars($l["nome"]) ?>
</option>
<?php endforeach; ?>
</select>
</div>
 
    <div class="col-md-2 d-grid">
<button class="btn btn-primary">Filtrar</button>
</div>
 
    <div class="col-12 d-flex align-items-center gap-2 flex-wrap">
<div class="form-check">
<input class="form-check-input" type="checkbox" value="1" id="so_atrasados" name="so_atrasados" <?= $so_atrasados ? "checked" : "" ?>>
<label class="form-check-label" for="so_atrasados">Só atrasados</label>
</div>
 
      <a class="btn btn-outline-secondary btn-sm" href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/emprestimos_ativos.php">Limpar filtros</a>
 
      <a class="btn btn-outline-success btn-sm"
         href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/emprestimos_ativos_exportar.php?<?= htmlspecialchars(build_qs()) ?>">
        Exportar CSV
</a>
 
      <span class="text-muted">Exporta exatamente o que está filtrado/ordenado.</span>
</div>
 
    <!-- manter sort/dir ao filtrar -->
<input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
<input type="hidden" name="dir" value="<?= htmlspecialchars($dir) ?>">
</form>
 
  <div class="table-responsive">
<table class="table table-striped align-middle">
<thead>
<tr>
<th>
<a href="?<?= htmlspecialchars(build_qs(["sort" => "codigo", "dir" => ($sort === "codigo" ? toggle_dir($dir) : "asc")])) ?>">
              Código
</a>
</th>
<th>
<a href="?<?= htmlspecialchars(build_qs(["sort" => "nome", "dir" => ($sort === "nome" ? toggle_dir($dir) : "asc")])) ?>">
              Nome
</a>
</th>
<th>Categoria</th>
<th>Localização</th>
<th>
<a href="?<?= htmlspecialchars(build_qs(["sort" => "responsavel", "dir" => ($sort === "responsavel" ? toggle_dir($dir) : "asc")])) ?>">
              Responsável
</a>
</th>
<th>
<a href="?<?= htmlspecialchars(build_qs(["sort" => "desde", "dir" => ($sort === "desde" ? toggle_dir($dir) : "desc")])) ?>">
              Desde
</a>
</th>
<th>Estado</th>
<th>Ações</th>
</tr>
</thead>
<tbody>
<?php foreach ($rows as $r): ?>
<?php
          $dias = null;
          if (!empty($r["emprestado_em"])) {
            $dias = (int)floor((time() - strtotime($r["emprestado_em"])) / 86400);
            if ($dias < 0) $dias = 0;
          }
          $atrasado = ($dias !== null && $dias >= $limite_atraso);
          $resp = trim((string)($r["emprestado_a"] ?? ""));
        ?>
<tr>
<td><?= htmlspecialchars($r["codigo"]) ?></td>
<td><?= htmlspecialchars($r["nome"]) ?></td>
<td><?= htmlspecialchars($r["categoria"]) ?></td>
<td><?= htmlspecialchars($r["localizacao"]) ?></td>
<td><strong><?= htmlspecialchars($resp !== "" ? $resp : "Não definido") ?></strong></td>
<td>
<?php if (!empty($r["emprestado_em"])): ?>
<?= htmlspecialchars(date("d/m/Y H:i", strtotime($r["emprestado_em"]))) ?>
<div class="text-muted" style="font-size:0.9em;">(<?= (int)$dias ?> dia<?= ($dias === 1) ? "" : "s" ?>)</div>
<?php else: ?>
<span class="text-muted">Sem data</span>
<?php endif; ?>
</td>
<td>
<span class="badge text-bg-<?= $atrasado ? "danger" : "warning" ?>">
<?= $atrasado ? "Atrasado" : "Ativo" ?>
<?php if ($dias !== null): ?>
<span style="font-weight: normal;">· <?= (int)$dias ?>d</span>
<?php endif; ?>
</span>
</td>
<td>
<a class="btn btn-sm btn-outline-success"
   onclick="return confirm('Confirmar devolução deste material?')"
   href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/devolver.php?material_id=<?= (int)$r["id"] ?>">
  Devolver
</a>
<a class="btn btn-sm btn-outline-secondary"
               href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/historico.php?material_id=<?= (int)$r["id"] ?>">
              Histórico
</a>
</td>
</tr>
<?php endforeach; ?>
 
      <?php if (count($rows) === 0): ?>
<tr>
<td colspan="8" class="text-center text-muted">Sem empréstimos ativos.</td>
</tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
 
<?php require_once __DIR__ . "/includes/footer.php"; ?>