<?php

require_once __DIR__ . "/sessao.php";

if (!isset($_SESSION["usuario"])) {
    header("Location: ../login.php");
    exit;
}

if ($_SESSION["usuario"]["tipo"] !== "admin") {
    http_response_code(403);
    exit("Acesso negado! Somente administradores podem acessar esta página.");
}

?>