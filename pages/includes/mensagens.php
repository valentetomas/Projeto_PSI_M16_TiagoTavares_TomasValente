<?php
// pages/includes/mensagens.php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
 
function set_mensagem($tipo, $texto) {
  $_SESSION["mensagem"] = [
    "tipo" => $tipo,
    "texto" => $texto
  ];
}
 
function get_mensagem() {
  if (!isset($_SESSION["mensagem"])) {
    return null;
  }
 
  $msg = $_SESSION["mensagem"];
  unset($_SESSION["mensagem"]);
  return $msg;
}