<?php

require_once __DIR__ . "/auth/admin_guard.php";

require_once __DIR__ . "/includes/db.php";

require_once __DIR__ . "/includes/mensagens.php";

require_once __DIR__ . "/includes/header.php";

require_once __DIR__ . "/includes/navbar.php";
 
$erro = "";
 
if ($_SERVER["REQUEST_METHOD"] === "POST") {

  $nome = trim($_POST["nome"] ?? "");

  $email = trim($_POST["email"] ?? "");

  $password = $_POST["password"] ?? "";

  $role = $_POST["role"] ?? "user";
 
  if ($nome === "" || $email === "" || $password === "") {

    $erro = "Preenche todos os campos.";

  } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    $erro = "Email inválido.";

  } else if (!in_array($role, ["admin", "user"], true)) {

    $erro = "Perfil inválido.";

  } else {

    try {

      $hash = password_hash($password, PASSWORD_DEFAULT);
 
      $stmt = $pdo->prepare("INSERT INTO users (nome, email, password, role) VALUES (?, ?, ?, ?)");

      $stmt->execute([$nome, $email, $hash, $role]);
 
      set_mensagem("success", "Utilizador criado com sucesso.");

      header("Location: /Projeto_PSI_M16_TiagoTavares_TomasValente/pages/listar.php");

      exit;
 
    } catch (PDOException $e) {

      $erro = "Não foi possível criar. Confirma se o email já existe.";

    }

  }

}

?>
 
<div class="container mt-4" style="max-width: 650px;">
<h3>Criar utilizador</h3>
 
  <?php if ($erro): ?>
<div class="alert alert-danger mt-3"><?= htmlspecialchars($erro) ?></div>
<?php endif; ?>
 
  <form method="post" class="mt-3">
<div class="mb-3">
<label class="form-label">Nome</label>
<input class="form-control" name="nome" required>
</div>
 
    <div class="mb-3">
<label class="form-label">Email</label>
<input type="email" class="form-control" name="email" required>
</div>
 
    <div class="mb-3">
<label class="form-label">Password</label>
<input type="password" class="form-control" name="password" required>
</div>
 
    <div class="mb-3">
<label class="form-label">Perfil</label>
<select class="form-select" name="role">
<option value="user">Utilizador</option>
<option value="admin">Administrador</option>
</select>
</div>
 
    <div class="d-flex gap-2">
<button class="btn btn-success">Criar</button>
<a class="btn btn-outline-secondary"

         href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/listar.php">Voltar</a>
</div>
</form>
</div>
 
<?php require_once __DIR__ . "/includes/footer.php"; ?>

 