<?php
session_start();

require_once __DIR__ . "/../includes/db.php";

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
       
        header("Location: /Projeto_PSI_M16_TiagoTavares_TomasValente/pages/index.php");
        exit;
    } else {
        $erro = "Email ou password incorretos.";
    }
}

require_once __DIR__ . "/../includes/header.php"; // Mantém o header para CSS global e meta tags
?>

<style>
    /* Estilos Específicos para a Página de Login (Criativo e Interativo) */
    body {
        background-color: #0d1a2f; /* Fundo base ainda mais escuro para contraste com partículas */
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        overflow: hidden; /* Evita scrollbars devido às partículas */
        position: relative; /* Para posicionar o canvas */
        color: #e2e8f0; /* Cor de texto padrão clara */
    }

    /* O wrapper do login agora fica por cima do canvas */
    .login-wrapper {
        position: relative; /* Importante para estar acima do canvas */
        z-index: 2;
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .login-card {
        background: rgba(30, 41, 59, 0.6); /* Glassmorphism: Fundo semi-transparente */
        backdrop-filter: blur(15px); /* Efeito de desfoque de vidro */
        border: 1px solid rgba(255, 255, 255, 0.15); /* Borda mais suave */
        border-radius: 20px; /* Cantos mais arredondados */
        padding: 3rem 2.5rem; /* Mais espaço interno */
        width: 100%;
        max-width: 440px; /* Ligeiramente mais largo */
        box-shadow: 0 15px 50px rgba(0, 0, 0, 0.4); /* Sombra mais profunda */
        text-align: center;
        position: relative; /* Para z-index */
        transform: translateY(-20px); /* Começa ligeiramente acima */
        animation: card-appear 0.8s ease-out forwards; /* Animação de entrada */
    }

    @keyframes card-appear {
        from { opacity: 0; transform: translateY(-40px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Logo no Login */
    .login-logo img {
        height: 90px; /* Logo maior e mais proeminente */
        margin-bottom: 2rem; /* Mais espaço */
        filter: drop-shadow(0 0 15px rgba(14, 165, 233, 0.2)); /* Brilho sutil azul */
        animation: logo-bounce 1s ease-out 0.5s both;
        opacity: 1;
    }
   
    @keyframes logo-bounce {
        0% { opacity: 0; transform: scale(0.8); }
        60% { opacity: 1; transform: scale(1.05); }
        100% { opacity: 1; transform: scale(1); }
    }

    .login-title {
        color: white;
        font-weight: 700;
        margin-bottom: 0.8rem;
        font-size: 1.8rem; /* Título maior */
        animation: fade-in 0.6s ease-out forwards;
        animation-delay: 0.8s;
        opacity: 0;
    }
   
    .login-subtitle {
        color: #aebfd9; /* Um cinza-azul mais sofisticado */
        font-size: 1rem;
        margin-bottom: 2.5rem; /* Mais espaço */
        animation: fade-in 0.6s ease-out forwards;
        animation-delay: 1s;
        opacity: 0;
    }

    @keyframes fade-in {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Inputs Customizados */
    .form-label {
        color: #cbd5e1; /* Labels mais visíveis */
        font-weight: 600;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.5rem; /* Mais espaço entre label e input */
    }

    .form-control {
        background-color: rgba(15, 23, 42, 0.4); /* Fundo input mais transparente */
        border: 1px solid rgba(100, 116, 139, 0.4); /* Borda mais suave */
        color: #e2e8f0;
        padding: 14px 18px; /* Mais padding */
        border-radius: 10px; /* Cantos mais arredondados */
        transition: all 0.3s ease;
    }
   
    .form-control:focus {
        background-color: rgba(15, 23, 42, 0.6);
        color: white;
        border-color: #0ea5e9;
        box-shadow: 0 0 0 5px rgba(14, 165, 233, 0.2); /* Sombra de foco mais intensa */
    }
   
    .input-group-text {
        background-color: rgba(15, 23, 42, 0.4);
        border: 1px solid rgba(100, 116, 139, 0.4);
        border-right: none;
        color: #94a3b8; /* Ícone mais visível */
        padding: 0 15px; /* Ajusta padding do ícone */
        border-radius: 10px 0 0 10px;
    }
    .form-control.border-start-0 { border-left: 0; border-radius: 0 10px 10px 0; }


    /* Botão Gradiente */
    .btn-login {
        background: linear-gradient(135deg, #0ea5e9, #22c55e);
        border: none;
        color: white;
        font-weight: 700; /* Mais bold */
        padding: 14px;
        border-radius: 10px; /* Cantos mais arredondados */
        margin-top: 2rem; /* Mais espaço */
        letter-spacing: 0.05em; /* Ligeiro espaçamento */
        text-transform: uppercase;
        transition: transform 0.2s, box-shadow 0.2s, background 0.2s;
        box-shadow: 0 5px 25px rgba(34, 197, 94, 0.3);
    }
   
    .btn-login:hover {
        transform: translateY(-3px) scale(1.01); /* Mais efeito */
        box-shadow: 0 8px 30px rgba(34, 197, 94, 0.5); /* Sombra mais forte */
        background: linear-gradient(135deg, #0ea5e9, #1fb956); /* Ligeira alteração de cor */
    }

    .link-back {
        color: #94a3b8;
        text-decoration: none;
        font-size: 0.95rem;
        margin-top: 2rem;
        display: inline-block;
        transition: color 0.3s ease;
        animation: fade-in 0.6s ease-out forwards;
        animation-delay: 1.2s;
        opacity: 0;
    }
   
    .link-back:hover {
        color: #0ea5e9;
        text-decoration: underline;
    }

    /* Estilo para o alerta de erro */
    .alert-danger {
        background-color: rgba(220, 38, 38, 0.2);
        border-color: rgba(220, 38, 38, 0.4);
        color: #fef2f2;
        padding: 1rem 1.5rem;
        border-radius: 10px;
    }
    .link-back {
        color: #9fb3d3;
    }
</style>

<div class="login-wrapper">
    <div class="login-card">
       
        <div class="login-logo">
            <img src="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/img/logo_aeaav.png" alt="Logo AEAAV">
        </div>

        <h3 class="login-title">Bem-vindo de volta</h3>
        <p class="login-subtitle">Insira as suas credenciais para aceder ao sistema de inventário digital.</p>

        <?php if ($erro): ?>
            <div class="alert alert-danger d-flex align-items-center" role="alert" style="font-size: 0.9rem;">
                <i class="fas fa-exclamation-circle me-2"></i>
                <div><?= htmlspecialchars($erro) ?></div>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-4 text-start">
                <label class="form-label">Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" name="email" class="form-control border-start-0" placeholder="o seu email institucional" required autocomplete="email">
                </div>
            </div>

            <div class="mb-4 text-start">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" class="form-control border-start-0" placeholder="a sua palavra-passe" required autocomplete="current-password">
                </div>
            </div>

            <button class="btn btn-login w-100">
                <i class="fas fa-sign-in-alt me-2"></i> Entrar na Plataforma
            </button>
        </form>

        <a href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/index.php" class="link-back">
            <i class="fas fa-arrow-left me-1"></i> Voltar ao Início
        </a>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>