
<?php

require_once __DIR__ . "/includes/sessao.php";
require_once __DIR__ . "/config/conexao.php";

$mensagem = "";

if (isset($_GET["cadastro"])) {
    $mensagem = "Cadastro realizado! Agora entre na sua conta.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $senha = $_POST["senha"] ?? "";

    $consulta = $conexao->prepare(
        "SELECT id, nome, email, senha, tipo
         FROM usuarios
         WHERE email = ?"
    );

    $consulta->execute([$email]);

    $usuario = $consulta->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($senha, $usuario["senha"])) {

        session_regenerate_id(true);

        $_SESSION["usuario"] = [
            "id" => $usuario["id"],
            "nome" => $usuario["nome"],
            "email" => $usuario["email"],
            "tipo" => $usuario["tipo"]
        ];

        header("Location: index.php");
        exit;

    } else {

        $mensagem = "E-mail ou senha incorretos.";
    }
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - TECH STORE</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="estilos.css">
</head>

<body>

<div class="container py-5">

    <h1>TECH STORE</h1>

    <h2>Entrar na conta</h2>

    <?php if ($mensagem !== ""): ?>
        <div class="alert alert-info">
            <?= htmlspecialchars($mensagem) ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <div class="mb-3">
            <label class="form-label">E-mail:</label>
            <input type="email" name="email" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Senha:</label>
            <input type="password" name="senha" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary">
            Entrar
        </button>

    </form>

    <p class="mt-3">Ainda não tem conta?</p>

    <a href="cadastro.php">Criar uma conta</a>

    <br><br>

    <a href="index.php">Voltar à loja</a>

</div>

</body>
</html>
