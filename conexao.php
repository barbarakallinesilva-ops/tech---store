
<?php

$arquivoEnv = __DIR__ . "/../.env";

if (!file_exists($arquivoEnv)) {
    die("Arquivo de configuração do banco não encontrado.");
}

$config = parse_ini_file($arquivoEnv, false, INI_SCANNER_RAW);

if ($config === false) {
    die("Erro ao carregar configurações.");
}

$host = $config["DB_HOST"] ?? "";
$banco = $config["DB_NAME"] ?? "";
$usuario = $config["DB_USER"] ?? "";
$senha = $config["DB_PASS"] ?? "";

try {
    $conexao = new PDO(
        "mysql:host=$host;dbname=$banco;charset=utf8mb4",
        $usuario,
        $senha,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

} catch (PDOException $erro) {
    error_log($erro->getMessage());
    die("Não foi possível conectar ao banco de dados.");
}

?>
