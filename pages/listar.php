<?php
// pages/materiais/listar.php
 
require_once __DIR__ . "/auth/guard.php";
require_once __DIR__ . "/includes/db.php";     // ligação PDO
require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/includes/navbar.php";
require_once __DIR__ . "/includes/mensagens.php";
$mensagem = get_mensagem();
 
// regra simples de atraso (dias)
$limite_atraso = 7;
 
// filtros
$search = trim($_GET["search"] ?? "");
$categoria = trim($_GET["categoria"] ?? "");
$estado_filtro = trim($_GET["estado"] ?? "");
 
// categorias para o dropdown
$cats = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome")->fetchAll();
 
// query base
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
WHERE 1=1
";
$params = [];
 
// pesquisa por nome/código
if ($search !== "") {
  $sql .= " AND (m.nome LIKE ? OR m.codigo LIKE ?) ";
  $params[] = "%{$search}%";
  $params[] = "%{$search}%";
}
 
// filtro por categoria
if ($categoria !== "") {
  $sql .= " AND m.categoria_id = ? ";
  $params[] = $categoria;
}
 
// filtro por estado
if ($estado_filtro !== "") {
  $sql .= " AND m.estado = ? ";
  $params[] = $estado_filtro;
}
 
$sql .= " ORDER BY l.nome, c.nome, m.nome, m.codigo ";
 
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$materiais = $stmt->fetchAll();
?>
 
<div class="container mt-4">
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
<h3 class="m-0">Materiais</h3>
 
    <?php if (($_SESSION["user_role"] ?? "") === "admin"): ?>
<a class="btn btn-success" href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/criar.php">
        + Criar material
</a>
<?php endif; ?>
</div>
 
  <?php if ($mensagem): ?>
<div class="alert alert-<?= htmlspecialchars($mensagem["tipo"]) ?> mt-3">
<?= htmlspecialchars($mensagem["texto"]) ?>
</div>
<?php endif; ?>
 
  <form class="row g-2 mt-3 mb-3" method="get">
<div class="col-md-5">
<input class="form-control" name="search" placeholder="Pesquisar por nome ou código"
             value="<?= htmlspecialchars($search) ?>">
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
 
    <div class="col-md-2">
<select class="form-select" name="estado">
<option value="">Todos os estados</option>
<?php foreach (["Disponível","Emprestado","Avariado","Perdido"] as $e): ?>
<option value="<?= $e ?>" <?= ($estado_filtro === $e) ? "selected" : "" ?>><?= $e ?></option>
<?php endforeach; ?>
</select>
</div>
 
    <div class="col-md-2 d-grid">
<button class="btn btn-primary">Filtrar</button>
</div>
</form>
 
  <div class="table-responsive">
<table class="table table-striped align-middle">
<thead>
<tr>
<th>Código</th>
<th>Nome</th>
<th>Categoria</th>
<th>Localização</th>
<th>Estado</th>
<th>Empréstimo</th>
<th>Ações</th>
</tr>
</thead>
 
      <tbody>
<?php foreach ($materiais as $m): ?>
<?php
          $estado_mat = $m["estado"] ?? "";
          $badge = "secondary";
 
          if ($estado_mat === "Disponível") $badge = "success";
          else if ($estado_mat === "Emprestado") $badge = "warning";
          else if ($estado_mat === "Avariado") $badge = "danger";
          else if ($estado_mat === "Perdido") $badge = "dark";
 
          // dados do empréstimo
          $dias = null;
          if (!empty($m["emprestado_em"])) {
            $dias = (int)floor((time() - strtotime($m["emprestado_em"])) / 86400);
            if ($dias < 0) $dias = 0;
          }
          $atrasado = ($estado_mat === "Emprestado" && $dias !== null && $dias >= $limite_atraso);
 
          $resp = trim((string)($m["emprestado_a"] ?? ""));
        ?>
 
        <tr>
<td><?= htmlspecialchars($m["codigo"]) ?></td>
<td><?= htmlspecialchars($m["nome"]) ?></td>
<td><?= htmlspecialchars($m["categoria"]) ?></td>
<td><?= htmlspecialchars($m["localizacao"]) ?></td>
 
          <td>
<span class="badge text-bg-<?= $badge ?>">
<?= htmlspecialchars($estado_mat) ?>
</span>
</td>
 
          <td>
<?php if ($estado_mat === "Emprestado"): ?>
<div><strong><?= htmlspecialchars($resp !== "" ? $resp : "Não definido") ?></strong></div>
 
              <?php if (!empty($m["emprestado_em"])): ?>
<div class="text-muted" style="font-size: 0.9em;">
                  Desde <?= htmlspecialchars(date("d/m/Y H:i", strtotime($m["emprestado_em"]))) ?>
                  (<?= (int)$dias ?> dia<?= ($dias === 1) ? "" : "s" ?>)
</div>
<?php else: ?>
<div class="text-muted" style="font-size: 0.9em;">Sem data</div>
<?php endif; ?>
 
              <span class="badge text-bg-<?= $atrasado ? "danger" : "warning" ?>">
<?= $atrasado ? "Atrasado" : "Emprestado" ?>
<?php if ($dias !== null): ?>
<span style="font-weight: normal;">· <?= (int)$dias ?>d</span>
<?php endif; ?>
</span>
<?php else: ?>
<span class="text-muted">—</span>
<?php endif; ?>
</td>
 
          <td>
<?php if ($estado_mat === "Disponível"): ?>
<a class="btn btn-sm btn-outline-success"
                 href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/emprestar.php?material_id=<?= (int)$m["id"] ?>">
                Emprestar
</a>
<?php elseif ($estado_mat === "Emprestado"): ?>
<a class="btn btn-sm btn-outline-success"
                 onclick="return confirm('Confirmar devolução deste material?')"
                 href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/devolver.php?material_id=<?= (int)$m["id"] ?>">
                Devolver
</a>
<?php endif; ?>
 
            <a class="btn btn-sm btn-outline-secondary"
               href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/historico.php?material_id=<?= (int)$m["id"] ?>">
              Histórico
</a>
 
            <?php if (($_SESSION["user_role"] ?? "") === "admin"): ?>
<a class="btn btn-sm btn-outline-secondary"
                 href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/editar.php?id=<?= (int)$m["id"] ?>">
                Editar
</a>
 
              <form method="post"
                    action="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/apagar.php"
                    style="display:inline;"
                    onsubmit="return confirm('Tens a certeza que queres apagar este material?');">
<input type="hidden" name="id" value="<?= (int)$m["id"] ?>">
<button class="btn btn-sm btn-outline-danger">Apagar</button>
</form>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
 
      <?php if (count($materiais) === 0): ?>
<tr>
<td colspan="7" class="text-center text-muted">Sem resultados.</td>
</tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
 
<?php require_once __DIR__ . "/includes/footer.php"; ?>