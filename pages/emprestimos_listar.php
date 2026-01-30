<?php
require_once __DIR__ . "/auth/guard.php";
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/includes/navbar.php";
require_once __DIR__ . "/includes/mensagens.php";

$mensagem = get_mensagem();

$search = trim($_GET["search"] ?? "");
$estado = trim($_GET["estado"] ?? ""); // emprestimo | devolucao

$sql = "
SELECT
  mov.id,
  mov.material_id,
  mov.tipo,
  mov.responsavel,
  mov.data_movimento,
  m.codigo,
  m.nome
FROM movimentos mov
JOIN materiais m ON m.id = mov.material_id
WHERE 1=1
";

$params = [];

if ($search !== "") {
  $sql .= " AND (m.nome LIKE ? OR m.codigo LIKE ? OR mov.responsavel LIKE ?) ";
  $params[] = "%{$search}%";
  $params[] = "%{$search}%";
  $params[] = "%{$search}%";
}

if ($estado !== "") {
  $sql .= " AND mov.tipo = ? ";
  $params[] = $estado;
}

$sql .= " ORDER BY mov.data_movimento DESC, mov.id DESC ";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$movimentos = $stmt->fetchAll();
?>

<div class="container mt-4">
  <div class="d-flex align-items-center justify-content-between gap-2">
    <h3 class="mb-0">Empréstimos e Devoluções</h3>
    <a class="btn btn-success" href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/emprestar.php">+ Registar empréstimo</a>
  </div>

  <?php if ($mensagem): ?>
    <div class="alert alert-<?= htmlspecialchars($mensagem["tipo"]) ?> mt-3">
      <?= htmlspecialchars($mensagem["texto"]) ?>
    </div>
  <?php endif; ?>

  <form class="row g-2 mt-3 mb-3" method="get">
    <div class="col-md-6">
      <input class="form-control" name="search" placeholder="Pesquisar por material, código ou responsável"
             value="<?= htmlspecialchars($search) ?>">
    </div>

    <div class="col-md-3">
      <select class="form-select" name="estado">
        <option value="">Todos os tipos</option>
        <option value="emprestimo" <?= ($estado === "emprestimo") ? "selected" : "" ?>>Empréstimos</option>
        <option value="devolucao" <?= ($estado === "devolucao") ? "selected" : "" ?>>Devoluções</option>
      </select>
    </div>

    <div class="col-md-3 d-grid">
      <button class="btn btn-primary">Filtrar</button>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-striped align-middle">
      <thead>
        <tr>
          <th>Data</th>
          <th>Tipo</th>
          <th>Material</th>
          <th>Responsável</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($movimentos as $mv): ?>
        <tr>
          <td><?= htmlspecialchars(date("d/m/Y H:i", strtotime($mv["data_movimento"]))) ?></td>
          <td>
            <?php
              $badge = ($mv["tipo"] === "emprestimo") ? "warning" : "success";
              $label = ($mv["tipo"] === "emprestimo") ? "Empréstimo" : "Devolução";
            ?>
            <span class="badge text-bg-<?= $badge ?>"><?= $label ?></span>
          </td>
          <td><?= htmlspecialchars($mv["codigo"]) ?> · <?= htmlspecialchars($mv["nome"]) ?></td>
          <td><?= htmlspecialchars($mv["responsavel"]) ?></td>
          <td>
            <a class="btn btn-sm btn-outline-secondary"
               href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/historico.php?material_id=<?= (int)$mv["material_id"] ?>">
              Ver histórico
            </a>
          </td>
        </tr>
      <?php endforeach; ?>

      <?php if (count($movimentos) === 0): ?>
        <tr>
          <td colspan="5" class="text-center text-muted">Sem registos.</td>
        </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
