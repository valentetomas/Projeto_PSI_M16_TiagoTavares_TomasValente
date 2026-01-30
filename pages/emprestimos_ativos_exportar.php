<?php
require_once __DIR__ . "/auth/guard.php";
require_once __DIR__ . "/includes/db.php";
 
// regra simples de atraso (dias)
$limite_atraso = 7;
 
// filtros
$search = trim($_GET["search"] ?? "");
$categoria = trim($_GET["categoria"] ?? "");
$localizacao = trim($_GET["localizacao"] ?? "");
$so_atrasados = (int)($_GET["so_atrasados"] ?? 0);
 
// ordenação
$sort = $_GET["sort"] ?? "desde";
$dir = strtolower($_GET["dir"] ?? "desc");
$dir = $dir === "asc" ? "asc" : "desc";
 
$sql = "
SELECT
  m.codigo,
  m.nome,
  c.nome AS categoria,
  l.nome AS localizacao,
  m.emprestado_a,
  m.emprestado_em
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
 
// map de ordenação permitido
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
 
// filtrar só atrasados (igual à página)
if ($so_atrasados) {
  $rows = array_values(array_filter($rows, function($r) use ($limite_atraso) {
    if (empty($r["emprestado_em"])) return false;
    $dias = (int)floor((time() - strtotime($r["emprestado_em"])) / 86400);
    if ($dias < 0) $dias = 0;
    return $dias >= $limite_atraso;
  }));
}
 
header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=emprestimos_ativos.csv");
 
$out = fopen("php://output", "w");
 
fputcsv($out, ["Código", "Material", "Categoria", "Localização", "Emprestado a", "Desde", "Dias emprestado", "Atrasado"]);
 
foreach ($rows as $r) {
  $dias = 0;
  if (!empty($r["emprestado_em"])) {
    $dias = (int)floor((time() - strtotime($r["emprestado_em"])) / 86400);
    if ($dias < 0) $dias = 0;
  }
  $atrasado = (!empty($r["emprestado_em"]) && $dias >= $limite_atraso) ? "Sim" : "Não";
 
  fputcsv($out, [
    $r["codigo"],
    $r["nome"],
    $r["categoria"],
    $r["localizacao"],
    $r["emprestado_a"],
    $r["emprestado_em"],
    $dias,
    $atrasado
  ]);
}
 
fclose($out);
exit;