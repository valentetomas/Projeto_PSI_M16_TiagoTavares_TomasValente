<?php
session_start();
require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/includes/navbar.php";
?>
 
<div class="container mt-4">
<h3>CTE Inventário</h3>
<p>Aplicação de gestão e inventário de materiais do CTE Informática.</p>
 
  <?php if (isset($_SESSION["user_id"])): ?>
<a class="btn btn-primary" href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/listar.php">
      Entrar no sistema
</a>

<?php endif; ?>
</div>
 
<?php require_once __DIR__ . "/includes/footer.php"; ?>