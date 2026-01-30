<?php
session_start();
session_destroy();
 
header("Location: /Projeto_PSI_M16_TiagoTavares_TomasValente/pages/auth/login.php");
exit;