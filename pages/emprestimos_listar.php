<?php
require_once __DIR__ . "/auth/admin_guard.php";
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/includes/navbar.php";
require_once __DIR__ . "/includes/mensagens.php";

$mensagem = get_mensagem();

$search = trim($_GET["search"] ?? "");
$estado = trim($_GET["estado"] ?? ""); // emprestimo | devolucao

$sql = "
SELECT
  mov.id,
  mov.material_id,
  mov.tipo,
  mov.responsavel,
  mov.data_movimento,
  m.codigo,
  m.nome
FROM movimentos mov
JOIN materiais m ON m.id = mov.material_id
WHERE 1=1
";

$params = [];

if ($search !== "") {
  $sql .= " AND (m.nome LIKE ? OR m.codigo LIKE ? OR mov.responsavel LIKE ?) ";
  $params[] = "%{$search}%";
  $params[] = "%{$search}%";
  $params[] = "%{$search}%";
}

if ($estado !== "") {
  $sql .= " AND mov.tipo = ? ";
  $params[] = $estado;
}

$sql .= " ORDER BY mov.data_movimento DESC, mov.id DESC ";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$movimentos = $stmt->fetchAll();
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    /* --- DARK MODE STYLES (Consistente com Listar) --- */
    body {
        background-color: #0f172a;
        color: #e2e8f0;
    }

    /* Glass Cards */
    .glass-card {
        background: rgba(30, 41, 59, 0.7);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
    }

    /* Títulos e Breadcrumbs */
    h2 { color: #fff; letter-spacing: -0.5px; }
    .breadcrumb-item a { color: #94a3b8; text-decoration: none; }
    .breadcrumb-item a:hover { color: #38bdf8; }
    .breadcrumb-item.active { color: #cbd5e1; }
    .breadcrumb-item + .breadcrumb-item::before { color: #64748b; }

    /* Inputs e Selects */
    .form-control, .form-select {
        background-color: rgba(15, 23, 42, 0.6);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #fff;
    }
    .form-control:focus, .form-select:focus {
        background-color: rgba(15, 23, 42, 0.8);
        border-color: #38bdf8;
        color: #fff;
        box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.2);
    }
    .input-group-text {
        background-color: rgba(15, 23, 42, 0.6);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-right: none;
        color: #94a3b8;
    }
    .input-group .form-control { border-left: none; }
    .form-select option { background-color: #1e293b; color: white; }

    /* Text Labels */
    .text-label { color: #cbd5e1; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; margin-bottom: 5px; display: block; }

    /* Tabela */
    .table { color: #e2e8f0; margin-bottom: 0; }
    .table thead th {
        background-color: rgba(15, 23, 42, 0.8);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        color: #94a3b8;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 1px;
        padding: 1rem;
    }
    .table tbody td {
        background-color: transparent;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        padding: 1rem;
        vertical-align: middle;
    }
    .table-hover tbody tr:hover td { background-color: rgba(255, 255, 255, 0.03); }

    /* Botões */
    .btn-primary { background-color: #0ea5e9; border: none; font-weight: 600; }
    .btn-primary:hover { background-color: #0284c7; }
    .btn-dark-custom { background-color: #334155; color: white; border: none; font-weight: 600; }
    .btn-dark-custom:hover { background-color: #475569; }
   
    .btn-action-outline {
        border: 1px solid rgba(255, 255, 255, 0.15);
        background: transparent;
        color: #94a3b8;
        padding: 0.3rem 0.8rem;
        border-radius: 6px;
        font-size: 0.85rem;
        transition: 0.3s;
        text-decoration: none;
        display: inline-flex; align-items: center; gap: 6px;
    }
    .btn-action-outline:hover {
        border-color: #38bdf8;
        color: #fff;
        background: rgba(56, 189, 248, 0.1);
    }

    /* --- ESTILOS ESPECÍFICOS DESTA PÁGINA --- */
   
    /* Indicadores Laterais (Borda colorida à esquerda) */
    .border-left-loan td:first-child { box-shadow: inset 4px 0 0 0 #f59e0b; } /* Laranja */
    .border-left-return td:first-child { box-shadow: inset 4px 0 0 0 #10b981; } /* Verde */

    /* Avatar do utilizador */
    .avatar-placeholder {
        width: 28px; height: 28px;
        background-color: rgba(255,255,255,0.1);
        color: #94a3b8;
        border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 0.75rem;
        margin-right: 10px;
    }

    /* Badges de Status */
    .badge-mov { font-weight: 600; padding: 0.5em 0.8em; border-radius: 6px; font-size: 0.75rem; letter-spacing: 0.5px; }
    .bg-badge-loan { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
    .bg-badge-return { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }

    /* Código do Material */
    .code-badge {
        font-family: monospace;
        background: rgba(0, 0, 0, 0.3);
        color: #cbd5e1;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.85rem;
        border: 1px solid rgba(255,255,255,0.05);
    }

</style>

<div class="container mt-4 mb-5">
   
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h2 class="h3 fw-bold mb-1">Registo de Movimentos</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0" style="font-size: 0.9rem;">
                    <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
                    <li class="breadcrumb-item active">Histórico Geral</li>
                </ol>
            </nav>
        </div>
        <a class="btn btn-primary d-flex align-items-center gap-2 px-4 py-2 rounded-3 shadow-lg" href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/emprestar.php">
            <i class="fa-solid fa-hand-holding-box"></i> Novo Empréstimo
        </a>
    </div>

    <?php if ($mensagem): ?>
        <div class="alert alert-<?= htmlspecialchars($mensagem["tipo"]) ?> alert-dismissible fade show" role="alert"
             style="background: rgba(30, 41, 59, 0.9); border-color: rgba(255,255,255,0.1); color: #fff;">
            <i class="fas fa-info-circle me-2"></i><?= htmlspecialchars($mensagem["texto"]) ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="glass-card p-4 mb-4">
        <form class="row g-3" method="get">
            <div class="col-md-6">
                <label class="text-label">Pesquisa Rápida</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input class="form-control" name="search"
                           placeholder="Nome do material, código ou responsável..."
                           value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>

            <div class="col-md-4">
                <label class="text-label">Tipo de Movimento</label>
                <select class="form-select" name="estado">
                    <option value="">Todos os registos</option>
                    <option value="emprestimo" <?= ($estado === "emprestimo") ? "selected" : "" ?>>Saída (Empréstimos)</option>
                    <option value="devolucao" <?= ($estado === "devolucao") ? "selected" : "" ?>>Entrada (Devoluções)</option>
                </select>
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-dark-custom w-100 py-2 rounded-3">
                    <i class="fas fa-filter me-2"></i>Filtrar
                </button>
            </div>
        </form>
    </div>

    <div class="glass-card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Data / Hora</th>
                        <th>Tipo</th>
                        <th>Material</th>
                        <th>Responsável</th>
                        <th class="text-end pe-4">Detalhes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movimentos as $mv): ?>
                        <?php
                            $is_emprestimo = ($mv["tipo"] === "emprestimo");
                            // Definição de estilos baseados no tipo
                            $badge_class = $is_emprestimo ? "bg-badge-loan" : "bg-badge-return";
                            $icon = $is_emprestimo ? "fa-arrow-right-from-bracket" : "fa-arrow-right-to-bracket";
                            $label = $is_emprestimo ? "Empréstimo" : "Devolução";
                            $row_border = $is_emprestimo ? "border-left-loan" : "border-left-return";
                        ?>
                        <tr class="<?= $row_border ?>">
                            <td class="ps-4">
                                <div class="fw-bold text-white">
                                    <?= htmlspecialchars(date("d/m/Y", strtotime($mv["data_movimento"]))) ?>
                                </div>
                                <div class="text-muted small" style="font-size: 0.8rem;">
                                    <i class="far fa-clock me-1"></i><?= htmlspecialchars(date("H:i", strtotime($mv["data_movimento"]))) ?>
                                </div>
                            </td>

                            <td>
                                <span class="badge badge-mov <?= $badge_class ?>">
                                    <i class="fas <?= $icon ?> me-1"></i> <?= $label ?>
                                </span>
                            </td>

                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="code-badge">
                                        <?= htmlspecialchars($mv["codigo"]) ?>
                                    </span>
                                </div>
                                <div class="small mt-1 text-light fw-medium">
                                    <?= htmlspecialchars($mv["nome"]) ?>
                                </div>
                            </td>

                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-placeholder">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <span class="fw-medium text-light">
                                        <?= htmlspecialchars($mv["responsavel"]) ?>
                                    </span>
                                </div>
                            </td>

                            <td class="text-end pe-4">
                                <a class="btn-action-outline"
                                   title="Ver Histórico Completo do Material"
                                   href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/historico.php?material_id=<?= (int)$mv["material_id"] ?>">
                                    <i class="fas fa-history"></i> Histórico
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (count($movimentos) === 0): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <div class="opacity-50 mb-3">
                                    <i class="fas fa-exchange-alt fa-3x"></i>
                                </div>
                                <h5 class="text-muted fw-normal">Nenhum movimento encontrado</h5>
                                <p class="text-muted small">Ajuste os filtros para ver mais resultados.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>