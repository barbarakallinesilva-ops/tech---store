
<?php
require_once __DIR__ . "/includes/sessao.php";
require_once __DIR__ . "/config/conexao.php";

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

if (empty($_SESSION["carrinho"])) {
    header("Location: carrinho.php");
    exit;
}

$mensagem = "";
$confirmado = isset($_GET["confirmado"]);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["finalizar"])) {
    $formaPagamento = $_POST["forma_pagamento"] ?? "";
    $endereco = trim($_POST["endereco"] ?? "");
    $formasPermitidas = ["PIX", "Boleto", "Cartão"];

    if (!in_array($formaPagamento, $formasPermitidas, true)) {
        $mensagem = "Selecione uma forma de pagamento válida.";
    } elseif ($endereco === "") {
        $mensagem = "Informe o endereço de entrega.";
    } else {
        try {
            $conexao->beginTransaction();

            $total = 0;
            $produtosPedido = [];

            foreach ($_SESSION["carrinho"] as $id => $quantidade) {
                $consulta = $conexao->prepare(
                    "SELECT id, preco, quantidade
                     FROM produtos WHERE id = ? FOR UPDATE"
                );
                $consulta->execute([(int)$id]);
                $produto = $consulta->fetch(PDO::FETCH_ASSOC);

                if (!$produto || $quantidade <= 0 ||
                    $quantidade > $produto["quantidade"]) {
                    throw new Exception(
                        "Estoque insuficiente para um dos produtos."
                    );
                }

                $total += $produto["preco"] * $quantidade;

                $produtosPedido[] = [
                    "id" => $produto["id"],
                    "quantidade" => $quantidade,
                    "preco" => $produto["preco"]
                ];
            }

            $pedido = $conexao->prepare(
                "INSERT INTO pedidos
                (usuario_id, total, status, endereco_entrega, forma_pagamento)
                VALUES (?, ?, 'pago', ?, ?)"
            );

            $pedido->execute([
                $_SESSION["usuario"]["id"],
                $total,
                $endereco,
                $formaPagamento
            ]);

            $pedidoId = $conexao->lastInsertId();

            foreach ($produtosPedido as $item) {
                $inserir = $conexao->prepare(
                    "INSERT INTO itens_pedido
                    (pedido_id, produto_id, quantidade, preco_unitario)
                    VALUES (?, ?, ?, ?)"
                );

                $inserir->execute([
                    $pedidoId,
                    $item["id"],
                    $item["quantidade"],
                    $item["preco"]
                ]);

                $estoque = $conexao->prepare(
                    "UPDATE produtos
                    SET quantidade = quantidade - ?
                    WHERE id = ?"
                );

                $estoque->execute([
                    $item["quantidade"],
                    $item["id"]
                ]);
            }

            $conexao->commit();

            $_SESSION["carrinho"] = [];
            $_SESSION["pedido_confirmado"] = $pedidoId;

            header("Location: checkout.php?confirmado=1");
            exit;

        } catch (Exception $erro) {
            if ($conexao->inTransaction()) {
                $conexao->rollBack();
            }

            $mensagem = $erro->getMessage();
        }
    }
}

$total = 0;
$itens = [];

foreach ($_SESSION["carrinho"] as $id => $quantidade) {
    $consulta = $conexao->prepare(
        "SELECT nome, preco FROM produtos WHERE id = ?"
    );
    $consulta->execute([(int)$id]);
    $produto = $consulta->fetch(PDO::FETCH_ASSOC);

    if ($produto) {
        $subtotal = $produto["preco"] * $quantidade;
        $total += $subtotal;

        $itens[] = [
            "nome" => $produto["nome"],
            "quantidade" => $quantidade,
            "subtotal" => $subtotal
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Finalizar compra - Tech Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
<div class="container py-5">

    <h1 class="mb-4">Finalizar compra</h1>

    <?php if ($confirmado): ?>
        <div class="alert alert-success">
            <h3>Compra realizada com sucesso!</h3>
            <p>Seu pedido foi registrado na Tech Store.</p>
            <p>Número do pedido:
                <strong><?= htmlspecialchars((string)($_SESSION["pedido_confirmado"] ?? "")) ?></strong>
            </p>
        </div>
        <a href="index.php" class="btn btn-primary">Voltar à loja</a>

    <?php else: ?>

        <?php if ($mensagem): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <div class="card p-4 mb-4">
            <h4>Resumo do pedido</h4>

            <?php foreach ($itens as $item): ?>
                <p>
                    <?= htmlspecialchars($item["nome"]) ?>
                    (<?= (int)$item["quantidade"] ?>)
                    — R$ <?= number_format($item["subtotal"], 2, ",", ".") ?>
                </p>
            <?php endforeach; ?>

            <hr>
            <h4>Total: R$ <?= number_format($total, 2, ",", ".") ?></h4>
        </div>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Endereço de entrega</label>
                <input type="text" name="endereco" class="form-control"
                       maxlength="255" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Forma de pagamento</label>
                <select name="forma_pagamento" class="form-select" required>
                    <option value="">Selecione</option>
                    <option value="PIX">PIX</option>
                    <option value="Boleto">Boleto</option>
                    <option value="Cartão">Cartão</option>
                </select>
            </div>

            <button type="submit" name="finalizar" class="btn btn-success">
                Confirmar pedido
            </button>

            <a href="carrinho.php" class="btn btn-secondary">
                Voltar ao carrinho
            </a>
        </form>
    <?php endif; ?>

</div>
</body>
</html>
