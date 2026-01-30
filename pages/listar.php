<?php
// pages/materiais/listar.php
require_once __DIR__ . "/auth/guard.php";
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/includes/navbar.php";
require_once __DIR__ . "/includes/mensagens.php";

$mensagem = get_mensagem();
$limite_atraso = 7;

// --- FILTROS ---
$search = trim($_GET["search"] ?? "");
$categoria = trim($_GET["categoria"] ?? "");
$estado_filtro = trim($_GET["estado"] ?? "");
$cats = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome")->fetchAll();

// --- QUERY ---
$sql = "
    SELECT
        m.id, m.codigo, m.nome, m.estado, m.emprestado_a, m.emprestado_em,
        c.nome AS categoria, l.nome AS localizacao
    FROM materiais m
    JOIN categorias c ON c.id = m.categoria_id
    JOIN localizacoes l ON l.id = m.localizacao_id
    WHERE 1=1
";
$params = [];

if ($search !== "") {
    $sql .= " AND (m.nome LIKE ? OR m.codigo LIKE ?) ";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if ($categoria !== "") {
    $sql .= " AND m.categoria_id = ? ";
    $params[] = $categoria;
}
if ($estado_filtro !== "") {
    $sql .= " AND m.estado = ? ";
    $params[] = $estado_filtro;
}

$sql .= " ORDER BY m.nome, m.codigo ";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$raw_materiais = $stmt->fetchAll();

// --- AGRUPAMENTO (PHP) ---
$grouped_materiais = [];
foreach ($raw_materiais as $item) {
    $key = $item['nome'] . '|' . $item['categoria'];
    if (!isset($grouped_materiais[$key])) {
        $grouped_materiais[$key] = [
            'nome' => $item['nome'],
            'categoria' => $item['categoria'],
            'localizacao' => $item['localizacao'],
            'total' => 0,
            'disponiveis' => 0,
            'items' => []
        ];
    }
    $grouped_materiais[$key]['items'][] = $item;
    $grouped_materiais[$key]['total']++;
    if ($item['estado'] === 'Disponível') {
        $grouped_materiais[$key]['disponiveis']++;
    }
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    /* --- DESIGN SYSTEM DARK --- */
    body {
        background-color: #0b1120; /* Fundo muito escuro */
        background-image: radial-gradient(at 0% 0%, rgba(56, 189, 248, 0.1) 0px, transparent 50%),
                          radial-gradient(at 100% 100%, rgba(16, 185, 129, 0.05) 0px, transparent 50%);
        background-attachment: fixed;
        color: #e2e8f0;
        font-family: 'Segoe UI', system-ui, sans-serif;
    }

    h2 { letter-spacing: -0.5px; }

    /* --- FILTROS (Barra de cima) --- */
    .filter-bar {
        background: rgba(30, 41, 59, 0.4);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 12px;
        backdrop-filter: blur(10px);
        padding: 1.25rem;
    }

    .form-control, .form-select {
        background-color: #1e293b !important;
        border: 1px solid #334155;
        color: #f1f5f9 !important;
        border-radius: 8px;
    }
    .form-control:focus, .form-select:focus {
        border-color: #38bdf8;
        box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
    }
    .input-group-text {
        background-color: #334155;
        border: 1px solid #334155;
        color: #94a3b8;
    }

    /* --- TABELA E CARD SYSTEM --- */
    /* Removemos a tabela tradicional e usamos divs ou tabela limpa */
    .table-container {
        margin-top: 1.5rem;
    }

    /* O Card "Pai" (Linha Principal) */
    .material-card {
        background: rgba(30, 41, 59, 0.6); /* Azul acinzentado escuro */
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 12px;
        margin-bottom: 1rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .material-card:hover {
        background: rgba(30, 41, 59, 0.9);
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
        border-color: rgba(56, 189, 248, 0.3); /* Brilho azul ao passar o rato */
    }

    /* Faixa lateral colorida baseada no stock */
    .status-indicator {
        position: absolute;
        left: 0; top: 0; bottom: 0;
        width: 4px;
    }
    .status-good { background-color: #10b981; box-shadow: 0 0 10px rgba(16, 185, 129, 0.4); }
    .status-mid  { background-color: #f59e0b; }
    .status-bad  { background-color: #ef4444; }

    /* Header do Card (Clicável) */
    .card-header-custom {
        padding: 1.25rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    /* Icone do Material */
    .icon-box {
        width: 48px; height: 48px;
        background: rgba(15, 23, 42, 0.6);
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem;
        color: #38bdf8;
        border: 1px solid rgba(255, 255, 255, 0.05);
        margin-right: 1rem;
    }

    /* Barra de Progresso */
    .progress-track {
        background: rgba(255, 255, 255, 0.1);
        height: 6px;
        width: 120px;
        border-radius: 10px;
        margin-top: 6px;
        overflow: hidden;
    }
    .progress-fill { height: 100%; border-radius: 10px; }
   
    /* Area Expandida (Detalhes) */
    .card-details {
        background: #0f172a; /* Mais escuro que o cartão */
        border-top: 1px solid rgba(255, 255, 255, 0.05);
        padding: 0;
    }

    /* Tabela Interna */
    .table-details {
        width: 100%;
        margin-bottom: 0;
        color: #cbd5e1;
    }
    .table-details th {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #64748b;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        font-weight: 600;
    }
    .table-details td {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid rgba(255,255,255,0.03);
        vertical-align: middle;
        font-size: 0.9rem;
    }
    .table-details tr:last-child td { border-bottom: none; }
   
    /* Badges e Botões */
    .badge-soft {
        padding: 0.35em 0.8em;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .badge-soft.available { background: rgba(16, 185, 129, 0.15); color: #34d399; }
    .badge-soft.busy { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }
   
    .btn-action {
        width: 34px; height: 34px;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 8px;
        background: rgba(255,255,255,0.05);
        color: #94a3b8;
        border: 1px solid transparent;
        transition: all 0.2s;
        text-decoration: none;
    }
    .btn-action:hover { background: rgba(56, 189, 248, 0.15); color: #38bdf8; border-color: rgba(56, 189, 248, 0.3); transform: scale(1.05); }
    .btn-action.delete:hover { background: rgba(239, 68, 68, 0.15); color: #f87171; border-color: rgba(239, 68, 68, 0.3); }

    /* Seta de Rotação */
    .chevron { transition: transform 0.3s; color: #64748b; }
    .collapsed .chevron { transform: rotate(0deg); }
    .material-card:not(.collapsed) .chevron { transform: rotate(180deg); }

    /* Botão Novo */
    .btn-glow {
        background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);
        border: none;
        color: white;
        box-shadow: 0 4px 15px rgba(14, 165, 233, 0.4);
        font-weight: 600;
    }
    .btn-glow:hover { box-shadow: 0 6px 20px rgba(14, 165, 233, 0.6); color: white; transform: translateY(-1px); }

</style>

<div class="container mt-4 mb-5">
   
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0 text-white">Inventário</h2>
            <p class="text-secondary small mt-1 mb-0">Gestão centralizada de equipamentos</p>
        </div>
        <?php if (($_SESSION["user_role"] ?? "") === "admin"): ?>
            <a class="btn btn-glow px-4 py-2 rounded-3 d-flex align-items-center gap-2" href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/criar.php">
                <i class="fa-solid fa-plus"></i> <span class="d-none d-md-inline">Novo Material</span>
            </a>
        <?php endif; ?>
    </div>

    <div class="filter-bar mb-4">
        <form class="row g-3 align-items-end" method="get">
            <div class="col-md-5">
                <label class="text-muted small fw-bold mb-1 ms-1">PESQUISA</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input class="form-control" name="search" placeholder="Ex: Portátil, PT-001..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <label class="text-muted small fw-bold mb-1 ms-1">CATEGORIA</label>
                <select class="form-select" name="categoria">
                    <option value="">Todas</option>
                    <?php foreach ($cats as $c): ?>
                        <option value="<?= (int)$c["id"] ?>" <?= ((string)$categoria === (string)$c["id"]) ? "selected" : "" ?>>
                            <?= htmlspecialchars($c["nome"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="text-muted small fw-bold mb-1 ms-1">ESTADO</label>
                <select class="form-select" name="estado">
                    <option value="">Todos</option>
                    <option value="Disponível" <?= $estado_filtro === "Disponível" ? "selected" : "" ?>>Disponível</option>
                    <option value="Emprestado" <?= $estado_filtro === "Emprestado" ? "selected" : "" ?>>Emprestado</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-secondary w-100 fw-bold py-2" style="background: #475569; border: none;">Filtrar</button>
            </div>
        </form>
    </div>

    <?php if ($mensagem): ?>
        <div class="alert alert-<?= htmlspecialchars($mensagem["tipo"]) ?> alert-dismissible fade show shadow-sm mb-4" role="alert" style="background: rgba(30,41,59,0.9); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
            <?= htmlspecialchars($mensagem["texto"]) ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="list-container">
        <?php if (empty($grouped_materiais)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fa-solid fa-box-open fa-3x mb-3 opacity-25"></i>
                <p>Nenhum material encontrado com estes filtros.</p>
            </div>
        <?php else: ?>
            <?php
            $i = 0;
            foreach ($grouped_materiais as $key => $group):
                $i++;
                $percent = ($group['total'] > 0) ? ($group['disponiveis'] / $group['total']) * 100 : 0;
               
                // Cores baseadas no stock
                $statusClass = 'status-mid';
                $fillColor = '#f59e0b';
                if ($percent > 50) { $statusClass = 'status-good'; $fillColor = '#10b981'; }
                if ($percent == 0) { $statusClass = 'status-bad'; $fillColor = '#ef4444'; }
            ?>
               
                <div class="material-card collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?= $i ?>" aria-expanded="false">
                    <div class="status-indicator <?= $statusClass ?>"></div>
                   
                    <div class="card-header-custom">
                        <div class="d-flex align-items-center flex-grow-1">
                            <div class="icon-box">
                                <i class="fa-solid fa-microchip"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-white mb-0"><?= htmlspecialchars($group['nome']) ?></h5>
                                <div class="text-muted small mt-1">
                                    <span class="me-3"><i class="fa-solid fa-layer-group me-1"></i><?= htmlspecialchars($group['categoria']) ?></span>
                                    <span><i class="fa-solid fa-location-dot me-1"></i><?= htmlspecialchars($group['localizacao']) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="d-none d-md-block mx-4 text-end">
                            <div class="d-flex justify-content-end align-items-center gap-2 mb-1">
                                <span class="small text-muted fw-bold">DISPONIBILIDADE</span>
                                <span class="badge bg-dark border border-secondary text-white">
                                    <?= $group['disponiveis'] ?> / <?= $group['total'] ?>
                                </span>
                            </div>
                            <div class="progress-track ms-auto">
                                <div class="progress-fill" style="width: <?= $percent ?>%; background-color: <?= $fillColor ?>;"></div>
                            </div>
                        </div>

                        <div class="ps-3">
                            <i class="fa-solid fa-chevron-down chevron"></i>
                        </div>
                    </div>

                    <div id="collapse-<?= $i ?>" class="accordion-collapse collapse card-details">
                        <div class="table-responsive">
                            <table class="table-details">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Estado</th>
                                        <th>Info Empréstimo</th>
                                        <th class="text-end">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($group['items'] as $m): ?>
                                        <tr>
                                            <td width="15%">
                                                <span class="font-monospace text-info bg-dark px-2 py-1 rounded border border-secondary border-opacity-25">
                                                    <?= htmlspecialchars($m['codigo']) ?>
                                                </span>
                                            </td>
                                            <td width="20%">
                                                <?php if ($m['estado'] === 'Disponível'): ?>
                                                    <span class="badge-soft available"><i class="fa-solid fa-check me-1"></i>Disponível</span>
                                                <?php elseif ($m['estado'] === 'Emprestado'): ?>
                                                    <span class="badge-soft busy"><i class="fa-solid fa-user-clock me-1"></i>Emprestado</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25"><?= htmlspecialchars($m['estado']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($m['estado'] === 'Emprestado'):
                                                    $dias = !empty($m["emprestado_em"]) ? (int)floor((time() - strtotime($m["emprestado_em"])) / 86400) : 0;
                                                    $isLate = ($dias >= $limite_atraso);
                                                ?>
                                                    <div class="d-flex align-items-center text-light">
                                                        <div class="bg-dark rounded-circle p-2 me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                            <i class="fa-regular fa-user text-muted"></i>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold small"><?= htmlspecialchars($m['emprestado_a']) ?></div>
                                                            <div class="extra-small text-muted" style="font-size: 0.75rem;">
                                                                <?php if ($isLate): ?>
                                                                    <span class="text-danger fw-bold"><i class="fa-solid fa-triangle-exclamation"></i> Atrasado há <?= $dias ?> dias</span>
                                                                <?php else: ?>
                                                                    Emprestado há <?= $dias ?> dias
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted opacity-25 small">---</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <div class="d-flex justify-content-end gap-2">
                                                    <?php if ($m['estado'] === 'Disponível'): ?>
                                                        <a href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/emprestar.php?material_id=<?= $m['id'] ?>" class="btn-action" title="Emprestar">
                                                            <i class="fa-solid fa-hand-holding"></i>
                                                        </a>
                                                    <?php elseif ($m['estado'] === 'Emprestado'): ?>
                                                        <a href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/devolver.php?material_id=<?= $m['id'] ?>" class="btn-action" title="Devolver" onclick="return confirm('Confirmar devolução?');">
                                                            <i class="fa-solid fa-rotate-left"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                   
                                                    <a href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/historico.php?material_id=<?= $m['id'] ?>" class="btn-action" title="Histórico">
                                                        <i class="fa-solid fa-clock-rotate-left"></i>
                                                    </a>

                                                    <?php if (($_SESSION["user_role"] ?? "") === "admin"): ?>
                                                        <div class="vr bg-secondary opacity-25 mx-1"></div>
                                                        <a href="/Projeto_PSI_M16_TiagoTavares_TomasValente/pages/editar.php?id=<?= $m['id'] ?>" class="btn-action" title="Editar">
                                                            <i class="fa-solid fa-pen"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>