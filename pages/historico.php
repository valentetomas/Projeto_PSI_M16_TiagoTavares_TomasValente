<?php
require_once __DIR__ . "/auth/guard.php";
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/includes/navbar.php";

$material_id = trim($_GET["material_id"] ?? "");
$tipo = trim($_GET["tipo"] ?? "");
$search = trim($_GET["search"] ?? "");
$data_inicio = trim($_GET["data_inicio"] ?? "");
$data_fim = trim($_GET["data_fim"] ?? "");

$params = [];
$sql = "
SELECT
  mov.id,
  mov.tipo,
  mov.responsavel,
  mov.user_id,
  mov.observacao,
  mov.data_movimento,
  m.id AS material_id,
  m.codigo,
  m.nome,
  u.nome AS registado_por
FROM movimentos mov
JOIN materiais m ON m.id = mov.material_id
LEFT JOIN users u ON u.id = mov.user_id
WHERE 1=1
";

if ($material_id !== "") {
  $sql .= " AND m.id = ? ";
  $params[] = $material_id;
}

if ($tipo !== "") {
  $sql .= " AND mov.tipo = ? ";
  $params[] = $tipo;
}

if ($search !== "") {
  $sql .= " AND (m.nome LIKE ? OR m.codigo LIKE ? OR mov.responsavel LIKE ? OR mov.observacao LIKE ? OR u.nome LIKE ?) ";
  $params[] = "%{$search}%";
  $params[] = "%{$search}%";
  $params[] = "%{$search}%";
  $params[] = "%{$search}%";
  $params[] = "%{$search}%";
}

if ($data_inicio !== "") {
  $sql .= " AND mov.data_movimento >= ? ";
  $params[] = $data_inicio . " 00:00:00";
}

if ($data_fim !== "") {
  $sql .= " AND mov.data_movimento <= ? ";
  $params[] = $data_fim . " 23:59:59";
}

if (($_SESSION["user_role"] ?? "") !== "admin") {
    $sql .= " AND mov.user_id = ? ";
    $params[] = (int)($_SESSION["user_id"] ?? 0);
}

$sql .= " ORDER BY mov.data_movimento DESC, mov.id DESC ";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// dropdown de materiais (para filtro)
$materiais = $pdo->query("SELECT id, codigo, nome FROM materiais ORDER BY nome, codigo")->fetchAll();
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    /* --- DARK MODE STYLES --- */
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
    /* Para calendários ficarem escuros nos navegadores */
    input[type="date"] { color-scheme: dark; }

    .input-group-text {
        background-color: rgba(15, 23, 42, 0.6);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-right: none;
        color: #94a3b8;
    }
    .input-group .form-control { border-left: none; }
    .form-select option { background-color: #1e293b; color: white; }

    .form-label-small {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #94a3b8;
        margin-bottom: 0.35rem;
        letter-spacing: 0.5px;
    }

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

    /* Badges Customizados */
    .badge-loan {
        background-color: rgba(245, 158, 11, 0.15);
        color: #fbbf24;
        border: 1px solid rgba(245, 158, 11, 0.3);
    }
    .badge-return {
        background-color: rgba(16, 185, 129, 0.15);
        color: #34d399;
        border: 1px solid rgba(16, 185, 129, 0.3);
    }
   
    .code-badge {
        font-family: monospace;
        background: rgba(0, 0, 0, 0.3);
        color: #cbd5e1;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.85rem;
        border: 1px solid rgba(255,255,255,0.05);
    }

    .btn-action-outline {
        border: 1px solid rgba(255, 255, 255, 0.15);
        background: transparent;
        color: #94a3b8;
        padding: 0.4rem 1rem;
        border-radius: 6px;
        font-size: 0.9rem;
        transition: 0.3s;
        text-decoration: none;
        display: inline-flex; align-items: center; gap: 8px;
    }
    .btn-action-outline:hover {
        border-color: #38bdf8;
        color: #fff;
        background: rgba(56, 189, 248, 0.1);
    }
   
    .btn-primary { background-color: #0ea5e9; border: none; font-weight: 600; }
    .btn-primary:hover { background-color: #0284c7; }
</style>

<div class="container mt-4 mb-5">
   
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h2 class="h3 fw-bold mb-1 text-white">Histórico Detalhado</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="#" class="text-secondary text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active text-light opacity-75">Histórico</li>
                </ol>
            </nav>
        </div>
        <?php if (($_SESSION["user_role"] ?? "") === "admin"): ?>
            <a href="historico_exportar.php?<?= http_build_query($_GET) ?>" class="btn btn-action-outline">
                <i class="fas fa-file-csv text-success"></i> Exportar Dados
            </a>
        <?php endif; ?>
    </div>

    <div class="glass-card p-4 mb-4">
        <form method="get">
            <div class="row g-3">
               
                <div class="col-md-4">
                    <label class="form-label-small">Pesquisa Geral</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" name="search"
                               placeholder="Nome, código, responsável..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
               
                <div class="col-md-5">
                    <label class="form-label-small">Filtrar por Material</label>
                    <select class="form-select" name="material_id">
                        <option value="">Todos os materiais</option>
                        <?php foreach ($materiais as $m): ?>
                            <option value="<?= (int)$m["id"] ?>" <?= ((string)$material_id === (string)$m["id"]) ? "selected" : "" ?>>
                                [<?= htmlspecialchars($m["codigo"]) ?>] <?= htmlspecialchars($m["nome"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label-small">Tipo de Movimento</label>
                    <select class="form-select" name="tipo">
                        <option value="">Todos</option>
                        <option value="emprestimo" <?= ($tipo === "emprestimo") ? "selected" : "" ?>>Empréstimo</option>
                        <option value="devolucao" <?= ($tipo === "devolucao") ? "selected" : "" ?>>Devolução</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label-small">Data Início</label>
                    <input type="date" class="form-control" name="data_inicio" value="<?= htmlspecialchars($data_inicio) ?>">
                </div>
               
                <div class="col-md-3">
                    <label class="form-label-small">Data Fim</label>
                    <input type="date" class="form-control" name="data_fim" value="<?= htmlspecialchars($data_fim) ?>">
                </div>

                <div class="col-md-6 d-flex align-items-end gap-2">
                    <button class="btn btn-primary flex-grow-1"><i class="fas fa-filter me-2"></i>Aplicar Filtros</button>
                    <a href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/historico.php" class="btn btn-action-outline border-secondary text-secondary" title="Limpar Filtros">
                        <i class="fas fa-times"></i> Limpar
                    </a>
                </div>
            </div>
        </form>
    </div>

    <div class="glass-card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Data do Registo</th>
                        <th>Ação</th>
                        <th>Material</th>
                        <th>Responsável (Aluno/Prof)</th>
                        <th>Registado Por</th>
                        <th class="pe-4">Observações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <?php
                            $is_loan = ($r["tipo"] === "emprestimo");
                            $badge_class = $is_loan ? "badge-loan" : "badge-return";
                            $icon = $is_loan ? "fa-arrow-right" : "fa-arrow-left";
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-white"><?= htmlspecialchars(date("d/m/Y", strtotime($r["data_movimento"]))) ?></div>
                                <div class="small text-secondary"><?= htmlspecialchars(date("H:i", strtotime($r["data_movimento"]))) ?></div>
                            </td>
                            <td>
                                <span class="badge rounded-pill <?= $badge_class ?> py-2 px-3 fw-normal">
                                    <i class="fas <?= $icon ?> me-1"></i>
                                    <?= $is_loan ? "Empréstimo" : "Devolução" ?>
                                </span>
                            </td>
                            <td>
                                <span class="code-badge mb-1 d-inline-block"><?= htmlspecialchars($r["codigo"]) ?></span>
                                <div class="fw-medium text-light small"><?= htmlspecialchars($r["nome"]) ?></div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle p-2 me-2 text-secondary" style="background-color: rgba(255,255,255,0.1); width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-user small"></i>
                                    </div>
                                    <span class="text-light"><?= htmlspecialchars($r["responsavel"]) ?></span>
                                </div>
                            </td>
                            <td class="text-secondary small">
                                <i class="fas fa-id-badge me-1 opacity-50"></i><?= htmlspecialchars($r["registado_por"] ?? "Sistema") ?>
                            </td>
                            <td class="pe-4 text-secondary small fst-italic">
                                <?= $r["observacao"] ? htmlspecialchars($r["observacao"]) : '<span class="opacity-25">—</span>' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (count($rows) === 0): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="text-light opacity-25 mb-3">
                                    <i class="fas fa-folder-open fa-3x"></i>
                                </div>
                                <h6 class="text-muted fw-normal">Sem registos encontrados para os filtros selecionados.</h6>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>