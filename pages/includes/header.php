<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!doctype html>
<html lang="pt">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>CTE Inventário | AEAAV</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
 
    <style>

    :root {

        --brand-dark: #0b1328;

        --brand-surface: rgba(15, 23, 42, 0.75);

        --brand-border: rgba(148, 163, 184, 0.18);

        --brand-blue: #38bdf8;

        --brand-teal: #22d3ee;

        --brand-green: #34d399;

        --brand-violet: #818cf8;

        --brand-gradient: linear-gradient(135deg, #38bdf8 0%, #22d3ee 50%, #34d399 100%);

        --brand-glow: 0 12px 35px rgba(56, 189, 248, 0.25);

        --text-strong: #f8fafc;

        --text-muted: #9aa6bf;

    }
 
    body {

        font-family: 'Inter', sans-serif;

        background-color: #0d1a2f;

        color: var(--text-strong);

        min-height: 100vh;

        position: relative;

        overflow-x: hidden;

    }

    .particles-bg {

        position: fixed;

        inset: 0;

        z-index: 1;

        background-color: #0d1a2f;

        pointer-events: none;

    }

    .particles-bg canvas {

        width: 100% !important;

        height: 100% !important;

        display: block;

    }

    .page-wrapper {

        position: relative;

        z-index: 2;

        min-height: 100vh;

    }

    a { color: #7dd3fc; }

    a:hover { color: #5eead4; }

    .text-muted { color: var(--text-muted) !important; }

    .text-dark { color: var(--text-strong) !important; }

    .bg-white { background-color: var(--brand-surface) !important; }

    .bg-light { background-color: rgba(30, 41, 59, 0.55) !important; }

    .border, .border-top, .border-bottom, .border-start, .border-end {

        border-color: var(--brand-border) !important;

    }

    .card {

        background: var(--brand-surface);

        border: 1px solid var(--brand-border);

        box-shadow: var(--brand-glow);

        color: var(--text-strong);

        backdrop-filter: blur(14px);

    }

    .form-control,
    .form-select,
    .input-group-text {

        background-color: rgba(15, 23, 42, 0.55) !important;

        border-color: var(--brand-border) !important;

        color: var(--text-strong) !important;

    }

    .form-control::placeholder { color: rgba(148, 163, 184, 0.8); }

    .form-control:focus,
    .form-select:focus {

        box-shadow: 0 0 0 0.2rem rgba(56, 189, 248, 0.25) !important;

        border-color: rgba(56, 189, 248, 0.7) !important;

    }

    .table { color: var(--text-strong); }

    .table thead th {

        background: rgba(30, 41, 59, 0.8) !important;

        color: var(--text-strong) !important;

        border-color: var(--brand-border) !important;

    }

    .table-hover tbody tr:hover {

        background-color: rgba(56, 189, 248, 0.08) !important;

    }

    .badge { border-radius: 999px; font-weight: 600; }

    .badge.bg-success { background-color: #16a34a !important; color: #ecfdf5 !important; }

    .badge.bg-warning { background-color: #f59e0b !important; color: #0b1224 !important; }

    .badge.bg-danger { background-color: #ef4444 !important; color: #0b1224 !important; }

    .btn-primary,
    .btn-gradient {

        background: var(--brand-gradient);

        border: none;

        color: #071523;

        font-weight: 700;

        box-shadow: var(--brand-glow);

    }

    .btn-primary:hover,
    .btn-gradient:hover {

        transform: translateY(-2px);

        filter: brightness(1.05);

        box-shadow: 0 16px 40px rgba(34, 211, 238, 0.25);

    }

    .btn-outline-light {

        border-color: rgba(125, 211, 252, 0.4);

        color: #e2f2ff;

    }

    .btn-outline-light:hover {

        background: rgba(56, 189, 248, 0.15);

        color: #fff;

    }
 
    /* --- Navbar Customizada --- */

    .navbar-custom {

        background: linear-gradient(180deg, rgba(15, 23, 42, 0.98), rgba(15, 23, 42, 0.9));

        border-bottom: 1px solid rgba(255,255,255,0.1);

        padding: 10px 0; /* Mais espaço vertical */

        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.6);

        min-height: 80px; /* Garante altura suficiente para o logo grande */

    }
 
    /* --- CONFIGURAÇÃO DO LOGO --- */

    /* Contentor do logo (opcional, se quiseres manter o fundo branco atrás dele) */

    .logo-container {

        /* Se o logo já for branco/claro e não precisares do fundo branco,

           podes apagar estas 3 linhas abaixo (background, padding, border-radius) */

        background-color: rgba(248, 250, 252, 0.1); 

        padding: 5px 10px;       

        border-radius: 8px;      

        border: 1px solid rgba(148, 163, 184, 0.2);

        display: flex;

        align-items: center;

        margin-right: 15px;

    }
 
    /* O TAMANHO DA IMAGEM É DEFINIDO AQUI */

    .navbar-brand img {

        height: 65px;  /* AUMENTEI ISTO (estava 35px). Ajusta este valor se quiseres maior/menor */

        width: auto;   /* Mantém a proporção correta */

        display: block;

        object-fit: contain; /* Garante que a imagem não fica distorcida */

    }

    /* Texto ao lado do logo */

    .brand-text {

        font-size: 1rem; /* Texto ligeiramente maior */

        line-height: 1.2;

        color: white;

    }
 
    /* --- Restante CSS (Links, Botões, etc) --- */

    .nav-link {

        color: #cbd5e1 !important;

        font-weight: 500;

        margin: 0 5px;

        transition: all 0.3s ease;

        position: relative;

    }
 
    .nav-link:hover, .nav-link.active {

        color: #fff !important;

    }
 
    .nav-link::after {

        content: '';

        position: absolute;

        width: 0;

        height: 2px;

        bottom: 0;

        left: 50%;

        background: linear-gradient(90deg, #38bdf8, #818cf8);

        transition: all 0.3s ease;

        transform: translateX(-50%);

    }
 
    .nav-link:hover::after {

        width: 100%;

    }
 
    .btn-gradient {

        background: var(--brand-gradient);

        border: none;

        color: white;

        font-weight: 600;

    }

    .user-pill {

        background: rgba(255, 255, 255, 0.05);

        border-radius: 50px;

        padding: 6px 16px;

        display: flex;

        align-items: center;

        border: 1px solid rgba(255,255,255,0.1);

    }

    .user-role-badge {

        font-size: 0.65rem;

        text-transform: uppercase;

        background: var(--brand-gradient);

        padding: 2px 6px;

        border-radius: 4px;

        color: white;

        font-weight: 700;

        margin-left: 8px;

    }

    .dropdown-menu-dark {

        background-color: #1e293b;

        border: 1px solid rgba(255,255,255,0.1);

    }
</style>
 
</head>
<body>
    <div id="particles-js" class="particles-bg"></div>
    <div class="page-wrapper">