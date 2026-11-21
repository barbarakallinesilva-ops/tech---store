
// NOSSO CARRINHO DE COMPRAS

let carrinho = [];

// Encontrar os produtos da loja
let produtos = document.querySelectorAll(".card");

// Fazer os botoes Comprar funcionarem
produtos.forEach(function(produto) {

    let botao = produto.querySelector("button");

    if (!botao) return;

    botao.addEventListener("click", function() {

        let nome = produto.querySelector("h3").textContent;

        let precoTexto = produto.innerText.match(/R\$\s*[\d.,]+/);

        if (!precoTexto) {
            alert("Preco nao encontrado!");
            return;
        }

        let preco = Number(
            precoTexto[0]
                .replace("R$", "")
                .trim()
                .replace(/\./g, "")
                .replace(",", ".")
        );

        let produtoExistente = carrinho.find(function(item) {
            return item.nome === nome;
        });

        if (produtoExistente) {
            produtoExistente.quantidade++;
        } else {
            carrinho.push({
                nome: nome,
                preco: preco,
                quantidade: 1
            });
        }

        atualizarCarrinho();

        alert(nome + " foi adicionado ao carrinho!");

    });

});


// ATUALIZAR O CARRINHO

function atualizarCarrinho() {

    let lista = document.getElementById("listaCarrinho");
    let totalElemento = document.getElementById("totalCarrinho");

    lista.innerHTML = "";

    let total = 0;

    if (carrinho.length === 0) {
        lista.innerHTML = "<li>Seu carrinho está vazio.</li>";
    }

    carrinho.forEach(function(item, indice) {

        let subtotal = item.preco * item.quantidade;

        total += subtotal;

        let linha = document.createElement("li");

        linha.textContent =
            item.nome +
            " | Quantidade: " + item.quantidade +
            " | Subtotal: R$ " +
            subtotal.toFixed(2).replace(".", ",");

        let botaoRemover = document.createElement("button");

        botaoRemover.textContent = "Remover";

        botaoRemover.addEventListener("click", function() {

            carrinho.splice(indice, 1);

            atualizarCarrinho();

        });

        linha.appendChild(botaoRemover);

        lista.appendChild(linha);

    });

    totalElemento.textContent =
        "Total: R$ " + total.toFixed(2).replace(".", ",");

}


// FILTRAR PRODUTOS POR CATEGORIA

function filtrarCategoria(categoria) {

    produtos.forEach(function(produto) {

        let categoriaProduto = produto.dataset.categoria;

        let mostrar = categoria === "todos" ||
                      categoriaProduto === categoria;

        let coluna = produto.closest(".col-md-4") || produto;

        coluna.style.display = mostrar ? "" : "none";

    });

}
