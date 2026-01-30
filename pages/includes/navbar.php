<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
?>
 
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
<div class="container">
<a class="navbar-brand" href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/index.php">
      CTE Inventário
</a>
 
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
<span class="navbar-toggler-icon"></span>
</button>
 
    <div class="collapse navbar-collapse" id="nav">
<ul class="navbar-nav me-auto">
<?php if (isset($_SESSION["user_id"])): ?>
<li class="nav-item">
<a class="nav-link" href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/listar.php">Materiais</a>
</li>

<li class="nav-item">
  <a class="nav-link" href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/emprestimos_listar.php">Empréstimos</a>
</li>

<li class="nav-item">
  <a class="nav-link" href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/emprestimos_ativos.php">Ativos</a>
</li>

<li class="nav-item">
  <a class="nav-link" href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/historico.php">Histórico</a>
</li>
<?php endif; ?>
</ul>
 
      <ul class="navbar-nav">
<?php if (isset($_SESSION["user_id"])): ?>
<?php if (($_SESSION["user_role"] ?? "") === "admin"): ?>
<li class="nav-item">
<a class="nav-link" href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/utilizadores_criar.php">
      Criar utilizador
</a>
</li>
<?php endif; ?>
<li class="nav-item">
<span class="navbar-text me-3">
<?= htmlspecialchars($_SESSION["user_nome"]) ?> (<?= htmlspecialchars($_SESSION["user_role"]) ?>)
</span>
</li>
<li class="nav-item">
<a class="nav-link" href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/auth/logout.php">Logout</a>
</li>
<?php else: ?>
    
<li class="nav-item">
<a class="nav-link" href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/auth/login.php">Login</a>
</li>
<?php endif; ?>
</ul>
</div>
</div>
</nav>