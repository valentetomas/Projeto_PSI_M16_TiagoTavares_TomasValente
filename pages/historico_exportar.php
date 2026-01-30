<?php
require_once __DIR__ . "/auth/guard.php";
require_once __DIR__ . "/includes/db.php";
 
$material_id = trim($_GET["material_id"] ?? "");
$tipo = trim($_GET["tipo"] ?? "");
$search = trim($_GET["search"] ?? "");
$data_inicio = trim($_GET["data_inicio"] ?? "");
$data_fim = trim($_GET["data_fim"] ?? "");
 
$params = [];
$sql = "
SELECT
  mov.data_movimento,
  mov.tipo,
  m.codigo,
  m.nome,
  mov.responsavel,
  u.nome AS registado_por,
  mov.observacao
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
 
if ($data_inicio !== "") {
  $sql .= " AND mov.data_movimento >= ? ";
  $params[] = $data_inicio . " 00:00:00";
}
 
if ($data_fim !== "") {
  $sql .= " AND mov.data_movimento <= ? ";
  $params[] = $data_fim . " 23:59:59";
}
 
if ($search !== "") {
  $sql .= " AND (
    m.nome LIKE ? OR
    m.codigo LIKE ? OR
    mov.responsavel LIKE ? OR
    mov.observacao LIKE ? OR
    u.nome LIKE ?
  ) ";
  for ($i=0; $i<5; $i++) {
    $params[] = "%{$search}%";
  }
}
 
$sql .= " ORDER BY mov.data_movimento DESC ";
 
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
 
header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=historico.csv");
 
$output = fopen("php://output", "w");
 
fputcsv($output, [
  "Data",
  "Tipo",
  "Código",
  "Material",
  "Responsável",
  "Registado por",
  "Observação"
]);
 
foreach ($rows as $r) {
  fputcsv($output, [
    $r["data_movimento"],
    $r["tipo"],
    $r["codigo"],
    $r["nome"],
    $r["responsavel"],
    $r["registado_por"] ?? "Sistema",
    $r["observacao"]
  ]);
}
 
fclose($output);
exit;