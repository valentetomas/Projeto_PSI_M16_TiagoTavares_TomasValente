<?php
require_once __DIR__ . "/auth/guard.php";
require_once __DIR__ . "/includes/db.php";
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

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    /* --- DARK MODE STYLES --- */
    body {
        background-color: #0f172a;
        color: #e2e8f0;
    }

    /* Glass Card */
    .glass-card {
        background: rgba(30, 41, 59, 0.7);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 16px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
    }

    /* Form Elements */
    .form-label {
        font-weight: 600;
        color: #94a3b8;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
    }

    .form-control, .form-select {
        background-color: rgba(15, 23, 42, 0.6);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #fff;
        padding: 0.75rem 1rem;
        border-radius: 8px;
    }
   
    .form-control:focus, .form-select:focus {
        background-color: rgba(15, 23, 42, 0.8);
        border-color: #10b981; /* Verde Emerald */
        color: #fff;
        box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
    }
   
    .form-control::placeholder { color: #475569; }
    .form-select option { background-color: #1e293b; color: white; }

    /* Buttons */
    .btn-submit {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border: none;
        color: white;
        font-weight: 600;
        padding: 0.75rem;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    .btn-submit:hover {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }
    .btn-submit:disabled {
        background: #334155;
        transform: none;
        cursor: not-allowed;
    }

    .btn-cancel {
        background: transparent;
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #94a3b8;
        padding: 0.75rem;
        border-radius: 8px;
        transition: all 0.2s;
        text-decoration: none;
        text-align: center;
        display: inline-block;
    }
    .btn-cancel:hover {
        border-color: #fff;
        color: #fff;
        background: rgba(255,255,255,0.05);
    }

    /* Alerts */
    .alert-custom-danger {
        background-color: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.2);
        color: #fca5a5;
        border-radius: 8px;
    }
    .alert-custom-warning {
        background-color: rgba(245, 158, 11, 0.1);
        border: 1px solid rgba(245, 158, 11, 0.2);
        color: #fcd34d;
        border-radius: 8px;
    }

    /* Icons inside inputs wrapper */
    .input-icon-wrapper { position: relative; }
    .input-icon {
        position: absolute;
        top: 50%;
        left: 1rem;
        transform: translateY(-50%);
        color: #64748b;
        z-index: 10;
    }
    .with-icon { padding-left: 2.5rem; }
</style>

<div class="container mt-5 mb-5" style="max-width: 600px;">
   
    <div class="mb-4">
        <h2 class="h3 fw-bold text-white mb-1">Novo Empréstimo</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="#" class="text-secondary text-decoration-none">Início</a></li>
                <li class="breadcrumb-item"><a href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/emprestimos_listar.php" class="text-secondary text-decoration-none">Empréstimos</a></li>
                <li class="breadcrumb-item active text-light opacity-75">Registar</li>
            </ol>
        </nav>
    </div>

    <div class="glass-card p-4 p-md-5">
       
        <?php if ($erro): ?>
            <div class="alert alert-custom-danger mb-4 d-flex align-items-center">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= htmlspecialchars($erro) ?>
            </div>
        <?php endif; ?>

        <?php if (count($materiais) === 0): ?>
            <div class="text-center py-4">
                <div class="mb-3 text-warning opacity-75">
                    <i class="fas fa-box-open fa-3x"></i>
                </div>
                <h5 class="text-white">Sem materiais disponíveis</h5>
                <p class="text-secondary small">Todos os materiais estão emprestados ou indisponíveis no momento.</p>
                <a href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/emprestimos_listar.php" class="btn btn-cancel mt-2">Voltar à lista</a>
            </div>
        <?php else: ?>

            <form method="post" onsubmit="this.querySelector('button[type=submit]').disabled=true;">
               
                <div class="mb-4">
                    <label class="form-label">Material (Disponível)</label>
                    <div class="input-icon-wrapper">
                        <select class="form-select" name="material_id" required>
                            <option value="">Selecione o material...</option>
                            <?php foreach ($materiais as $m): ?>
                                <option value="<?= (int)$m["id"] ?>" <?= ($preselect_id > 0 && (int)$m["id"] === $preselect_id) ? "selected" : "" ?>>
                                    [<?= htmlspecialchars($m["codigo"]) ?>] <?= htmlspecialchars($m["nome"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Responsável (Requisitante)</label>
                    <div class="input-icon-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" class="form-control with-icon" name="responsavel" placeholder="Ex: João Silva / 12º A" required>
                    </div>
                    <div class="form-text text-secondary small opacity-75 mt-1">
                        Indique o nome do aluno, professor ou funcionário.
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Observações (Opcional)</label>
                    <textarea class="form-control" name="observacao" rows="3" maxlength="255" placeholder="Ex: Para aula de TIC, sala 2.14..."></textarea>
                </div>

                <hr class="border-secondary opacity-25 my-4">

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/emprestimos_listar.php" class="btn btn-cancel px-4 order-2 order-md-1">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-submit px-4 order-1 order-md-2">
                        <i class="fas fa-check me-2"></i> Confirmar Empréstimo
                    </button>
                </div>

            </form>

        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>