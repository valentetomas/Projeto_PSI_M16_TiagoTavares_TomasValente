<?php
// pages/auth/guard.php
session_start();
 
if (!isset($_SESSION["user_id"])) {
  header("Location: /Projeto_PSI_M16_TiagoTavares_TomasValente/pages/auth/login.php");
  exit;
}