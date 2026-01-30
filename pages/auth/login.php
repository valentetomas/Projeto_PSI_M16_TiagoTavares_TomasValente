<?php

require_once __DIR__ . "/../includes/db.php";

require_once __DIR__ . "/../includes/header.php";
 
session_start();

$erro = "";
 
if ($_SERVER["REQUEST_METHOD"] === "POST") {

  $email = trim($_POST["email"] ?? "");

  $password = $_POST["password"] ?? "";
 
  $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");

  $stmt->execute([$email]);

  $user = $stmt->fetch();
 
if ($user && password_verify($password, $user["password"])) {

    $_SESSION["user_id"] = $user["id"];

    $_SESSION["user_nome"] = $user["nome"];

    $_SESSION["user_role"] = $user["role"];
 
    header("Location: /Projeto_PSI_M16_TiagoTavares_TomasValente/pages/listar.php");

    exit;

  } else {

    $erro = "Email ou password incorretos.";

  }

}

?>
 
<div class="container mt-5" style="max-width: 420px;">
<h3>Login</h3>
 
  <?php if ($erro): ?>
<div class="alert alert-danger mt-3"><?= htmlspecialchars($erro) ?></div>
<?php endif; ?>
 
  <form method="post" class="mt-3">
<div class="mb-3">
<label class="form-label">Email</label>
<input type="email" name="email" class="form-control" required>
</div>
 
    <div class="mb-3">
<label class="form-label">Password</label>
<input type="password" name="password" class="form-control" required>
</div>
 
    <button class="btn btn-primary w-100">Entrar</button>
</form>
</div>
<div class="text-center mt-3">
<a href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/index.php">Voltar ao início</a>
</div>
 
<?php require_once __DIR__ . "/../includes/footer.php"; ?>

 