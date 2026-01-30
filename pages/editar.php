<?php
require_once __DIR__ . "/auth/admin_guard.php";
require_once __DIR__ . "/auth/guard.php";
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/includes/navbar.php";
require_once __DIR__ . "/includes/mensagens.php";
 
$erro = "";
 
// validar id
$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) {
  header("Location: /Projeto_PSI_M16_TiagoTavares_TomasValente/pages/listar.php");
  exit;
}
 
// buscar material
$stmt = $pdo->prepare("SELECT * FROM materiais WHERE id = ?");
$stmt->execute([$id]);
$mat = $stmt->fetch();
 
if (!$mat) {
  die("Material não encontrado.");
}
 
// dropdowns
$categorias = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome")->fetchAll();
$localizacoes = $pdo->query("SELECT id, nome FROM localizacoes ORDER BY nome")->fetchAll();
 
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $codigo = trim($_POST["codigo"] ?? "");
  $nome = trim($_POST["nome"] ?? "");
  $categoria_id = (int)($_POST["categoria_id"] ?? 0);
  $localizacao_id = (int)($_POST["localizacao_id"] ?? 0);
  $estado = trim($_POST["estado"] ?? "Disponível");
 
  if ($codigo === "" || $nome === "" || $categoria_id <= 0 || $localizacao_id <= 0) {
    $erro = "Preenche todos os campos obrigatórios.";
  } else {
    try {
      $stmt = $pdo->prepare("
        UPDATE materiais
        SET codigo = ?, nome = ?, categoria_id = ?, localizacao_id = ?, estado = ?
        WHERE id = ?
      ");
      $stmt->execute([$codigo, $nome, $categoria_id, $localizacao_id, $estado, $id]);
 
      set_mensagem("success", "Material editado com sucesso.");
      header("Location: /Projeto_PSI_M16_TiagoTavares_TomasValente/pages/listar.php");
      exit;
 
    } catch (PDOException $e) {
      $erro = "Não foi possível editar. Confirma se o código já existe noutro material.";
    }
  }
}
?>
 
<div class="container mt-4" style="max-width: 750px;">
<h3>Editar material</h3>
 
  <?php if ($erro): ?>
<div class="alert alert-danger mt-3"><?= htmlspecialchars($erro) ?></div>
<?php endif; ?>
 
  <form method="post" class="mt-3">
<div class="row g-3">
<div class="col-md-4">
<label class="form-label">Código</label>
<input class="form-control" name="codigo" value="<?= htmlspecialchars($mat["codigo"]) ?>" required>
</div>
 
      <div class="col-md-8">
<label class="form-label">Nome</label>
<input class="form-control" name="nome" value="<?= htmlspecialchars($mat["nome"]) ?>" required>
</div>
 
      <div class="col-md-6">
<label class="form-label">Categoria</label>
<select class="form-select" name="categoria_id" required>
<?php foreach ($categorias as $c): ?>
<option value="<?= $c["id"] ?>" <?= ($mat["categoria_id"] == $c["id"]) ? "selected" : "" ?>>
<?= htmlspecialchars($c["nome"]) ?>
</option>
<?php endforeach; ?>
</select>
</div>
 
      <div class="col-md-6">
<label class="form-label">Localização</label>
<select class="form-select" name="localizacao_id" required>
<?php foreach ($localizacoes as $l): ?>
<option value="<?= $l["id"] ?>" <?= ($mat["localizacao_id"] == $l["id"]) ? "selected" : "" ?>>
<?= htmlspecialchars($l["nome"]) ?>
</option>
<?php endforeach; ?>
</select>
</div>
 
      <div class="col-md-4">
<label class="form-label">Estado</label>
<select class="form-select" name="estado">
<?php foreach (["Disponível","Emprestado","Avariado","Perdido"] as $e): ?>
<option <?= ($mat["estado"] === $e) ? "selected" : "" ?>><?= $e ?></option>
<?php endforeach; ?>
</select>
</div>
</div>
 
    <div class="mt-4 d-flex gap-2">
<button class="btn btn-primary">Guardar alterações</button>
<a class="btn btn-outline-secondary"
         href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/listar.php">Voltar</a>
</div>
</form>
</div>
 
<?php require_once __DIR__ . "/includes/footer.php"; ?>