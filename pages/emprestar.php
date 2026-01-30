<?php
require_once __DIR__ . "/auth/guard.php";
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/includes/navbar.php";
require_once __DIR__ . "/includes/mensagens.php";
 
$erro = "";
 
// material pré-selecionado via GET (ex: vindo do listar.php)
$preselect_id = (int)($_GET["material_id"] ?? 0);
 
// materiais disponíveis
$materiais = $pdo->query("SELECT id, codigo, nome FROM materiais WHERE estado = 'Disponível' ORDER BY nome, codigo")->fetchAll();
 
// validar se o id pré-selecionado existe na lista de disponíveis
if ($preselect_id > 0) {
  $ok = false;
  foreach ($materiais as $m) {
    if ((int)$m["id"] === $preselect_id) {
      $ok = true;
      break;
    }
  }
  if (!$ok) {
    $erro = "O material selecionado já não está disponível para empréstimo.";
    $preselect_id = 0;
  }
}
 
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $material_id = (int)($_POST["material_id"] ?? 0);
  $responsavel = trim($_POST["responsavel"] ?? "");
  $observacao = trim($_POST["observacao"] ?? "");
  $user_id = (int)($_SESSION["user_id"] ?? 0);
  $user_id = $user_id > 0 ? $user_id : null;
 
  if ($material_id <= 0 || $responsavel === "") {
    $erro = "Seleciona um material e indica o responsável.";
  } else {
    try {
      $pdo->beginTransaction();
 
      // garantir que continua disponível
      $stmt = $pdo->prepare("SELECT estado FROM materiais WHERE id = ? FOR UPDATE");
      $stmt->execute([$material_id]);
      $row = $stmt->fetch();
 
      if (!$row) {
        throw new Exception("Material não encontrado.");
      }
 
      if ($row["estado"] !== "Disponível") {
        throw new Exception("Este material já não está disponível.");
      }
 
      // registar movimento (com user_id e observacao)
      $stmt = $pdo->prepare("
        INSERT INTO movimentos (material_id, tipo, responsavel, user_id, observacao, data_movimento)
        VALUES (?, 'emprestimo', ?, ?, ?, NOW())
      ");
      $stmt->execute([
        $material_id,
        $responsavel,
        $user_id,
        $observacao !== "" ? $observacao : null
      ]);
 
      // atualizar estado + guardar quem ficou com o material
      $stmt = $pdo->prepare("
        UPDATE materiais
        SET estado = 'Emprestado', emprestado_a = ?, emprestado_em = NOW()
        WHERE id = ?
      ");
      $stmt->execute([$responsavel, $material_id]);
 
      $pdo->commit();
 
      set_mensagem("success", "Empréstimo registado com sucesso.");
      header("Location: /Projeto_PSI_M16_TiagoTavares_TomasValente/pages/emprestimos_listar.php");
      exit;
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $erro = $e->getMessage();
    }
  }
}
?>
 
<div class="container mt-4" style="max-width: 750px;">
<h3>Registar empréstimo</h3>
 
  <?php if ($erro): ?>
<div class="alert alert-danger mt-3"><?= htmlspecialchars($erro) ?></div>
<?php endif; ?>
 
  <?php if (count($materiais) === 0): ?>
<div class="alert alert-warning mt-3">
      Não existem materiais disponíveis para empréstimo.
</div>
<?php endif; ?>
 
  <form method="post" class="mt-3" onsubmit="this.querySelector('button[type=submit]').disabled=true;">
<div class="row g-3">
<div class="col-md-7">
<label class="form-label">Material (apenas disponíveis)</label>
<select class="form-select" name="material_id" required <?= count($materiais) === 0 ? "disabled" : "" ?>>
<option value="">Selecionar</option>
<?php foreach ($materiais as $m): ?>
<option value="<?= (int)$m["id"] ?>" <?= ($preselect_id > 0 && (int)$m["id"] === $preselect_id) ? "selected" : "" ?>>
<?= htmlspecialchars($m["codigo"]) ?> · <?= htmlspecialchars($m["nome"]) ?>
</option>
<?php endforeach; ?>
</select>
</div>
 
      <div class="col-md-5">
<label class="form-label">Responsável (quem requisitou)</label>
<input class="form-control" name="responsavel" placeholder="Ex: João Silva / 3ºF" required>
</div>
 
      <div class="col-12">
<label class="form-label">Observação (opcional)</label>
<input class="form-control" name="observacao" maxlength="255" placeholder="Ex: Para aula de redes / sala 2.14">
</div>
</div>
 
    <div class="mt-4 d-flex gap-2">
<button type="submit" class="btn btn-success" <?= count($materiais) === 0 ? "disabled" : "" ?>>Guardar</button>
<a class="btn btn-outline-secondary" href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/emprestimos_listar.php">Voltar</a>
</div>
</form>
</div>
 
<?php require_once __DIR__ . "/includes/footer.php"; ?>