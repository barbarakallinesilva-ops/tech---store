
<?php
require_once __DIR__ . "/includes/sessao.php";
require_once __DIR__ . "/config/conexao.php";

$usuario = $_SESSION["usuario"] ?? null;

$id = (int)($_GET["id"] ?? 0);

$consulta = $conexao->prepare(
    "SELECT id, nome, descricao, preco, quantidade, categoria, imagem
     FROM produtos
     WHERE id = ?"
);

$consulta->execute([$id]);
$produto = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$produto) {
    http_response_code(404);
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>
        <?= $produto ? htmlspecialchars($produto["nome"]) : "Produto não encontrado" ?> - TECH STORE
    </title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="estilos.css">
</head>

<body>

<header class="container-fluid bg-dark text-white p-3">
    <div class="container d-flex justify-content-between align-items-center flex-wrap">

        <h1>TECH STORE</h1>

        <nav class="d-flex gap-3">
            <a href="index.php" class="text-white">Início</a>
            <a href="carrinho.php" class="text-white">Carrinho</a>

            <?php if ($usuario): ?>
                <a href="sair.php" class="text-white">Sair</a>
            <?php else: ?>
                <a href="login.php" class="text-white">Entrar</a>
            <?php endif; ?>
        </nav>

    </div>
</header>

<main class="container py-5">

    <?php if (!$produto): ?>

        <div class="alert alert-warning">
            Produto não encontrado.
        </div>

        <a href="index.php" class="btn btn-dark">
            Voltar à loja
        </a>

    <?php else: ?>

        <div class="row g-4 align-items-center">

            <div class="col-md-6">

                <?php if (!empty($produto["imagem"])): ?>

                    <img
                        src="<?= htmlspecialchars($produto["imagem"]) ?>"
                        alt="<?= htmlspecialchars($produto["nome"]) ?>"
                        class="img-fluid rounded"
                        style="width: 100%; max-height: 450px; object-fit: contain;"
                    >

                <?php else: ?>

                    <div class="bg-light text-center p-5">
                        Imagem não disponível
                    </div>

                <?php endif; ?>

            </div>

            <div class="col-md-6">

                <h2><?= htmlspecialchars($produto["nome"]) ?></h2>

                <p class="text-muted">
                    Categoria:
                    <?= htmlspecialchars($produto["categoria"] ?? "Não informada") ?>
                </p>

                <p>
                    <?= nl2br(htmlspecialchars($produto["descricao"] ?? "Sem descrição.")) ?>
                </p>

                <h3 class="text-primary">
                    R$ <?= number_format($produto["preco"], 2, ",", ".") ?>
                </h3>

                <p>
                    <strong>Estoque disponível:</strong>
                    <?= (int)$produto["quantidade"] ?> unidade(s)
                </p>

                <?php if ($produto["quantidade"] > 0): ?>

                    <form action="carrinho.php" method="POST">

                        <input
                            type="hidden"
                            name="produto_id"
                            value="<?= (int)$produto["id"] ?>"
                        >

                        <button
                            type="submit"
                            name="adicionar"
                            class="btn btn-primary btn-lg w-100"
                        >
                            Adicionar ao carrinho
                        </button>

                    </form>

                <?php else: ?>

                    <button class="btn btn-secondary w-100" disabled>
                        Produto indisponível
                    </button>

                <?php endif; ?>

                <a href="index.php" class="btn btn-outline-dark mt-3 w-100">
                    Voltar à loja
                </a>

            </div>

        </div>

    <?php endif; ?>

</main>

<footer class="container-fluid bg-dark text-white text-center p-3 mt-5">
    <p>TECH STORE - Sua loja de tecnologia</p>
</footer>

</body>
</html>
