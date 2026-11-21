
<?php

// PROTEGER A ÁREA ADMINISTRATIVA
require_once __DIR__ . "/../includes/proteger_admin.php";

// CONEXÃO COM O BANCO
require_once __DIR__ . "/../config/conexao.php";

$mensagem = "";
$tipoMensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $produtoId = (int) ($_POST["produto_id"] ?? 0);
    $tipo = $_POST["tipo"] ?? "";
    $quantidade = (int) ($_POST["quantidade"] ?? 0);
    $observacao = trim($_POST["observacao"] ?? "");

    if ($produtoId <= 0 || $quantidade <= 0 || !in_array($tipo, ["entrada", "saida"], true)) {

        $mensagem = "Preencha os campos corretamente.";
        $tipoMensagem = "erro";

    } else {

        try {

            $conexao->beginTransaction();

            $consulta = $conexao->prepare(
                "SELECT quantidade FROM produtos WHERE id = ? FOR UPDATE"
            );

            $consulta->execute([$produtoId]);
            $produto = $consulta->fetch(PDO::FETCH_ASSOC);

            if (!$produto) {
                throw new Exception("Produto não encontrado.");
            }

            $estoqueAtual = (int) $produto["quantidade"];

            if ($tipo === "saida" && $quantidade > $estoqueAtual) {
                throw new Exception("Não há estoque suficiente para essa saída.");
            }

            if ($tipo === "entrada") {
                $novoEstoque = $estoqueAtual + $quantidade;
            } else {
                $novoEstoque = $estoqueAtual - $quantidade;
            }

            $atualizar = $conexao->prepare(
                "UPDATE produtos SET quantidade = ? WHERE id = ?"
            );

            $atualizar->execute([$novoEstoque, $produtoId]);

            $registrar = $conexao->prepare(
                "INSERT INTO estoque_movimentacoes
                (produto_id, tipo, quantidade, observacao)
                VALUES (?, ?, ?, ?)"
            );

            $registrar->execute([
                $produtoId,
                $tipo,
                $quantidade,
                $observacao
            ]);

            $conexao->commit();

            $mensagem = "Movimentação registrada com sucesso!";
            $tipoMensagem = "sucesso";

        } catch (Exception $erro) {

            if ($conexao->inTransaction()) {
                $conexao->rollBack();
            }

            $mensagem = $erro->getMessage();
            $tipoMensagem = "erro";
        }
    }
}

// BUSCAR PRODUTOS
$produtos = $conexao->query(
    "SELECT id, nome, quantidade FROM produtos ORDER BY nome"
)->fetchAll(PDO::FETCH_ASSOC);

// BUSCAR HISTÓRICO
$historico = $conexao->query(
    "SELECT
        estoque_movimentacoes.*,
        produtos.nome AS nome_produto
     FROM estoque_movimentacoes
     INNER JOIN produtos
        ON produtos.id = estoque_movimentacoes.produto_id
     ORDER BY estoque_movimentacoes.criado_em DESC
     LIMIT 30"
)->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Controle de Estoque - TECH STORE</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            padding: 25px;
            color: #222;
        }

        .container {
            max-width: 1000px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
        }

        input, select, textarea, button {
            display: block;
            width: 100%;
            padding: 12px;
            margin-top: 8px;
            margin-bottom: 15px;
            box-sizing: border-box;
        }

        button {
            background: #111;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
        }

        button:hover {
            background: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        th {
            background: #222;
            color: white;
        }

        .sucesso {
            background: #d8f5df;
            padding: 12px;
            margin: 15px 0;
        }

        .erro {
            background: #ffe0e0;
            padding: 12px;
            margin: 15px 0;
        }

        a {
            color: #222;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>TECH STORE</h1>
    <h2>Controle de Estoque</h2>

    <p>
        Administrador:
        <?= htmlspecialchars($_SESSION["usuario"]["nome"]) ?>
    </p>

    <p>
        <a href="../index.php">Voltar à loja</a> |
        <a href="produtos.php">Cadastro de produtos</a> |
        <a href="../sair.php">Sair da conta</a>
    </p>

    <?php if ($mensagem !== ""): ?>
        <div class="<?= htmlspecialchars($tipoMensagem) ?>">
            <?= htmlspecialchars($mensagem) ?>
        </div>
    <?php endif; ?>

    <h2>Registrar movimentação</h2>

    <form method="POST">

        <label>Produto:</label>

        <select name="produto_id" required>
            <option value="">Selecione um produto</option>

            <?php foreach ($produtos as $produto): ?>
                <option value="<?= $produto["id"] ?>">
                    <?= htmlspecialchars($produto["nome"]) ?>
                    (Estoque: <?= $produto["quantidade"] ?>)
                </option>
            <?php endforeach; ?>

        </select>

        <label>Tipo de movimentação:</label>

        <select name="tipo" required>
            <option value="">Selecione</option>
            <option value="entrada">Entrada</option>
            <option value="saida">Saída</option>
        </select>

        <label>Quantidade:</label>

        <input type="number" name="quantidade" min="1" required>

        <label>Observação:</label>

        <textarea
            name="observacao"
            maxlength="255"
            placeholder="Exemplo: Reposição de mercadoria"
        ></textarea>

        <button type="submit">
            Registrar movimentação
        </button>

    </form>

    <hr>

    <h2>Estoque atual</h2>

    <table>
        <tr>
            <th>Produto</th>
            <th>Quantidade disponível</th>
        </tr>

        <?php foreach ($produtos as $produto): ?>
            <tr>
                <td><?= htmlspecialchars($produto["nome"]) ?></td>
                <td><?= $produto["quantidade"] ?></td>
            </tr>
        <?php endforeach; ?>

    </table>

    <hr>

    <h2>Histórico de movimentações</h2>

    <table>
        <tr>
            <th>Produto</th>
            <th>Tipo</th>
            <th>Quantidade</th>
            <th>Observação</th>
            <th>Data</th>
        </tr>

        <?php foreach ($historico as $movimento): ?>
            <tr>
                <td><?= htmlspecialchars($movimento["nome_produto"]) ?></td>

                <td>
                    <?= $movimento["tipo"] === "entrada" ? "Entrada" : "Saída" ?>
                </td>

                <td><?= $movimento["quantidade"] ?></td>

                <td><?= htmlspecialchars($movimento["observacao"] ?? "") ?></td>

                <td><?= htmlspecialchars($movimento["criado_em"]) ?></td>
            </tr>
        <?php endforeach; ?>

    </table>

</div>

</body>
</html>
