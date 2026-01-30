<?php
require_once __DIR__ . "/auth/admin_guard.php";
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/includes/navbar.php";

// Regra de negócio: Atraso definido (dias)
$limite_atraso = 7;

// --- Lógica de Filtros e Ordenação (Mantida) ---
$search = trim($_GET["search"] ?? "");
$categoria = trim($_GET["categoria"] ?? "");
$localizacao = trim($_GET["localizacao"] ?? "");
$so_atrasados = (int)($_GET["so_atrasados"] ?? 0);

$sort = $_GET["sort"] ?? "desde";
$dir = strtolower($_GET["dir"] ?? "desc");
$dir = ($dir === "asc") ? "asc" : "desc";

$cats = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome")->fetchAll();
$locs = $pdo->query("SELECT id, nome FROM localizacoes ORDER BY nome")->fetchAll();

$sql = "
    SELECT
      m.id, m.codigo, m.nome, m.estado, m.emprestado_a, m.emprestado_em,
      c.nome AS categoria, l.nome AS localizacao
    FROM materiais m
    JOIN categorias c ON c.id = m.categoria_id
    JOIN localizacoes l ON l.id = m.localizacao_id
    WHERE m.estado = 'Emprestado'
";

$params = [];

if ($search !== "") {
    $sql .= " AND (m.nome LIKE ? OR m.codigo LIKE ? OR m.emprestado_a LIKE ?) ";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if ($categoria !== "") {
    $sql .= " AND m.categoria_id = ? ";
    $params[] = $categoria;
}
if ($localizacao !== "") {
    $sql .= " AND m.localizacao_id = ? ";
    $params[] = $localizacao;
}

$sort_map = [
    "desde" => "m.emprestado_em",
    "nome" => "m.nome",
    "codigo" => "m.codigo",
    "responsavel" => "m.emprestado_a"
];
$order_by = $sort_map[$sort] ?? "m.emprestado_em";
$sql .= " ORDER BY {$order_by} {$dir}, m.nome, m.codigo ";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Filtragem PHP para atrasados
if ($so_atrasados) {
    $rows = array_values(array_filter($rows, function($r) use ($limite_atraso) {
        if (empty($r["emprestado_em"])) return false;
        $dias = (int)floor((time() - strtotime($r["emprestado_em"])) / 86400);
        return $dias >= $limite_atraso;
    }));
}

// Cálculos Estatísticos
$total = count($rows);
$atrasados = 0;
foreach ($rows as $r) {
    if (!empty($r["emprestado_em"])) {
        $diasTmp = (int)floor((time() - strtotime($r["emprestado_em"])) / 86400);
        if ($diasTmp >= $limite_atraso) $atrasados++;
    }
}

// Helpers Visuais
function build_qs($overrides = []) {
    $q = array_merge($_GET, $overrides);
    foreach ($q as $k => $v) if ($v === "" || $v === null) unset($q[$k]);
    return http_build_query($q);
}

function sort_icon($col_name, $current_sort, $current_dir) {
    if ($col_name !== $current_sort) return '<i class="fas fa-sort text-muted opacity-25 ms-1"></i>';
    return $current_dir === 'asc' ? '<i class="fas fa-sort-up text-info ms-1"></i>' : '<i class="fas fa-sort-down text-info ms-1"></i>';
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    /* --- DARK MODE STYLES (Consistente) --- */
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

    /* Stats Cards Specific */
    .stat-card {
        height: 100%;
        display: flex;
        align-items: center;
        padding: 1.5rem;
        transition: transform 0.2s;
    }
    .stat-icon {
        width: 50px; height: 50px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem;
        margin-right: 1rem;
    }

    /* Títulos e Breadcrumbs */
    h2 { color: #fff; letter-spacing: -0.5px; }
   
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

    /* Checkbox Custom */
    .form-check-input {
        background-color: rgba(15, 23, 42, 0.6);
        border-color: rgba(255, 255, 255, 0.3);
    }
    .form-check-input:checked {
        background-color: #ef4444;
        border-color: #ef4444;
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
   
    /* Linha Atrasada - Efeito visual suave no dark mode */
    .row-overdue td {
        background-color: rgba(239, 68, 68, 0.08) !important;
    }
    .table-hover tbody tr:hover td { background-color: rgba(255, 255, 255, 0.03); }
    .table-hover tbody tr.row-overdue:hover td { background-color: rgba(239, 68, 68, 0.12) !important; }

    /* Links de Ordenação */
    .table thead th a { color: #94a3b8; }
    .table thead th a:hover { color: #38bdf8; }

    /* Botões */
    .btn-primary { background-color: #0ea5e9; border: none; font-weight: 600; }
    .btn-primary:hover { background-color: #0284c7; }
   
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

    .btn-return {
        border: 1px solid rgba(16, 185, 129, 0.3);
        background: rgba(16, 185, 129, 0.1);
        color: #34d399;
        font-weight: 500;
        transition: all 0.2s;
    }
    .btn-return:hover {
        background: #10b981;
        color: #fff;
        border-color: #10b981;
    }

    /* Badges e Avatares */
    .badge-time { font-size: 0.75rem; padding: 0.35em 0.65em; border-radius: 6px; }
    .code-badge {
        font-family: monospace;
        background: rgba(0, 0, 0, 0.3);
        color: #cbd5e1;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.85rem;
        border: 1px solid rgba(255,255,255,0.05);
    }
    .avatar-placeholder {
        width: 30px; height: 30px;
        background-color: rgba(255,255,255,0.1);
        color: #94a3b8;
        border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 0.8rem;
        margin-right: 10px;
    }

</style>

<div class="container mt-4 mb-5">

    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h2 class="h3 fw-bold mb-1">Gestão de Empréstimos Ativos</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0" style="font-size: 0.9rem;">
                    <li class="breadcrumb-item"><a href="#" class="text-secondary text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active text-light opacity-75">Empréstimos Ativos</li>
                </ol>
            </nav>
        </div>
        <a href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/emprestimos_ativos_exportar.php?<?= htmlspecialchars(build_qs()) ?>"
           class="btn btn-action-outline text-light border-light-subtle">
            <i class="fas fa-file-csv text-success"></i> Exportar Lista
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-lg-4">
            <div class="glass-card stat-card">
                <div class="stat-icon" style="background-color: rgba(56, 189, 248, 0.15); color: #38bdf8;">
                    <i class="fas fa-boxes"></i>
                </div>
                <div>
                    <h6 class="text-secondary text-uppercase small fw-bold mb-1">Total Emprestado</h6>
                    <h3 class="mb-0 fw-bold text-white"><?= $total ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <?php $has_delay = $atrasados > 0; ?>
            <div class="glass-card stat-card" style="<?= $has_delay ? 'border-color: rgba(239, 68, 68, 0.4); box-shadow: 0 4px 12px rgba(239, 68, 68, 0.1);' : '' ?>">
                <div class="stat-icon" style="background-color: <?= $has_delay ? 'rgba(239, 68, 68, 0.15)' : 'rgba(16, 185, 129, 0.15)' ?>; color: <?= $has_delay ? '#f87171' : '#34d399' ?>;">
                    <i class="fas <?= $has_delay ? 'fa-exclamation-triangle' : 'fa-check-circle' ?>"></i>
                </div>
                <div>
                    <h6 class="text-secondary text-uppercase small fw-bold mb-1">Em Atraso (> <?= $limite_atraso ?> dias)</h6>
                    <h3 class="mb-0 fw-bold <?= $has_delay ? 'text-danger' : 'text-success' ?>"><?= $atrasados ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="glass-card p-4 mb-4">
        <form class="row g-3 align-items-center" method="get">
            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
            <input type="hidden" name="dir" value="<?= htmlspecialchars($dir) ?>">

            <div class="col-md-3">
                <label class="form-label small text-secondary fw-bold text-uppercase">Pesquisa</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input class="form-control" name="search" placeholder="Material ou pessoa..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
           
            <div class="col-md-2">
                <label class="form-label small text-secondary fw-bold text-uppercase">Categoria</label>
                <select class="form-select" name="categoria">
                    <option value="">Todas</option>
                    <?php foreach ($cats as $c): ?>
                        <option value="<?= $c["id"] ?>" <?= ((string)$categoria === (string)$c["id"]) ? "selected" : "" ?>><?= htmlspecialchars($c["nome"]) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small text-secondary fw-bold text-uppercase">Localização</label>
                <select class="form-select" name="localizacao">
                    <option value="">Todas</option>
                    <?php foreach ($locs as $l): ?>
                        <option value="<?= $l["id"] ?>" <?= ((string)$localizacao === (string)$l["id"]) ? "selected" : "" ?>><?= htmlspecialchars($l["nome"]) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label d-block small text-secondary fw-bold text-uppercase">&nbsp;</label>
                <div class="form-check pt-2">
                    <input class="form-check-input" type="checkbox" value="1" id="so_atrasados" name="so_atrasados" <?= $so_atrasados ? "checked" : "" ?>>
                    <label class="form-check-label small fw-bold text-danger" for="so_atrasados">Ver Atrasos</label>
                </div>
            </div>

            <div class="col-md-3 text-end d-flex align-items-end justify-content-end gap-2">
                <button class="btn btn-primary px-3"><i class="fas fa-filter me-1"></i> Aplicar</button>
                <a href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/emprestimos_ativos.php" class="btn btn-action-outline">Limpar</a>
            </div>
        </form>
    </div>

    <div class="glass-card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4 py-3"><a href="?<?= build_qs(["sort" => "codigo", "dir" => ($sort === "codigo" && $dir === "asc" ? "desc" : "asc")]) ?>" class="text-decoration-none">Código <?= sort_icon("codigo", $sort, $dir) ?></a></th>
                        <th><a href="?<?= build_qs(["sort" => "nome", "dir" => ($sort === "nome" && $dir === "asc" ? "desc" : "asc")]) ?>" class="text-decoration-none">Material <?= sort_icon("nome", $sort, $dir) ?></a></th>
                        <th>Responsável</th>
                        <th><a href="?<?= build_qs(["sort" => "desde", "dir" => ($sort === "desde" && $dir === "asc" ? "desc" : "asc")]) ?>" class="text-decoration-none">Data Saída <?= sort_icon("desde", $sort, $dir) ?></a></th>
                        <th>Status / Tempo</th>
                        <th class="text-end pe-4">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <?php
                            $dias = 0;
                            if (!empty($r["emprestado_em"])) {
                                $dias = (int)floor((time() - strtotime($r["emprestado_em"])) / 86400);
                                if ($dias < 0) $dias = 0;
                            }
                            $is_late = ($dias >= $limite_atraso);
                            $row_class = $is_late ? "row-overdue" : "";
                        ?>
                        <tr class="<?= $row_class ?>">
                            <td class="ps-4">
                                <span class="code-badge"><?= htmlspecialchars($r["codigo"]) ?></span>
                            </td>
                            <td>
                                <div class="fw-medium text-white"><?= htmlspecialchars($r["nome"]) ?></div>
                                <div class="text-secondary small" style="font-size: 0.75rem;">
                                    <?= htmlspecialchars($r["categoria"]) ?> &bull; <?= htmlspecialchars($r["localizacao"]) ?>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-placeholder">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <span class="fw-medium text-light small"><?= htmlspecialchars($r["emprestado_a"] ?? "—") ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="small text-light">
                                    <i class="far fa-calendar-alt me-1 text-secondary"></i>
                                    <?= !empty($r["emprestado_em"]) ? date("d/m/Y", strtotime($r["emprestado_em"])) : "-" ?>
                                </div>
                                <div class="small text-secondary ps-4" style="font-size: 0.7rem;">
                                    <?= !empty($r["emprestado_em"]) ? date("H:i", strtotime($r["emprestado_em"])) : "" ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($is_late): ?>
                                    <span class="badge bg-danger bg-opacity-25 text-danger border border-danger badge-time mb-1">
                                        <i class="fas fa-exclamation-circle me-1"></i> Atrasado
                                    </span>
                                    <div class="text-danger small fw-bold ps-1"><?= $dias ?> dias</div>
                                <?php else: ?>
                                    <span class="badge bg-success bg-opacity-25 text-success border border-success badge-time mb-1">
                                        Dentro do prazo
                                    </span>
                                    <div class="text-success small ps-1"><?= $dias ?> dias</div>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group">
                                    <a class="btn btn-return btn-sm py-1 px-3 shadow-sm rounded-start"
                                       title="Registar Devolução"
                                       onclick="return confirm('Confirmar a devolução de: <?= htmlspecialchars($r['nome']) ?>?')"
                                       href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/devolver.php?material_id=<?= (int)$r["id"] ?>">
                                        <i class="fas fa-check me-1"></i> Devolver
                                    </a>
                                    <a class="btn btn-action-outline btn-sm rounded-0 rounded-end border-start-0"
                                       title="Ver Histórico"
                                       href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/historico.php?material_id=<?= (int)$r["id"] ?>">
                                        <i class="fas fa-history"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (count($rows) === 0): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="mb-3 opacity-25">
                                    <i class="fas fa-clipboard-check fa-4x text-light"></i>
                                </div>
                                <h5 class="text-muted fw-normal">Nenhum empréstimo ativo encontrado.</h5>
                                <p class="small text-secondary">Tente alterar os filtros de pesquisa.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>