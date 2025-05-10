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

// Carregar todas as promoções ativas por produto
$sql_promocoes = "SELECT * FROM promocoes WHERE data_inicio <= NOW() AND data_fim >= NOW() AND ativa = 1";
$resultado_promocoes = $conexao->query($sql_promocoes);
$promocoes = [];
while ($row = $resultado_promocoes->fetch_assoc()) {
    if (!isset($promocoes[$row['produto_id']])) {
        $promocoes[$row['produto_id']] = [];
    }
    $promocoes[$row['produto_id']][] = [
        'id' => $row['id'],
        'tipo' => $row['tipo'],
        'valor' => $row['valor']
    ];
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
        $promocao_id = isset($item['promocaoId']) ? intval($item['promocaoId']) : null;

        // Verificar estoque
        $stmt = $conexao->prepare("SELECT preco, quantidade, nome_produto, estoque_minimo FROM produtos WHERE id = ?");
        $stmt->bind_param('i', $produto_id);
        $stmt->execute();
        $resultado_produto = $stmt->get_result();
        if ($produto = $resultado_produto->fetch_assoc()) {
            $estoque_atual = $produto['quantidade'];
            $estoque_minimo = $produto['estoque_minimo'];
            if ($quantidade_vendida <= $estoque_atual) {
                // Calcular preço com desconto promocional
                $preco_com_desconto = $preco_unitario;
                $promocao_aplicada = '';

                if ($promocao_id && isset($promocoes[$produto_id])) {
                    $promocao_selecionada = array_filter($promocoes[$produto_id], function ($p) use ($promocao_id) {
                        return $p['id'] == $promocao_id;
                    });
                    $promocao = array_values($promocao_selecionada)[0] ?? null;

                    if ($promocao) {
                        if ($promocao['tipo'] === 'percentual') {
                            $desconto_promocao = floatval($promocao['valor']);
                            $preco_com_desconto = $preco_unitario * (1 - $desconto_promocao / 100);
                            $promocao_aplicada = " com Desconto de {$desconto_promocao}%";
                        } elseif ($promocao['tipo'] === 'compre_por') {
                            if (preg_match('/(\d+)\s*por\s*(\d+)/i', $promocao['valor'], $matches)) {
                                $leve = intval($matches[1]);
                                $pague = intval($matches[2]);
                                if ($leve > $pague && $quantidade_vendida >= $leve) {
                                    $grupos = floor($quantidade_vendida / $leve);
                                    $unidades_restantes = $quantidade_vendida % $leve;
                                    $unidades_pagas = ($grupos * $pague) + $unidades_restantes;
                                    $preco_com_desconto = ($unidades_pagas / $quantidade_vendida) * $preco_unitario;
                                    $promocao_aplicada = " com Promoção Compre $leve por $pague";
                                }
                            }
                        }
                    }
                }

                // Calcular subtotal e aplicar desconto manual
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .calculator-section {
            margin-top: 2rem;
        }

        .card {
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .total-section {
            margin-top: 1.5rem;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 0.5rem;
        }

        .items-table {
            margin-top: 1rem;
        }

        .alert {
            margin-top: 1rem;
        }

        .promocao-select {
            margin-top: 0.5rem;
            display: none;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>
    <div class="container">
        <?php if (!empty($mensagem_venda)): ?>
            <div class="alert <?php echo strpos($mensagem_venda, 'Erro') !== false || strpos($mensagem_venda, 'Alerta') !== false ? 'alert-warning' : 'alert-success'; ?> alert-dismissible fade show" role="alert">
                <?php echo $mensagem_venda; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="calculator-section">
            <div class="card shadow-sm">
                <h3 class="mb-4">Adicionar Produtos</h3>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="produto_id" class="form-label">Produto:</label>
                        <select id="produto_id" class="form-select" onchange="atualizarPromocoes()">
                            <option value="">Selecione um produto</option>
                            <?php foreach ($produtos as $produto): ?>
                                <option value="<?php echo $produto['id']; ?>"
                                    data-preco="<?php echo $produto['preco']; ?>">
                                    <?php echo htmlspecialchars($produto['nome_produto']); ?> (R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select id="promocao_id" class="form-select promocao-select" onchange="atualizarPreco()">
                            <option value="">Sem promoção</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="quantidade" class="form-label">Quantidade:</label>
                        <input type="number" id="quantidade" class="form-control" min="1" value="1" onchange="atualizarPreco()">
                    </div>
                    <div class="col-md-3 align-self-end">
                        <button onclick="adicionarProduto()" class="btn btn-primary w-100">Adicionar Produto</button>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <h3 class="mb-4">Itens da Venda</h3>
                <table class="table table-striped items-table" id="itens_venda">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Quantidade</th>
                            <th>Preço Unitário</th>
                            <th>Desconto</th>
                            <th>Subtotal</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody id="itens_venda_body"></tbody>
                </table>
            </div>

            <div class="card shadow-sm total-section">
                <h4 class="mb-3">Resumo da Venda</h4>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label for="desconto_manual" class="form-label">Desconto Manual (%):</label>
                        <input type="number" id="desconto_manual" class="form-control" min="0" max="100" value="0" onchange="calcularTotal()">
                    </div>
                    <div class="col-md-6">
                        <p>Subtotal: R$ <span id="subtotal">0,00</span></p>
                        <p>Desconto Total: R$ <span id="desconto_total">0,00</span></p>
                        <p>Total Final: R$ <span id="total_final">0,00</span></p>
                    </div>
                </div>
            </div>

            <form id="form_venda" action="?" method="post">
                <input type="hidden" name="itens_venda" id="itens_venda_input">
                <input type="hidden" name="desconto_manual" id="desconto_manual_input">
                <div class="d-grid gap-2 mt-3">
                    <button type="submit" name="finalizar_venda" class="btn btn-success" onclick="prepararVenda()">Finalizar Venda</button>
                    <button type="button" onclick="limparVenda()" class="btn btn-danger">Limpar Venda</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const produtos = <?php echo json_encode($produtos); ?>;
        const promocoes = <?php echo json_encode($promocoes); ?>;
        let itensVenda = [];

        function atualizarPromocoes() {
            const produtoId = document.getElementById('produto_id').value;
            const promocaoSelect = document.getElementById('promocao_id');
            promocaoSelect.innerHTML = '<option value="">Sem promoção</option>';
            promocaoSelect.style.display = 'none';

            if (produtoId && promocoes[produtoId] && promocoes[produtoId].length > 0) {
                promocaoSelect.style.display = 'block';
                promocoes[produtoId].forEach(p => {
                    const option = document.createElement('option');
                    option.value = p.id;
                    option.text = `${p.tipo === 'percentual' ? p.valor + '%' : p.valor}`;
                    promocaoSelect.appendChild(option);
                });
            }
            atualizarPreco();
        }

        function atualizarPreco() {
            const produtoId = document.getElementById('produto_id').value;
            const quantidade = parseInt(document.getElementById('quantidade').value) || 1;
            const promocaoId = document.getElementById('promocao_id').value;
            const produto = produtos.find(p => p.id == produtoId);

            if (!produto) return;

            let precoUnitario = parseFloat(produto.preco);
            let precoComDesconto = precoUnitario;
            let descontoPromocao = 0;

            if (promocaoId && promocoes[produtoId]) {
                const promocao = promocoes[produtoId].find(p => p.id == promocaoId);
                if (promocao) {
                    if (promocao.tipo === 'percentual') {
                        descontoPromocao = parseFloat(promocao.valor);
                        precoComDesconto = precoUnitario * (1 - descontoPromocao / 100);
                    } else if (promocao.tipo === 'compre_por') {
                        const matches = promocao.valor.match(/(\d+)\s*por\s*(\d+)/i);
                        if (matches && quantidade >= parseInt(matches[1])) {
                            const leve = parseInt(matches[1]);
                            const pague = parseInt(matches[2]);
                            const grupos = Math.floor(quantidade / leve);
                            const unidadesRestantes = quantidade % leve;
                            const unidadesPagas = (grupos * pague) + unidadesRestantes;
                            precoComDesconto = (unidadesPagas / quantidade) * precoUnitario;
                        }
                    }
                }
            }

            // Atualizar visualização (opcional, se quiser mostrar o preço com desconto antes de adicionar)
        }

        function adicionarProduto() {
            const produtoId = document.getElementById('produto_id').value;
            const quantidade = parseInt(document.getElementById('quantidade').value);
            const promocaoId = document.getElementById('promocao_id').value;

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

            let precoUnitario = parseFloat(produto.preco);
            let precoComDesconto = precoUnitario;
            let descontoPromocao = 0;
            let promocaoDescricao = '';

            if (promocaoId && promocoes[produtoId]) {
                const promocao = promocoes[produtoId].find(p => p.id == promocaoId);
                if (promocao) {
                    if (promocao.tipo === 'percentual') {
                        descontoPromocao = parseFloat(promocao.valor);
                        precoComDesconto = precoUnitario * (1 - descontoPromocao / 100);
                        promocaoDescricao = `${descontoPromocao}%`;
                    } else if (promocao.tipo === 'compre_por') {
                        const matches = promocao.valor.match(/(\d+)\s*por\s*(\d+)/i);
                        if (matches && quantidade >= parseInt(matches[1])) {
                            const leve = parseInt(matches[1]);
                            const pague = parseInt(matches[2]);
                            const grupos = Math.floor(quantidade / leve);
                            const unidadesRestantes = quantidade % leve;
                            const unidadesPagas = (grupos * pague) + unidadesRestantes;
                            precoComDesconto = (unidadesPagas / quantidade) * precoUnitario;
                            promocaoDescricao = `${promocao.valor}`;
                        }
                    }
                }
            }

            const subtotal = precoComDesconto * quantidade;

            itensVenda.push({
                id: produtoId,
                nome: produto.nome_produto,
                quantidade: quantidade,
                precoUnitario: precoUnitario,
                promocaoId: promocaoId,
                descontoPromocao: promocaoDescricao,
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
                    <td>${item.descontoPromocao}</td>
                    <td>R$ ${item.subtotal.toFixed(2).replace('.', ',')}</td>
                    <td><button class="btn btn-danger btn-sm" onclick="removerProduto(${index})">Remover</button></td>
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
            document.getElementById('promocao_id').value = '';
            document.getElementById('promocao_id').style.display = 'none';
            atualizarTabela();
            calcularTotal();
        }

        function prepararVenda() {
            document.getElementById('itens_venda_input').value = JSON.stringify(itensVenda);
            document.getElementById('desconto_manual_input').value = document.getElementById('desconto_manual').value;
        }

        // Inicializar promoções ao carregar
        document.addEventListener('DOMContentLoaded', function() {
            atualizarPromocoes();
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>