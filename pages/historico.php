<?php
require_once __DIR__ . "/auth/guard.php";
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/includes/navbar.php";
 
$material_id = trim($_GET["material_id"] ?? "");
$tipo = trim($_GET["tipo"] ?? "");
$search = trim($_GET["search"] ?? "");
$data_inicio = trim($_GET["data_inicio"] ?? "");
$data_fim = trim($_GET["data_fim"] ?? "");
 
$params = [];
$sql = "
SELECT
  mov.id,
  mov.tipo,
  mov.responsavel,
  mov.user_id,
  mov.observacao,
  mov.data_movimento,
  m.id AS material_id,
  m.codigo,
  m.nome,
  u.nome AS registado_por
FROM movimentos mov
JOIN materiais m ON m.id = mov.material_id
LEFT JOIN users u ON u.id = mov.user_id
WHERE 1=1
";
 
if ($material_id !== "") {
  $sql .= " AND m.id = ? ";
  $params[] = $material_id;
}
 
if ($tipo !== "") {
  $sql .= " AND mov.tipo = ? ";
  $params[] = $tipo;
}
 
if ($search !== "") {
  $sql .= " AND (m.nome LIKE ? OR m.codigo LIKE ? OR mov.responsavel LIKE ? OR mov.observacao LIKE ? OR u.nome LIKE ?) ";
  $params[] = "%{$search}%";
  $params[] = "%{$search}%";
  $params[] = "%{$search}%";
  $params[] = "%{$search}%";
  $params[] = "%{$search}%";
}

if ($data_inicio !== "") {
  $sql .= " AND mov.data_movimento >= ? ";
  $params[] = $data_inicio . " 00:00:00";
}

if ($data_fim !== "") {
  $sql .= " AND mov.data_movimento <= ? ";
  $params[] = $data_fim . " 23:59:59";
}
 
$sql .= " ORDER BY mov.data_movimento DESC, mov.id DESC ";
 
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
 
// dropdown de materiais (para filtro)
$materiais = $pdo->query("SELECT id, codigo, nome FROM materiais ORDER BY nome, codigo")->fetchAll();
?>
 
<div class="container mt-4">
<h3>Histórico</h3>
 
  <form class="row g-2 mt-3 mb-2" method="get">
<div class="col-md-5">
<select class="form-select" name="material_id">
<option value="">Todos os materiais</option>
<?php foreach ($materiais as $m): ?>
<option value="<?= (int)$m["id"] ?>" <?= ((string)$material_id === (string)$m["id"]) ? "selected" : "" ?>>
<?= htmlspecialchars($m["codigo"]) ?> · <?= htmlspecialchars($m["nome"]) ?>
</option>
<?php endforeach; ?>
</select>
</div>
 
    <div class="col-md-2">
<select class="form-select" name="tipo">
<option value="">Todos</option>
<option value="emprestimo" <?= ($tipo === "emprestimo") ? "selected" : "" ?>>Empréstimo</option>
<option value="devolucao" <?= ($tipo === "devolucao") ? "selected" : "" ?>>Devolução</option>
</select>
</div>
 
    <div class="col-md-3">
<input class="form-control" name="search" placeholder="Pesquisar" value="<?= htmlspecialchars($search) ?>">
</div>
 
    <div class="col-md-2">
      <input type="date" class="form-control" name="data_inicio" value="<?= htmlspecialchars($data_inicio) ?>" title="Data início">
    </div>

    <div class="col-md-2">
      <input type="date" class="form-control" name="data_fim" value="<?= htmlspecialchars($data_fim) ?>" title="Data fim">
    </div>

    <div class="col-md-2 d-grid">
      <button class="btn btn-primary">Filtrar</button>
    </div>
    <div class="col-md-2 d-grid">
<a class="btn btn-outline-success"
     href="historico_exportar.php?<?= http_build_query($_GET) ?>">
    Exportar CSV
</a>
</div>
</form>

  <div class="mb-3">
    <a class="btn btn-outline-secondary btn-sm" href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/historico.php">Limpar filtros</a>
  </div>
 
  <div class="table-responsive">
<table class="table table-striped align-middle">
<thead>
<tr>
<th>Data</th>
<th>Tipo</th>
<th>Material</th>
<th>Responsável</th>
<th>Registado por</th>
<th>Observação</th>
</tr>
</thead>
<tbody>
<?php foreach ($rows as $r): ?>
<tr>
<td><?= htmlspecialchars(date("d/m/Y H:i", strtotime($r["data_movimento"]))) ?></td>
<td>
<?php
              $badge = ($r["tipo"] === "emprestimo") ? "warning" : "success";
              $label = ($r["tipo"] === "emprestimo") ? "Empréstimo" : "Devolução";
            ?>
<span class="badge text-bg-<?= $badge ?>"><?= $label ?></span>
</td>
<td><?= htmlspecialchars($r["codigo"]) ?> · <?= htmlspecialchars($r["nome"]) ?></td>
<td><?= htmlspecialchars($r["responsavel"]) ?></td>
<td><?= htmlspecialchars($r["registado_por"] ?? "—") ?></td>
<td><?= htmlspecialchars($r["observacao"] ?? "") ?></td>
</tr>
<?php endforeach; ?>
 
      <?php if (count($rows) === 0): ?>
<tr>
<td colspan="6" class="text-center text-muted">Sem registos.</td>
</tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
 
<?php require_once __DIR__ . "/includes/footer.php"; ?>