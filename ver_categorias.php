<?php

require_once __DIR__ . "/config/conexao.php";

$consulta = $conexao->query(
    "SELECT DISTINCT categoria FROM produtos ORDER BY categoria"
);

$categorias = $consulta->fetchAll(PDO::FETCH_COLUMN);

echo "<h2>Categorias cadastradas no banco:</h2>";

foreach ($categorias as $categoria) {
    echo htmlspecialchars($categoria ?? "Sem categoria") . "<br>";
}