<?php
require_once __DIR__ . "/auth/guard.php";
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/mensagens.php";
 
$erro = "";
 
$material_id = (int)($_GET["material_id"] ?? ($_POST["material_id"] ?? 0));
 
// buscar material (com info de empréstimo)
$stmt = $pdo->prepare("SELECT id, codigo, nome, estado, emprestado_a, emprestado_em FROM materiais WHERE id = ?");
$stmt->execute([$material_id]);
$material = $stmt->fetch();
 
if (!$material) {
  $erro = "Material não encontrado.";
}
 
// pré-preencher responsável com quem estava com o material
$resp_default = "";
if ($material && ($material["estado"] ?? "") === "Emprestado") {
  $resp_default = trim((string)($material["emprestado_a"] ?? ""));
}
 
// se não estiver emprestado, bloquear já (mais eficiente)
if ($material && ($material["estado"] ?? "") !== "Emprestado" && $erro === "") {
  $erro = "Este material não está marcado como Emprestado.";
}
 
if ($_SERVER["REQUEST_METHOD"] === "POST" && !$erro) {
  $responsavel = trim($_POST["responsavel"] ?? "");
  $observacao = trim($_POST["observacao"] ?? "");
  $user_id = (int)($_SESSION["user_id"] ?? 0);
  $user_id = $user_id > 0 ? $user_id : null;
 
  if ($responsavel === "") {
    $erro = "Indica o responsável pela devolução.";
  } else {
    try {
      $pdo->beginTransaction();
 
      // lock + validar estado
      $stmt = $pdo->prepare("SELECT estado FROM materiais WHERE id = ? FOR UPDATE");
      $stmt->execute([$material_id]);
      $row = $stmt->fetch();
 
      if (!$row) {
        throw new Exception("Material não encontrado.");
      }
 
      if ($row["estado"] !== "Emprestado") {
        throw new Exception("Este material não está marcado como Emprestado.");
      }
 
      // registar movimento (com user_id e observacao)
      $stmt = $pdo->prepare("
        INSERT INTO movimentos (material_id, tipo, responsavel, user_id, observacao, data_movimento)
        VALUES (?, 'devolucao', ?, ?, ?, NOW())
      ");
      $stmt->execute([
        $material_id,
        $responsavel,
        $user_id,
        $observacao !== "" ? $observacao : null
      ]);
 
      // atualizar estado + limpar campos de empréstimo
      $stmt = $pdo->prepare("
        UPDATE materiais
        SET estado = 'Disponível', emprestado_a = NULL, emprestado_em = NULL
        WHERE id = ?
      ");
      $stmt->execute([$material_id]);
 
      $pdo->commit();
 
      set_mensagem("success", "Devolução registada com sucesso.");
      $is_admin = (($_SESSION["user_role"] ?? "") === "admin");
      $redirect = $is_admin
        ? "/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/emprestimos_listar.php"
        : "/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/historico.php";
      header("Location: " . $redirect);
      exit;
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $erro = $e->getMessage();
    }
  }
}
?>

<?php
require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/includes/navbar.php";
?>
 
<div class="container mt-4" style="max-width: 750px;">
<h3>Registar devolução</h3>
 
  <?php if ($erro): ?>
<div class="alert alert-danger mt-3"><?= htmlspecialchars($erro) ?></div>
<?php endif; ?>
 
  <?php if ($material): ?>
<div class="card mt-3">
<div class="card-body">
<div><strong>Material:</strong> <?= htmlspecialchars($material["codigo"]) ?> · <?= htmlspecialchars($material["nome"]) ?></div>
<div><strong>Estado atual:</strong> <?= htmlspecialchars($material["estado"]) ?></div>
 
        <?php if (!empty($material["emprestado_a"])): ?>
<div><strong>Emprestado a:</strong> <?= htmlspecialchars($material["emprestado_a"]) ?></div>
<?php endif; ?>
 
        <?php if (!empty($material["emprestado_em"])): ?>
<div class="text-muted" style="font-size:0.95em;">
<strong>Desde:</strong> <?= htmlspecialchars(date("d/m/Y H:i", strtotime($material["emprestado_em"]))) ?>
</div>
<?php endif; ?>
</div>
</div>
 
    <?php if (!$erro): ?>
<form method="post" class="mt-3" onsubmit="this.querySelector('button[type=submit]').disabled=true;">
<input type="hidden" name="material_id" value="<?= (int)$material["id"] ?>">
 
        <div class="row g-3">
<div class="col-md-6">
<label class="form-label">Responsável (quem devolveu)</label>
<input class="form-control" name="responsavel"
                   value="<?= htmlspecialchars($resp_default) ?>"
                   placeholder="Ex: João Silva / 3ºF" required>
</div>
 
          <div class="col-md-6">
<label class="form-label">Observação (opcional)</label>
<input class="form-control" name="observacao" maxlength="255" placeholder="Ex: Devolvido em boas condições">
</div>
</div>
 
        <div class="mt-4 d-flex gap-2">
<button type="submit" class="btn btn-success">Confirmar devolução</button>
<a class="btn btn-outline-secondary" href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/emprestimos_listar.php">Voltar</a>
</div>
</form>
<?php endif; ?>
<?php endif; ?>
</div>
 
<?php require_once __DIR__ . "/includes/footer.php"; ?>