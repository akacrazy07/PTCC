<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: login.html");
    exit();
}
require_once 'conexao.php';

// Carregar produtos
$sql_produtos = "SELECT id, nome_produto, preco FROM produtos"; // Ajustado para 'preco'
$resultado_produtos = $conexao->query($sql_produtos);
$produtos = $resultado_produtos->fetch_all(MYSQLI_ASSOC);

// Carregar promoções ativas
$sql_promocoes = "SELECT * FROM promocoes WHERE data_inicio <= NOW() AND data_fim >= NOW() AND ativa = 1";
$resultado_promocoes = $conexao->query($sql_promocoes);
$promocoes = [];
while ($row = $resultado_promocoes->fetch_assoc()) {
    $promocoes[$row['produto_id']] = $row['desconto_percentual'];
}

$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calculadora de Vendas - Panificadora</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Gestão de Estoque - Panificadora</h1>
        <nav>
            <a href="controle_estoque.php">Dashboard</a>
            <?php if (in_array($_SESSION['perfil'], ['admin', 'gerente'])): ?>
                <a href="adicionar_produto.php">Adicionar Produto</a>
                <a href="planejamento_producao.php">Planejamento de Produção</a>
            <?php endif; ?>
            <a href="registrar_venda.php">Registrar Venda</a>
            <a href="listar_produtos.php">Listar Produtos</a>
            <a href="calculadora_vendas.php">Calculadora de Vendas</a>
            <?php if (in_array($_SESSION['perfil'], ['admin', 'gerente'])): ?>
                <a href="relatorios.php">Relatórios</a>
                <a href="receitas.php">Receitas</a>
                <a href="desperdicio.php">Desperdício</a>
                <a href="gerenciar_promocoes.php">Gerenciar Promoções</a>
                <a href="gerenciar_fornecedores.php">Gerenciar Fornecedores</a>
                <a href="exportar_dados.php">Exportar Dados</a>
                <a href="pesquisa_avancada.php">Pesquisa Avançada</a>
            <?php endif; ?>
            <?php if ($_SESSION['perfil'] === 'admin'): ?>
                <a href="gerenciar_usuarios.php">Gerenciar Usuários</a>
                <a href="ver_logs.php">Ver Logs</a>
                <a href="gerenciar_backups.php">Gerenciar Backups</a>
            <?php endif; ?>
            <a href="logout.php">Sair</a>
        </nav>
    </header>
    <div class="container">
        <h2>Calculadora de Vendas</h2>

        <div class="calculator-section">
            <h3>Adicionar Produtos</h3>
            <div>
                <label for="produto_id">Produto:</label>
                <select id="produto_id">
                    <option value="">Selecione um produto</option>
                    <?php foreach ($produtos as $produto): ?>
                        <option value="<?php echo $produto['id']; ?>" 
                                data-preco="<?php echo $produto['preco']; ?>" 
                                data-promocao="<?php echo isset($promocoes[$produto['id']]) ? $promocoes[$produto['id']] : 0; ?>">
                            <?php echo htmlspecialchars($produto['nome_produto']); ?> (R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="quantidade">Quantidade:</label>
                <input type="number" id="quantidade" min="1" value="1">

                <button onclick="adicionarProduto()">Adicionar Produto</button>
            </div>

            <h3>Itens da Venda</h3>
            <table class="items-table" id="itens_venda">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Quantidade</th>
                        <th>Preço Unitário</th>
                        <th>Desconto (%)</th>
                        <th>Subtotal</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody id="itens_venda_body"></tbody>
            </table>

            <div class="total-section">
                <label for="desconto_manual">Desconto Manual (%):</label>
                <input type="number" id="desconto_manual" min="0" max="100" value="0" onchange="calcularTotal()">
                <p>Subtotal: R$ <span id="subtotal">0,00</span></p>
                <p>Desconto Total: R$ <span id="desconto_total">0,00</span></p>
                <p>Total Final: R$ <span id="total_final">0,00</span></p>
            </div>

            <button onclick="limparVenda()">Limpar Venda</button>
        </div>
    </div>

    <script>
        const produtos = <?php echo json_encode($produtos); ?>;
        const promocoes = <?php echo json_encode($promocoes); ?>;
        let itensVenda = [];

        function adicionarProduto() {
            const produtoId = document.getElementById('produto_id').value;
            const quantidade = parseInt(document.getElementById('quantidade').value);

            if (!produtoId || quantidade < 1) {
                alert('Por favor, selecione um produto e informe uma quantidade válida.');
                return;
            }

            const produto = produtos.find(p => p.id == produtoId);
            if (!produto) return;

            const descontoPromocao = promocoes[produtoId] || 0;
            const precoUnitario = parseFloat(produto.preco); // Ajustado para 'preco'
            const precoComDesconto = precoUnitario * (1 - descontoPromocao / 100);
            const subtotal = precoComDesconto * quantidade;

            itensVenda.push({
                id: produtoId,
                nome: produto.nome_produto,
                quantidade: quantidade,
                precoUnitario: precoUnitario,
                descontoPromocao: descontoPromocao,
                subtotal: subtotal
            });

            atualizarTabela();
            calcularTotal();
        }

        function removerProduto(index) {
            itensVenda.splice(index, 1);
            atualizarTabela();
            calcularTotal();
        }

        function atualizarTabela() {
            const tbody = document.getElementById('itens_venda_body');
            tbody.innerHTML = '';

            itensVenda.forEach((item, index) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${item.nome}</td>
                    <td>${item.quantidade}</td>
                    <td>R$ ${item.precoUnitario.toFixed(2).replace('.', ',')}</td>
                    <td>${item.descontoPromocao}%</td>
                    <td>R$ ${item.subtotal.toFixed(2).replace('.', ',')}</td>
                    <td><button onclick="removerProduto(${index})">Remover</button></td>
                `;
                tbody.appendChild(tr);
            });
        }

        function calcularTotal() {
            let subtotal = itensVenda.reduce((sum, item) => sum + item.subtotal, 0);
            const descontoManual = parseFloat(document.getElementById('desconto_manual').value) || 0;
            const descontoValor = subtotal * (descontoManual / 100);
            const totalFinal = subtotal - descontoValor;

            document.getElementById('subtotal').textContent = subtotal.toFixed(2).replace('.', ',');
            document.getElementById('desconto_total').textContent = descontoValor.toFixed(2).replace('.', ',');
            document.getElementById('total_final').textContent = totalFinal.toFixed(2).replace('.', ',');
        }

        function limparVenda() {
            itensVenda = [];
            document.getElementById('desconto_manual').value = '0';
            atualizarTabela();
            calcularTotal();
        }
    </script>
</body>
</html>