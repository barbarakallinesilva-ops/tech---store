
<?php
require_once __DIR__ . "/../includes/proteger_admin.php";
require_once __DIR__ . "/../config/conexao.php";

$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $pedidoId = (int)($_POST["pedido_id"] ?? 0);
    $status = $_POST["status"] ?? "";

    $permitidos = ["pendente", "pago", "enviado", "cancelado"];

    if ($pedidoId > 0 && in_array($status, $permitidos, true)) {
        $atualizar = $conexao->prepare(
            "UPDATE pedidos SET status = ? WHERE id = ?"
        );
        $atualizar->execute([$status, $pedidoId]);
        $mensagem = "Status do pedido atualizado!";
    }
}

$pedidos = $conexao->query(
    "SELECT p.*, u.nome AS cliente, u.email
     FROM pedidos p
     INNER JOIN usuarios u ON u.id = p.usuario_id
     ORDER BY p.criado_em DESC"
)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pedidos - Tech Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../estilos.css">
</head>
<body>
<div class="container py-5">

    <h1>Gerenciamento de pedidos</h1>

    <a href="produtos.php" class="btn btn-secondary mb-4">
        Voltar à administração
    </a>

    <?php if ($mensagem): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($mensagem) ?>
        </div>
    <?php endif; ?>

    <?php if (!$pedidos): ?>
        <div class="alert alert-info">Nenhum pedido registrado.</div>
    <?php endif; ?>

    <?php foreach ($pedidos as $pedido): ?>
        <div class="card p-4 mb-4">
            <h4>Pedido #<?= (int)$pedido["id"] ?></h4>

            <p><strong>Cliente:</strong>
                <?= htmlspecialchars($pedido["cliente"]) ?>
            </p>

            <p><strong>E-mail:</strong>
                <?= htmlspecialchars($pedido["email"]) ?>
            </p>

            <p><strong>Data:</strong>
                <?= htmlspecialchars($pedido["criado_em"]) ?>
            </p>

            <p><strong>Endereço:</strong>
                <?= htmlspecialchars($pedido["endereco_entrega"] ?? "") ?>
            </p>

            <p><strong>Pagamento:</strong>
                <?= htmlspecialchars($pedido["forma_pagamento"] ?? "") ?>
            </p>

            <h5>Produtos</h5>

            <?php
            $itensConsulta = $conexao->prepare(
                "SELECT i.quantidade, i.preco_unitario, pr.nome
                 FROM itens_pedido i
                 INNER JOIN produtos pr ON pr.id = i.produto_id
                 WHERE i.pedido_id = ?"
            );
            $itensConsulta->execute([$pedido["id"]]);
            $itens = $itensConsulta->fetchAll(PDO::FETCH_ASSOC);
            ?>

            <?php foreach ($itens as $item): ?>
                <p>
                    <?= htmlspecialchars($item["nome"]) ?>
                    — <?= (int)$item["quantidade"] ?> unidade(s)
                    — R$ <?= number_format($item["preco_unitario"], 2, ",", ".") ?>
                </p>
            <?php endforeach; ?>

            <h5>
                Total: R$ <?= number_format($pedido["total"], 2, ",", ".") ?>
            </h5>

            <form method="POST" class="mt-3">
                <input type="hidden" name="pedido_id"
                       value="<?= (int)$pedido["id"] ?>">

                <label class="form-label">Status do pedido</label>

                <div class="d-flex gap-2 flex-wrap">
                    <select name="status" class="form-select">
                        <?php foreach (["pendente", "pago", "enviado", "cancelado"] as $status): ?>
                            <option value="<?= $status ?>"
                                <?= $pedido["status"] === $status ? "selected" : "" ?>>
                                <?= ucfirst($status) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button class="btn btn-primary" type="submit">
                        Atualizar
                    </button>
                </div>
            </form>
        </div>
    <?php endforeach; ?>

</div>
</body>
</html>
