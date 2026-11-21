
<?php

require_once __DIR__ . "/includes/sessao.php";
require_once __DIR__ . "/config/conexao.php";

if (!isset($_SESSION["carrinho"])) {
    $_SESSION["carrinho"] = [];
}

$mensagem = "";

// ADICIONAR PRODUTO
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["adicionar"])) {

    $produtoId = (int) ($_POST["produto_id"] ?? 0);

    $consulta = $conexao->prepare(
        "SELECT id, nome, quantidade FROM produtos WHERE id = ?"
    );

    $consulta->execute([$produtoId]);
    $produto = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$produto) {
        $mensagem = "Produto não encontrado.";
    } elseif ((int)$produto["quantidade"] <= 0) {
        $mensagem = "Produto sem estoque.";
    } else {

        $quantidadeAtual = $_SESSION["carrinho"][$produtoId] ?? 0;

        if ($quantidadeAtual + 1 > (int)$produto["quantidade"]) {
            $mensagem = "Quantidade maior que o estoque disponível.";
        } else {
            $_SESSION["carrinho"][$produtoId] = $quantidadeAtual + 1;
            $mensagem = "Produto adicionado ao carrinho!";
        }
    }
}

// ATUALIZAR QUANTIDADES
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["atualizar"])) {

    $quantidades = $_POST["quantidades"] ?? [];

    foreach ($quantidades as $id => $quantidade) {

        $id = (int)$id;
        $quantidade = (int)$quantidade;

        if ($quantidade <= 0) {
            unset($_SESSION["carrinho"][$id]);
            continue;
        }

        $consulta = $conexao->prepare(
            "SELECT quantidade FROM produtos WHERE id = ?"
        );

        $consulta->execute([$id]);
        $produto = $consulta->fetch(PDO::FETCH_ASSOC);

        if ($produto) {
            $_SESSION["carrinho"][$id] = min(
                $quantidade,
                (int)$produto["quantidade"]
            );
        } else {
            unset($_SESSION["carrinho"][$id]);
        }
    }

    $mensagem = "Carrinho atualizado!";
}

// REMOVER PRODUTO
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["remover"])) {

    $produtoId = (int)$_POST["remover"];

    unset($_SESSION["carrinho"][$produtoId]);

    $mensagem = "Produto removido do carrinho.";
}

// ESVAZIAR CARRINHO
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["esvaziar"])) {

    $_SESSION["carrinho"] = [];

    $mensagem = "Carrinho esvaziado.";
}

// BUSCAR PRODUTOS DO CARRINHO
$itens = [];
$total = 0;

if (!empty($_SESSION["carrinho"])) {

    $ids = array_keys($_SESSION["carrinho"]);
    $marcadores = implode(",", array_fill(0, count($ids), "?"));

    $consulta = $conexao->prepare(
        "SELECT id, nome, preco, quantidade, imagem
         FROM produtos
         WHERE id IN ($marcadores)"
    );

    $consulta->execute($ids);

    $produtos = $consulta->fetchAll(PDO::FETCH_ASSOC);

    foreach ($produtos as $produto) {

        $id = (int)$produto["id"];
        $quantidade = (int)$_SESSION["carrinho"][$id];

        $subtotal = (float)$produto["preco"] * $quantidade;

        $produto["quantidade_carrinho"] = $quantidade;
        $produto["subtotal"] = $subtotal;

        $itens[] = $produto;

        $total += $subtotal;
    }
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Carrinho - TECH STORE</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="estilos.css">

    <style>
        .imagem-carrinho {
            width: 90px;
            height: 90px;
            object-fit: contain;
            background: white;
            border-radius: 8px;
            padding: 5px;
        }

        .tabela-carrinho td {
            vertical-align: middle;
        }
    </style>
</head>

<body>

<div class="container py-5">

    <h1>Meu carrinho - TECH STORE</h1>

    <p>
        <a href="index.php">Continuar comprando</a>
    </p>

    <?php if ($mensagem !== ""): ?>
        <div class="alert alert-info">
            <?= htmlspecialchars($mensagem) ?>
        </div>
    <?php endif; ?>

    <?php if (count($itens) > 0): ?>

        <form method="POST">

            <div class="table-responsive">

                <table class="table table-bordered tabela-carrinho">

                    <thead>
                        <tr>
                            <th>Imagem</th>
                            <th>Produto</th>
                            <th>Preço</th>
                            <th>Quantidade</th>
                            <th>Subtotal</th>
                            <th>Ação</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($itens as $item): ?>

                            <tr>

                                <td>
                                    <?php if (!empty($item["imagem"])): ?>

                                        <img
                                            src="<?= htmlspecialchars($item["imagem"]) ?>"
                                            alt="<?= htmlspecialchars($item["nome"]) ?>"
                                            class="imagem-carrinho"
                                        >

                                    <?php else: ?>

                                        <span>Sem imagem</span>

                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($item["nome"]) ?>
                                </td>

                                <td>
                                    R$ <?= number_format($item["preco"], 2, ",", ".") ?>
                                </td>

                                <td>

                                    <input
                                        type="number"
                                        name="quantidades[<?= $item["id"] ?>]"
                                        value="<?= $item["quantidade_carrinho"] ?>"
                                        min="0"
                                        max="<?= (int)$item["quantidade"] ?>"
                                        class="form-control"
                                        style="width: 90px"
                                    >

                                    <small>
                                        Estoque: <?= (int)$item["quantidade"] ?>
                                    </small>

                                </td>

                                <td>
                                    R$ <?= number_format($item["subtotal"], 2, ",", ".") ?>
                                </td>

                                <td>

                                    <button
                                        type="submit"
                                        name="remover"
                                        value="<?= $item["id"] ?>"
                                        class="btn btn-danger btn-sm"
                                        formnovalidate
                                    >
                                        Remover
                                    </button>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

            <button
                type="submit"
                name="atualizar"
                class="btn btn-primary"
            >
                Atualizar quantidades
            </button>

        </form>

        <h3 class="mt-4">
            Total: R$ <?= number_format($total, 2, ",", ".") ?>
        </h3>

        <form method="POST" class="mt-3">

            <button
                type="submit"
                name="esvaziar"
                class="btn btn-outline-danger"
            >
                Esvaziar carrinho
            </button>

        </form>

        <a href="checkout.php" class="btn btn-success mt-3">
            Finalizar compra
        </a>

    <?php else: ?>

        <p>Seu carrinho está vazio.</p>

    <?php endif; ?>

</div>

</body>
</html>
