<?php

// includes/db.php
 
$host = "localhost";

$dbname = "cte_inventario";

$user = "root";

$pass = ""; // XAMPP: password vazia por defeito
 
try {

  $pdo = new PDO(

    "mysql:host=$host;dbname=$dbname;charset=utf8mb4",

    $user,

    $pass,

    [

      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC

    ]

  );

} catch (PDOException $e) {

  die("Erro na ligação à Base de Dados: " . $e->getMessage());

}

 