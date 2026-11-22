
<?php

require_once __DIR__ . "/../includes/proteger_admin.php";
require_once __DIR__ . "/../config/conexao.php";

// EXCLUIR PRODUTO
if (isset($_GET["excluir"])) {

    $id = (int) $_GET["excluir"];

    $sql = $conexao->prepare("DELETE FROM produtos WHERE id = ?");
    $sql->execute([$id]);

    header("Location: produtos.php");
    exit;
}

// BUSCAR PRODUTO PARA EDITAR
$produtoEdicao = null;

if (isset($_GET["editar"])) {

    $id = (int) $_GET["editar"];

    $consulta = $conexao->prepare("SELECT * FROM produtos WHERE id = ?");
    $consulta->execute([$id]);

    $produtoEdicao = $consulta->fetch(PDO::FETCH_ASSOC);
}

// CADASTRAR OU ATUALIZAR PRODUTO
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = trim($_POST["nome"] ?? "");
    $descricao = trim($_POST["descricao"] ?? "");
    $preco = $_POST["preco"] ?? "";
    $quantidade = $_POST["quantidade"] ?? "";
    $categoria = trim($_POST["categoria"] ?? "");
    $imagem = trim($_POST["imagem"] ?? "");
    $id = $_POST["id"] ?? "";

    if (
        $nome === "" ||
        !is_numeric($preco) ||
        $preco < 0 ||
        !filter_var($quantidade, FILTER_VALIDATE_INT) ||
        $quantidade < 0
    ) {
        exit("Confira os dados informados.");
    }

    if ($imagem !== "" && !filter_var($imagem, FILTER_VALIDATE_URL)) {
        exit("Informe um endereço de imagem válido.");
    }

    if ($id !== "") {

        // PRESERVAR A IMAGEM SE O CAMPO FOR DEIXADO VAZIO
        if ($imagem === "") {
            $consultaImagem = $conexao->prepare(
                "SELECT imagem FROM produtos WHERE id = ?"
            );
            $consultaImagem->execute([$id]);
            $imagem = $consultaImagem->fetchColumn() ?: "";
        }

        $sql = "UPDATE produtos
                SET nome = ?, descricao = ?, preco = ?, quantidade = ?,
                    categoria = ?, imagem = ?
                WHERE id = ?";

        $comando = $conexao->prepare($sql);

        $comando->execute([
            $nome,
            $descricao,
            $preco,
            $quantidade,
            $categoria,
            $imagem,
            $id
        ]);

    } else {

        $sql = "INSERT INTO produtos
                (nome, descricao, preco, quantidade, categoria, imagem)
                VALUES (?, ?, ?, ?, ?, ?)";

        $comando = $conexao->prepare($sql);

        $comando->execute([
            $nome,
            $descricao,
            $preco,
            $quantidade,
            $categoria,
            $imagem
        ]);
    }

    header("Location: produtos.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Produtos - TECH STORE</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

<div class="container py-4">

    <h1>Cadastro de Produtos - TECH STORE</h1>

    <p>
        Administrador:
        <?= htmlspecialchars($_SESSION["usuario"]["nome"]) ?>
    </p>

    <a href="../index.php">Voltar para a loja</a> |
    <a href="../sair.php">Sair da conta</a>

    <hr>

    <h2>
        <?= $produtoEdicao ? "Editar produto" : "Cadastrar produto" ?>
    </h2>

    <form method="POST">

        <input type="hidden" name="id"
            value="<?= htmlspecialchars($produtoEdicao['id'] ?? '') ?>">

        <div class="mb-3">
            <label class="form-label">Nome do produto:</label>
            <input type="text" name="nome" class="form-control"
                value="<?= htmlspecialchars($produtoEdicao['nome'] ?? '') ?>"
                required>
        </div>

        <div class="mb-3">
            <label class="form-label">Descrição:</label>
            <textarea name="descricao" class="form-control"><?= htmlspecialchars($produtoEdicao['descricao'] ?? '') ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Preço:</label>
            <input type="number" step="0.01" min="0" name="preco"
                class="form-control"
                value="<?= htmlspecialchars($produtoEdicao['preco'] ?? '') ?>"
                required>
        </div>

        <div class="mb-3">
            <label class="form-label">Quantidade em estoque:</label>
            <input type="number" min="0" name="quantidade"
                class="form-control"
                value="<?= htmlspecialchars($produtoEdicao['quantidade'] ?? '') ?>"
                required>
        </div>

        <div class="mb-3">
            <label class="form-label">Categoria:</label>
            <input type="text" name="categoria" class="form-control"
                value="<?= htmlspecialchars($produtoEdicao['categoria'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Endereço da imagem:</label>
            <input type="url" name="imagem" class="form-control"
                placeholder="Cole aqui o endereço da imagem"
                value="<?= htmlspecialchars($produtoEdicao['imagem'] ?? '') ?>">

            <small class="text-muted">
                Cole o link de uma imagem disponível na internet.
            </small>
        </div>

        <?php if (!empty($produtoEdicao["imagem"])): ?>
            <p>Imagem atual:</p>
            <img
                src="<?= htmlspecialchars($produtoEdicao["imagem"]) ?>"
                alt="Imagem atual do produto"
                style="width: 150px; height: 120px; object-fit: contain;"
            >
        <?php endif; ?>

        <br><br>

        <button type="submit" class="btn btn-primary">
            <?= $produtoEdicao ? "Salvar alterações" : "Cadastrar produto" ?>
        </button>

        <?php if ($produtoEdicao): ?>
            <a href="produtos.php" class="btn btn-secondary">Cancelar</a>
        <?php endif; ?>

    </form>

    <hr>

    <h2>Produtos cadastrados</h2>

    <?php

    $produtos = $conexao->query("SELECT * FROM produtos ORDER BY id DESC");

    while ($produto = $produtos->fetch(PDO::FETCH_ASSOC)):

    ?>

        <div class="card p-3 mb-3">

            <div class="d-flex gap-3 align-items-center flex-wrap">

                <?php if (!empty($produto["imagem"])): ?>

                    <img
                        src="<?= htmlspecialchars($produto["imagem"]) ?>"
                        alt="<?= htmlspecialchars($produto["nome"]) ?>"
                        style="width: 100px; height: 100px; object-fit: contain;"
                    >

                <?php else: ?>

                    <span>Sem imagem</span>

                <?php endif; ?>

                <div>

                    <strong><?= htmlspecialchars($produto["nome"]) ?></strong>

                    <p class="mb-1">
                        Preço: R$ <?= number_format($produto["preco"], 2, ",", ".") ?>
                    </p>

                    <p class="mb-1">
                        Estoque: <?= (int)$produto["quantidade"] ?> unidades
                    </p>

                    <p class="mb-1">
                        Categoria: <?= htmlspecialchars($produto["categoria"] ?? "") ?>
                    </p>

                    <a href="produtos.php?editar=<?= (int)$produto["id"] ?>">
                        Editar produto
                    </a>

                    <br>

                    <a
                        href="produtos.php?excluir=<?= (int)$produto["id"] ?>"
                        onclick="return confirm('Tem certeza que deseja excluir?')"
                    >
                        Excluir produto
                    </a>

                </div>

            </div>

        </div>

    <?php endwhile; ?>

</div>

</body>
</html>
