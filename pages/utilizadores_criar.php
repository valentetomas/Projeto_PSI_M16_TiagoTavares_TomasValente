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
        $erro = "Por favor, preencha todos os campos obrigatórios.";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "O endereço de email introduzido é inválido.";
    } else if (!in_array($role, ["admin", "user"], true)) {
        $erro = "O perfil selecionado não é válido.";
    } else {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (nome, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nome, $email, $hash, $role]);

            set_mensagem("success", "Novo utilizador registado com sucesso.");
            // Redireciona para listar.php ou uma lista de utilizadores se tiveres
            header("Location: /Projeto_PSI_M16_TiagoTavares_TomasValente/pages/listar.php");
            exit;
        } catch (PDOException $e) {
            // Verifica se é erro de duplicado (email já existe)
            if ($e->getCode() == 23000) {
                $erro = "Este endereço de email já se encontra registado no sistema.";
            } else {
                $erro = "Erro ao criar utilizador: " . $e->getMessage();
            }
        }
    }
}
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

    .input-group-text {
        background-color: rgba(15, 23, 42, 0.6);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-right: none;
        color: #94a3b8;
    }
   
    .form-control, .form-select {
        background-color: rgba(15, 23, 42, 0.6);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #fff;
        padding: 0.75rem 1rem;
    }
   
    .form-control:focus, .form-select:focus {
        background-color: rgba(15, 23, 42, 0.8);
        border-color: #38bdf8;
        color: #fff;
        box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.2);
    }
   
    .form-control::placeholder { color: #475569; }
    .form-select option { background-color: #1e293b; color: white; }

    /* Info Cards inside Form */
    .role-info-card {
        background-color: rgba(255, 255, 255, 0.03);
        border: 1px dashed rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        padding: 1rem;
        margin-top: 0.5rem;
    }

    /* Buttons */
    .btn-submit {
        background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); /* Blue Sky */
        border: none;
        color: white;
        font-weight: 600;
        padding: 0.75rem;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    .btn-submit:hover {
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
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
</style>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
           
            <div class="mb-4 text-center">
                <h2 class="h3 fw-bold text-white mb-1">Novo Utilizador</h2>
                <p class="text-secondary small">Adicione administradores ou utilizadores padrão ao sistema.</p>
            </div>

            <?php if ($erro): ?>
                <div class="alert alert-custom-danger d-flex align-items-center mb-4 shadow-sm" role="alert">
                    <i class="fas fa-exclamation-circle me-3 fa-lg"></i>
                    <div><?= htmlspecialchars($erro) ?></div>
                </div>
            <?php endif; ?>

            <div class="glass-card p-4 p-md-5">
                <form method="post" autocomplete="off">
                   
                    <div class="mb-4">
                        <label class="form-label">Nome Completo</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control border-start-0" name="nome"
                                   placeholder="Ex: Maria Santos" required
                                   value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Email Institucional</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control border-start-0" name="email"
                                   placeholder="nome@escola.pt" required
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Palavra-passe</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control border-start-0" name="password"
                                   placeholder="Defina uma senha segura" required>
                        </div>
                        <div class="mt-1 ms-1 text-secondary opacity-75 small">
                            <i class="fas fa-info-circle me-1"></i>Recomendado: Mínimo 8 caracteres.
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Nível de Acesso</label>
                        <select class="form-select" name="role" id="roleSelect">
                            <option value="user">Utilizador (Padrão)</option>
                            <option value="admin">Administrador</option>
                        </select>
                       
                        <div class="role-info-card">
                            <div class="d-flex align-items-start gap-3">
                                <i class="fas fa-shield-alt text-info mt-1"></i>
                                <div>
                                    <strong class="d-block text-white small mb-1">Permissões de Acesso</strong>
                                    <p class="mb-0 text-secondary small opacity-75" style="line-height: 1.4;">
                                        Utilizadores padrão podem consultar materiais e realizar empréstimos, mas não podem criar ou apagar itens e utilizadores do sistema.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="border-secondary opacity-25 my-4">

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-submit shadow-sm">
                            <i class="fas fa-user-plus me-2"></i>Criar Conta
                        </button>
                        <a href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/listar.php" class="btn btn-cancel">
                            Cancelar
                        </a>
                    </div>

                </form>
            </div>

            <div class="text-center mt-4">
                <small class="text-secondary opacity-50">
                    <i class="fas fa-lock me-1"></i> Área segura · Apenas Administradores
                </small>
            </div>
           
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>