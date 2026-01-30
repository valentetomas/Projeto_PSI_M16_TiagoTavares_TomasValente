<?php
require_once __DIR__ . "/auth/admin_guard.php";
require_once __DIR__ . "/auth/guard.php";
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/includes/navbar.php";
require_once __DIR__ . "/includes/mensagens.php";
 
$erro = "";
 
// buscar categorias e localizações para os dropdowns
$categorias = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome")->fetchAll();
$localizacoes = $pdo->query("SELECT id, nome FROM localizacoes ORDER BY nome")->fetchAll();
 
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $codigo = trim($_POST["codigo"] ?? "");
    $nome = trim($_POST["nome"] ?? "");
    $categoria_id = (int)($_POST["categoria_id"] ?? 0);
    $localizacao_id = (int)($_POST["localizacao_id"] ?? 0);
    $estado = trim($_POST["estado"] ?? "Disponível");
 
    if ($codigo === "" || $nome === "" || $categoria_id <= 0 || $localizacao_id <= 0) {
        $erro = "Preencha todos os campos obrigatórios.";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO materiais (codigo, nome, categoria_id, localizacao_id, estado)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$codigo, $nome, $categoria_id, $localizacao_id, $estado]);
 
            set_mensagem("success", "Material criado com sucesso.");
            header("Location: /Projeto_PSI_M16_TiagoTavares_TomasValente/pages/listar.php");
            exit;
        } catch (PDOException $e) {
            $erro = "Erro: O código '<strong>$codigo</strong>' já está registado no sistema.";
        }
    }
}
?>
 
<style>
    body { background-color: #f8fafc; }
    .card-form { border: none; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
    .form-label { font-weight: 600; color: #475569; font-size: 0.9rem; }
    .form-control, .form-select { border-radius: 8px; border: 1px solid #e2e8f0; padding: 0.6rem 1rem; }
    .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
    .btn-save { background-color: #2563eb; border: none; padding: 0.6rem 2rem; border-radius: 8px; font-weight: 600; }
    .btn-save:hover { background-color: #1d4ed8; }
    .section-title { border-left: 4px solid #2563eb; padding-left: 15px; margin-bottom: 25px; }
</style>
 
<div class="container mt-5 mb-5">
<div class="row justify-content-center">
<div class="col-lg-8">
<div class="d-flex justify-content-between align-items-center mb-4">
<div>
<h2 class="h3 fw-bold mb-0 text-dark">Novo Equipamento</h2>
<p class="text-muted small mb-0">Registe novos materiais no inventário escolar</p>
</div>
<a href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/listar.php" class="btn btn-outline-secondary btn-sm">
<i class="fas fa-arrow-left me-2"></i>Voltar à Lista
</a>
</div>
 
            <?php if ($erro): ?>
<div class="alert alert-danger d-flex align-items-center border-0 shadow-sm mb-4" role="alert">
<i class="fas fa-exclamation-circle me-3 fa-lg"></i>
<div><?= $erro ?></div>
</div>
<?php endif; ?>
 
            <div class="card card-form">
<div class="card-body p-4 p-md-5">
<form method="post" autocomplete="off">
<div class="section-title">
<h5 class="mb-0 text-dark">Informações Gerais</h5>
</div>
 
                        <div class="row g-4">
<div class="col-md-4">
<label class="form-label">Código Interno <span class="text-danger">*</span></label>
<div class="input-group">
<span class="input-group-text bg-light text-muted"><i class="fas fa-barcode"></i></span>
<input type="text" class="form-control" name="codigo" 
                                           placeholder="Ex: MAT-2024-001" required 
                                           value="<?= htmlspecialchars($_POST['codigo'] ?? '') ?>">
</div>
</div>
 
                            <div class="col-md-8">
<label class="form-label">Nome do Equipamento <span class="text-danger">*</span></label>
<input type="text" class="form-control" name="nome" 
                                       placeholder="Ex: Projetor Epson X41" required
                                       value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
</div>
 
                            <div class="col-md-6">
<label class="form-label">Categoria <span class="text-danger">*</span></label>
<select class="form-select" name="categoria_id" required>
<option value="">Selecione uma categoria...</option>
<?php foreach ($categorias as $c): ?>
<option value="<?= $c["id"] ?>" <?= (isset($_POST['categoria_id']) && $_POST['categoria_id'] == $c['id']) ? 'selected' : '' ?>>
<?= htmlspecialchars($c["nome"]) ?>
</option>
<?php endforeach; ?>
</select>
</div>
 
                            <div class="col-md-6">
<label class="form-label">Localização Inicial <span class="text-danger">*</span></label>
<select class="form-select" name="local_id" required>
<option value="">Selecione o local...</option>
<?php foreach ($localizacoes as $l): ?>
<option value="<?= $l["id"] ?>" <?= (isset($_POST['local_id']) && $_POST['local_id'] == $l['id']) ? 'selected' : '' ?>>
<?= htmlspecialchars($l["nome"]) ?>
</option>
<?php endforeach; ?>
</select>
</div>
 
                            <div class="col-md-4">
<label class="form-label">Condição Atual</label>
<select class="form-select" name="estado">
<option value="Disponível">Disponível</option>
<option value="Avariado">Avariado</option>
<option value="Perdido">Perdido</option>
</select>
<div class="form-text">Novos materiais entram como "Disponível" por defeito.</div>
</div>
</div>
 
                        <hr class="my-5 opacity-50">
 
                        <div class="d-flex justify-content-end gap-3">
<button type="reset" class="btn btn-light px-4 border">Limpar Campos</button>
<button type="submit" class="btn btn-primary btn-save shadow-sm">
<i class="fas fa-check-circle me-2"></i>Finalizar Cadastro
</button>
</div>
</form>
</div>
</div>
 
            <p class="text-center text-muted mt-4 small">
<i class="fas fa-info-circle me-1"></i> Os campos marcados com <span class="text-danger">*</span> são de preenchimento obrigatório para o inventário.
</p>
 
        </div>
</div>
</div>
 
<?php require_once __DIR__ . "/includes/footer.php"; ?>