
<?php

require_once __DIR__ . "/config/conexao.php";
require_once __DIR__ . "/includes/sessao.php";

$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = trim($_POST["nome"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $senha = $_POST["senha"] ?? "";
    $confirmarSenha = $_POST["confirmar_senha"] ?? "";

    if ($nome === "" || strlen($nome) > 100) {
        $mensagem = "Informe um nome válido.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $mensagem = "Informe um e-mail válido.";

    } elseif (strlen($senha) < 8) {
        $mensagem = "A senha precisa ter pelo menos 8 caracteres.";

    } elseif ($senha !== $confirmarSenha) {
        $mensagem = "As senhas não são iguais.";

    } else {

        $verificar = $conexao->prepare(
            "SELECT id FROM usuarios WHERE email = ?"
        );

        $verificar->execute([$email]);

        if ($verificar->fetch()) {

            $mensagem = "Este e-mail já está cadastrado.";

        } else {

            $senhaProtegida = password_hash($senha, PASSWORD_DEFAULT);

            $cadastro = $conexao->prepare(
                "INSERT INTO usuarios (nome, email, senha, tipo)
                 VALUES (?, ?, ?, 'cliente')"
            );

            $cadastro->execute([
                $nome,
                $email,
                $senhaProtegida
            ]);

            header("Location: login.php?cadastro=sucesso");
            exit;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastro - TECH STORE</title>
</head>

<body>

    <h1>TECH STORE</h1>
    <h2>Criar conta</h2>

    <?php if ($mensagem !== ""): ?>
        <p><?= htmlspecialchars($mensagem) ?></p>
    <?php endif; ?>

    <form method="POST">

        <label>Nome completo:</label>
        <input type="text" name="nome" maxlength="100" required>

        <br><br>

        <label>E-mail:</label>
        <input type="email" name="email" maxlength="150" required>

        <br><br>

        <label>Senha:</label>
        <input type="password" name="senha" minlength="8" required>

        <br><br>

        <label>Confirmar senha:</label>
        <input type="password" name="confirmar_senha" minlength="8" required>

        <br><br>

        <button type="submit">Cadastrar</button>

    </form>

</body>
</html>
