<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: login.html");
    exit();
}
require_once 'conexao.php';

$mensagem_venda = '';

// Carregar produtos
$sql_produtos = "SELECT id, nome_produto, preco, quantidade, estoque_minimo FROM produtos WHERE quantidade > 0";
$resultado_produtos = $conexao->query($sql_produtos);
$produtos = $resultado_produtos->fetch_all(MYSQLI_ASSOC);

// Carregar promoções ativas
$sql_promocoes = "SELECT * FROM promocoes WHERE data_inicio <= NOW() AND data_fim >= NOW() AND ativa = 1 AND tipo = 'percentual'";
$resultado_promocoes = $conexao->query($sql_promocoes);
$promocoes = [];
while ($row = $resultado_promocoes->fetch_assoc()) {
    $promocoes[$row['produto_id']] = $row['valor'];
}

// Registrar vendas
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['finalizar_venda'])) {
    $itens_venda = json_decode($_POST['itens_venda'], true);
    $desconto_manual = floatval($_POST['desconto_manual']);
    $lucro_total = 0;
    $desconto_total = 0;
    $erro = false;
    $log_acoes = [];

    foreach ($itens_venda as $item) {
        $produto_id = intval($item['id']);
        $quantidade_vendida = intval($item['quantidade']);
        $preco_unitario = floatval($item['precoUnitario']);
        $desconto_promocao = floatval($item['descontoPromocao']);

        // Verificar estoque
        $stmt = $conexao->prepare("SELECT preco, quantidade, nome_produto, estoque_minimo FROM produtos WHERE id = ?");
        $stmt->bind_param('i', $produto_id);
        $stmt->execute();
        $resultado_produto = $stmt->get_result();
        if ($produto = $resultado_produto->fetch_assoc()) {
            $estoque_atual = $produto['quantidade'];
            $estoque_minimo = $produto['estoque_minimo'];
            if ($quantidade_vendida <= $estoque_atual) {
                // Calcular preço com desconto (promoção + manual)
                $preco_com_desconto = $preco_unitario * (1 - $desconto_promocao / 100);
                $subtotal = $preco_com_desconto * $quantidade_vendida;
                $desconto_manual_valor = $subtotal * ($desconto_manual / 100);
                $preco_final = $preco_com_desconto * (1 - $desconto_manual / 100);
                $desconto_total += ($preco_com_desconto - $preco_final) * $quantidade_vendida;

                // Registrar a venda
                $stmt_venda = $conexao->prepare("INSERT INTO vendas (produto_id, quantidade_vendida, preco_unitario_venda) VALUES (?, ?, ?)");
                $stmt_venda->bind_param('iid', $produto_id, $quantidade_vendida, $preco_final);
                if ($stmt_venda->execute()) {
                    $novo_estoque = $estoque_atual - $quantidade_vendida;
                    $stmt_update = $conexao->prepare("UPDATE produtos SET quantidade = ? WHERE id = ?");
                    $stmt_update->bind_param('ii', $novo_estoque, $produto_id);
                    if ($stmt_update->execute()) {
                        $lucro_total += $preco_final * $quantidade_vendida;
                        $promocao_aplicada = $desconto_promocao > 0 ? " com Desconto de {$desconto_promocao}%" : '';
                        $log_acoes[] = "Registrou venda de $quantidade_vendida unidade(s) de '{$produto['nome_produto']}'" . $promocao_aplicada;
                        if ($novo_estoque < $estoque_minimo) {
                            $mensagem_venda .= "Alerta: Estoque de " . htmlspecialchars($produto['nome_produto']) . " está abaixo do mínimo ($novo_estoque < $estoque_minimo)! ";
                        }
                    } else {
                        $mensagem_venda = "Erro ao atualizar o estoque do produto ID $produto_id: " . $conexao->error;
                        $erro = true;
                        $stmt_update->close();
                        break;
                    }
                    $stmt_update->close();
                } else {
                    $mensagem_venda = "Erro ao registrar a venda do produto ID $produto_id: " . $conexao->error;
                    $erro = true;
                    $stmt_venda->close();
                    break;
                }
                $stmt_venda->close();
            } else {
                $mensagem_venda = "Estoque insuficiente para o produto: " . htmlspecialchars($produto['nome_produto']);
                $erro = true;
                break;
            }
        }
        $stmt->close();
    }

    if (!$erro && $lucro_total > 0) {
        $mensagem_venda .= "Venda registrada com sucesso. Lucro total: R$ " . number_format($lucro_total, 2, ',', '.') . ($desconto_total > 0 ? " (Desconto aplicado: R$ " . number_format($desconto_total, 2, ',', '.') . ")" : "");
        // Registrar logs
        $usuario_id = $_SESSION['usuario_id'];
        foreach ($log_acoes as $acao) {
            $stmt_log = $conexao->prepare("INSERT INTO logs (usuario_id, acao) VALUES (?, ?)");
            $stmt_log->bind_param("is", $usuario_id, $acao);
            $stmt_log->execute();
            $stmt_log->close();
        }
    } elseif (!$erro) {
        $mensagem_venda = "Nenhum produto adicionado para venda.";
    }
}

$conexao->close();
?>


<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Venda - Gestão Panificadora</title>
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
        <h2>Registrar Venda</h2>
        <?php if (!empty($mensagem_venda)): ?>
            <p><?php echo $mensagem_venda; ?></p>
        <?php endif; ?>

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

            <form id="form_venda" action="registrar_venda.php" method="post">
                <input type="hidden" name="itens_venda" id="itens_venda_input">
                <input type="hidden" name="desconto_manual" id="desconto_manual_input">
                <button type="submit" name="finalizar_venda" onclick="prepararVenda()">Finalizar Venda</button>
            </form>
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

            if (quantidade > produto.quantidade) {
                alert(`Estoque insuficiente para ${produto.nome_produto}. Quantidade disponível: ${produto.quantidade}`);
                return;
            }

            const descontoPromocao = promocoes[produtoId] || 0;
            const precoUnitario = parseFloat(produto.preco);
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

        function prepararVenda() {
            document.getElementById('itens_venda_input').value = JSON.stringify(itensVenda);
            document.getElementById('desconto_manual_input').value = document.getElementById('desconto_manual').value;
        }
    </script>
</body>
</html>