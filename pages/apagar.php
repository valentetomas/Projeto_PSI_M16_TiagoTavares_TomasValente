<?php
require_once __DIR__ . "/auth/admin_guard.php";
require_once __DIR__ . "/auth/guard.php";
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/mensagens.php";

 
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  header("Location: /Projeto_PSI_M16_TiagoTavares_TomasValente/pages/listar.php");
  exit;
}
 
$id = (int)($_POST["id"] ?? 0);
 
if ($id > 0) {
  $stmt = $pdo->prepare("DELETE FROM materiais WHERE id = ?");
  $stmt->execute([$id]);
  set_mensagem("success", "Material removido com sucesso.");

}
 
header("Location: /Projeto_PSI_M16_TiagoTavares_TomasValente/pages/listar.php");
exit;