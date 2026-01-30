<?php
require_once __DIR__ . "/guard.php";
 
if (($_SESSION["user_role"] ?? "") !== "admin") {
  header("Location: /Projeto_PSI_M16_TiagoTavares_TomasValente/pages/403.php");
  exit;
}