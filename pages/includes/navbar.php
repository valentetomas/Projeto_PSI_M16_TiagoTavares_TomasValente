<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
 
// Caminho base do projeto
$base_url = "/Projeto_PSI_M16_TiagoTavares_TomasValente";
?>
 
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
<div class="container">
<a class="navbar-brand d-flex align-items-center gap-3" href="<?= $base_url ?>/pages/index.php">
<img src="<?= $base_url ?>/pages/img/logo_aeaav.png" alt="AEAAV Digital">
<span class="d-none d-sm-block border-start ps-3 border-secondary text-white small" style="line-height: 1.2;">
                CTE<br><span style="color: #22c55e; font-weight: 700;">INVENTÁRIO</span>
</span>
</a>
 
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Toggle navigation">
<span class="navbar-toggler-icon"></span>
</button>
 
        <div class="collapse navbar-collapse" id="nav">
<ul class="navbar-nav mx-auto mb-2 mb-lg-0">
<?php if (isset($_SESSION["user_id"])): ?>
<li class="nav-item">
<a class="nav-link" href="<?= $base_url ?>/pages/listar.php">
<i class="fas fa-boxes-stacked me-1"></i> Materiais
</a>
</li>
<?php if (($_SESSION["user_role"] ?? "") === "admin"): ?>
<li class="nav-item">
<a class="nav-link" href="<?= $base_url ?>/pages/emprestimos_listar.php">
<i class="fas fa-exchange-alt me-1"></i> Movimentos
</a>
</li>
<li class="nav-item">
<a class="nav-link" href="<?= $base_url ?>/pages/emprestimos_ativos.php">
<i class="fas fa-clock me-1"></i> Ativos
</a>
</li>
<?php endif; ?>
<li class="nav-item">
<a class="nav-link" href="<?= $base_url ?>/pages/historico.php">
<i class="fas fa-history me-1"></i> Histórico
</a>
</li>
<?php endif; ?>
</ul>
 
            <ul class="navbar-nav align-items-center">
<?php if (isset($_SESSION["user_id"])): ?>
<?php if (($_SESSION["user_role"] ?? "") === "admin"): ?>
<li class="nav-item me-3">
<a class="btn btn-sm btn-outline-light rounded-pill" href="<?= $base_url ?>/pages/utilizadores_criar.php">
<i class="fas fa-user-plus me-1"></i> Novo User
</a>
</li>
<?php endif; ?>
 
                    <li class="nav-item dropdown">
<a class="nav-link dropdown-toggle user-pill" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
<div class="d-flex flex-column align-items-start" style="line-height: 1;">
<span class="text-white small fw-bold"><?= htmlspecialchars($_SESSION["user_nome"]) ?></span>
<span class="user-role-badge mt-1"><?= htmlspecialchars($_SESSION["user_role"]) ?></span>
</div>
</a>
<ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end shadow">
<li>
<a class="dropdown-item text-danger" href="<?= $base_url ?>/pages/auth/logout.php">
<i class="fas fa-sign-out-alt me-2"></i> Sair
</a>
</li>
</ul>
</li>
 
                <?php else: ?>
<li class="nav-item">
<a class="btn btn-gradient rounded-pill px-4" href="<?= $base_url ?>/pages/auth/login.php">
<i class="fas fa-sign-in-alt me-2"></i> Entrar
</a>
</li>
<?php endif; ?>
</ul>
</div>
</div>
</nav>