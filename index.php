
<?php

require_once __DIR__ . "/includes/sessao.php";
require_once __DIR__ . "/config/conexao.php";

$usuario = $_SESSION["usuario"] ?? null;

$sql = "SELECT id, nome, descricao, preco, quantidade, categoria, imagem
        FROM produtos
        ORDER BY id DESC";

$consulta = $conexao->prepare($sql);
$consulta->execute();

$produtos = $consulta->fetchAll(PDO::FETCH_ASSOC);

function normalizarCategoria($categoria) {

    $categoria = mb_strtolower(trim($categoria ?? ""), "UTF-8");

    $categoria = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $categoria);

    return strtolower(trim($categoria));
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>TECH STORE</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="estilos.css">
</head>

<body>

<header class="container-fluid bg-dark text-white p-3">

    <div class="container d-flex justify-content-between align-items-center flex-wrap">

        <h1>TECH STORE</h1>

        <nav class="d-flex gap-3 align-items-center flex-wrap">

            <a href="index.php" class="text-white">Início</a>

            <a href="carrinho.php" class="text-white">Carrinho</a>

            <?php if ($usuario): ?>

                <span>Olá, <?= htmlspecialchars($usuario["nome"]) ?>!</span>

                <?php if ($usuario["tipo"] === "admin"): ?>
                    <a href="admin/produtos.php" class="text-white">Área administrativa</a>
                <?php endif; ?>

                <a href="sair.php" class="text-white">Sair</a>

            <?php else: ?>

                <a href="login.php" class="text-white">Entrar</a>

                <a href="cadastro.php" class="text-white">Cadastrar</a>

            <?php endif; ?>

        </nav>

    </div>

</header>

<main class="container mt-4">

    <section class="text-center mb-4">

        <h2>Bem-vindo à TECH STORE!</h2>

        <p>Encontre os melhores produtos de tecnologia.</p>

    </section>

    <section class="mb-4">

        <input
            type="text"
            id="pesquisa"
            class="form-control"
            placeholder="Pesquisar produtos..."
        >

    </section>

    <section class="mb-4 d-flex gap-2 flex-wrap">

        <button type="button" class="btn btn-dark" onclick="filtrarCategoria('todos')">
            Todos
        </button>

        <button type="button" class="btn btn-secondary" onclick="filtrarCategoria('audio')">
            Áudio
        </button>

        <button type="button" class="btn btn-secondary" onclick="filtrarCategoria('games')">
            Games
        </button>

        <button type="button" class="btn btn-secondary" onclick="filtrarCategoria('acessorios')">
            Acessórios
        </button>

        <button type="button" class="btn btn-secondary" onclick="filtrarCategoria('celulares')">
            Celulares
        </button>

    </section>

    <section class="row g-4" id="listaProdutos">

        <?php foreach ($produtos as $produto): ?>

            <div
                class="col-md-4 produto-item"
                data-categoria="<?= htmlspecialchars(normalizarCategoria($produto["categoria"]), ENT_QUOTES, "UTF-8") ?>"
            >

                <div class="card h-100">

                    <?php if (!empty($produto["imagem"])): ?>

                        <a href="produto.php?id=<?= (int)$produto["id"] ?>">

                            <img
                                src="<?= htmlspecialchars($produto["imagem"]) ?>"
                                class="card-img-top"
                                alt="<?= htmlspecialchars($produto["nome"]) ?>"
                                style="height: 220px; object-fit: contain;"
                            >

                        </a>

                    <?php else: ?>

                        <a
                            href="produto.php?id=<?= (int)$produto["id"] ?>"
                            class="text-decoration-none text-dark"
                        >

                            <div class="text-center p-5 bg-light">
                                Imagem não disponível
                            </div>

                        </a>

                    <?php endif; ?>

                    <div class="card-body">

                        <h5 class="card-title">

                            <a
                                href="produto.php?id=<?= (int)$produto["id"] ?>"
                                class="text-decoration-none text-dark"
                            >
                                <?= htmlspecialchars($produto["nome"]) ?>
                            </a>

                        </h5>

                        <p class="card-text">
                            <?= htmlspecialchars($produto["descricao"] ?? "") ?>
                        </p>

                        <p class="fw-bold">
                            R$ <?= number_format($produto["preco"], 2, ",", ".") ?>
                        </p>

                        <p>
                            Estoque: <?= (int)$produto["quantidade"] ?>
                        </p>

                        <a
                            href="produto.php?id=<?= (int)$produto["id"] ?>"
                            class="btn btn-outline-dark w-100 mb-2"
                        >
                            Ver detalhes
                        </a>

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
                                    class="btn btn-primary w-100"
                                >
                                    Comprar
                                </button>

                            </form>

                        <?php else: ?>

                            <button class="btn btn-secondary w-100" disabled>
                                Produto indisponível
                            </button>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        <?php endforeach; ?>

        <?php if (empty($produtos)): ?>

            <div class="col-12">

                <div class="alert alert-info">
                    Nenhum produto cadastrado no momento.
                </div>

            </div>

        <?php endif; ?>

    </section>

</main>

<footer class="container-fluid bg-dark text-white text-center p-3 mt-5">

    <p>TECH STORE - Sua loja de tecnologia</p>

</footer>

<script>

function normalizarTexto(texto) {

    return texto
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .trim()
        .toLowerCase();

}

function filtrarCategoria(categoria) {

    const produtos = document.querySelectorAll(".produto-item");

    produtos.forEach(function(produto) {

        const categoriaProduto = normalizarTexto(
            produto.dataset.categoria || ""
        );

        const categoriaEscolhida = normalizarTexto(categoria);

        if (
            categoriaEscolhida === "todos" ||
            categoriaProduto === categoriaEscolhida
        ) {

            produto.style.display = "";

        } else {

            produto.style.display = "none";

        }

    });

}

function pesquisarProdutos() {

    const pesquisa = normalizarTexto(
        document.getElementById("pesquisa").value
    );

    const produtos = document.querySelectorAll(".produto-item");

    produtos.forEach(function(produto) {

        const nome = normalizarTexto(
            produto.querySelector(".card-title").textContent
        );

        produto.style.display = nome.includes(pesquisa) ? "" : "none";

    });

}

document
    .getElementById("pesquisa")
    .addEventListener("input", pesquisarProdutos);

</script>

</body>
</html>
