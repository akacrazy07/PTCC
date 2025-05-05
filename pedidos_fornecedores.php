<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true || !in_array($_SESSION['perfil'], ['admin', 'gerente'])) {
    header("Location: login.html");
    exit();
}
require_once 'conexao.php';
require_once 'funcoes.php';
$mensagem = '';

// Listar fornecedores
$sql_fornecedores = "SELECT id, nome FROM fornecedores";
$resultado_fornecedores = $conexao->query($sql_fornecedores);
$fornecedores = [];
while ($row = $resultado_fornecedores->fetch_assoc()) {
    $fornecedores[] = $row;
}

// Listar categorias para o formulário
$sql_categorias = "SELECT id, nome FROM categorias";
$resultado_categorias = $conexao->query($sql_categorias);
$categorias = [];
while ($row = $resultado_categorias->fetch_assoc()) {
    $categorias[] = $row;
}

// Função para automação de pedidos (passo 1: listar produtos com estoque baixo)
$produtos_baixo_estoque = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verificar_estoque'])) {
    $sql_estoque = "SELECT p.id, p.nome_produto, p.quantidade, p.estoque_minimo 
                    FROM produtos p 
                    WHERE p.quantidade < p.estoque_minimo";
    $resultado_estoque = $conexao->query($sql_estoque);
    while ($row = $resultado_estoque->fetch_assoc()) {
        $sql_fornecedor = "SELECT pf.fornecedor_id, pf.preco_unitario, f.nome AS nome_fornecedor, pf.data_validade, pf.categoria_id, c.nome AS categoria_nome 
                           FROM produtos_fornecedores pf 
                           JOIN fornecedores f ON pf.fornecedor_id = f.id 
                           LEFT JOIN categorias c ON pf.categoria_id = c.id 
                           WHERE pf.nome_produto = ? LIMIT 1";
        $stmt_fornecedor = $conexao->prepare($sql_fornecedor);
        $stmt_fornecedor->bind_param("s", $row['nome_produto']);
        $stmt_fornecedor->execute();
        $resultado_fornecedor = $stmt_fornecedor->get_result();
        $fornecedor = $resultado_fornecedor->fetch_assoc();
        $stmt_fornecedor->close();

        if ($fornecedor) {
            $row['fornecedor_id'] = $fornecedor['fornecedor_id'];
            $row['nome_fornecedor'] = $fornecedor['nome_fornecedor'];
            $row['preco_unitario'] = $fornecedor['preco_unitario'];
            $row['data_validade'] = $fornecedor['data_validade'];
            $row['categoria_id'] = $fornecedor['categoria_id'];
            $row['categoria_nome'] = $fornecedor['categoria_nome'] ?? 'Sem categoria';
            $produtos_baixo_estoque[] = $row;
        }
    }

    if (empty($produtos_baixo_estoque)) {
        $mensagem = "Nenhum produto com estoque baixo para automatizar pedidos.";
    }
}

// Função para confirmar pedidos automáticos (passo 2: após inserir quantidades)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_automatizacao'])) {
    $produtos = $_POST['produtos'] ?? [];
    $conexao->begin_transaction();
    try {
        $pedidos_criados = 0;
        foreach ($produtos as $produto_id => $dados) {
            $quantidade = intval($dados['quantidade']);
            $fornecedor_id = intval($dados['fornecedor_id']);
            $nome_produto = $dados['nome_produto'];
            $preco_unitario = floatval($dados['preco_unitario']);
            $data_validade = !empty($dados['data_validade']) ? $dados['data_validade'] : null;
            $categoria_id = !empty($dados['categoria_id']) ? intval($dados['categoria_id']) : null;

            if ($quantidade <= 0) continue; // Pular se quantidade for inválida

            // Verificar se já existe um pedido pendente
            $sql_check = "SELECT id FROM pedidos_fornecedores 
                          WHERE fornecedor_id = ? AND nome_produto = ? AND status = 'pendente'";
            $stmt_check = $conexao->prepare($sql_check);
            $stmt_check->bind_param("is", $fornecedor_id, $nome_produto);
            $stmt_check->execute();
            $resultado_check = $stmt_check->get_result();
            $pedido_existente = $resultado_check->fetch_assoc();
            $stmt_check->close();

            if (!$pedido_existente) {
                $imposto_distrital = 0.00;
                $imposto_nacional = 0.00;
                $taxa_entrega = 0.00;
                $outras_taxas = 0.00;

                $sql_pedido = "INSERT INTO pedidos_fornecedores 
                               (fornecedor_id, nome_produto, quantidade, preco_unitario, imposto_distrital, imposto_nacional, taxa_entrega, outras_taxas, data_validade, categoria_id) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_pedido = $conexao->prepare($sql_pedido);
                $stmt_pedido->bind_param("isiddidssi", $fornecedor_id, $nome_produto, $quantidade, $preco_unitario, $imposto_distrital, $imposto_nacional, $taxa_entrega, $outras_taxas, $data_validade, $categoria_id);
                $stmt_pedido->execute();
                $stmt_pedido->close();
                $pedidos_criados++;
            }
        }

        $conexao->commit();
        $mensagem = $pedidos_criados > 0 ? "Automatização concluída! $pedidos_criados pedido(s) criado(s)." : "Nenhum pedido novo criado (produtos ignorados ou já têm pedidos pendentes).";
    } catch (Exception $e) {
        $conexao->rollback();
        $mensagem = "Erro ao automatizar pedidos: " . $e->getMessage();
    }
}

// Fazer pedido a fornecedor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fazer_pedido'])) {
    $fornecedor_id = $_POST['fornecedor_id'];
    $produtos = $_POST['produtos'] ?? [];

    if (empty($fornecedor_id) || empty($produtos)) {
        $mensagem = "Erro: Selecione um fornecedor e adicione pelo menos um produto!";
    } else {
        $conexao->begin_transaction();
        try {
            foreach ($produtos as $produto) {
                $nome_produto = $produto['nome_produto'];
                $quantidade = intval($produto['quantidade']);
                $preco_unitario = floatval($produto['preco_unitario']);
                $imposto_distrital = floatval($produto['imposto_distrital']);
                $imposto_nacional = floatval($produto['imposto_nacional']);
                $taxa_entrega = floatval($produto['taxa_entrega']);
                $outras_taxas = floatval($produto['outras_taxas']);
                $data_validade = !empty($produto['data_validade']) ? $produto['data_validade'] : null;
                $categoria_id = !empty($produto['categoria_id']) ? intval($produto['categoria_id']) : null;

                if (empty($nome_produto) || $quantidade <= 0 || $preco_unitario <= 0) {
                    throw new Exception("Erro: Preencha todos os campos obrigatórios corretamente!");
                }

                $sql = "INSERT INTO pedidos_fornecedores 
                        (fornecedor_id, nome_produto, quantidade, preco_unitario, imposto_distrital, imposto_nacional, taxa_entrega, outras_taxas, data_validade, categoria_id) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conexao->prepare($sql);
                $stmt->bind_param("isiddidssi", $fornecedor_id, $nome_produto, $quantidade, $preco_unitario, $imposto_distrital, $imposto_nacional, $taxa_entrega, $outras_taxas, $data_validade, $categoria_id);
                $stmt->execute();
                $stmt->close();
            }
            $conexao->commit();
            $mensagem = "Pedido(s) realizado(s) com sucesso!";
            $acao = "realizou um pedido a fornecedor: $fornecedor_id";
            registrarLog($conexao, $_SESSION['usuario_id'], $acao);
        } catch (Exception $e) {
            $conexao->rollback();
            $mensagem = "Erro ao realizar pedido: " . $e->getMessage();
        }
    }
}

// Limpar tabela se ultrapassar 30 pedidos
$sql_count = "SELECT COUNT(*) as total FROM pedidos_fornecedores";
$resultado_count = $conexao->query($sql_count);
$total_pedidos = $resultado_count->fetch_assoc()['total'];
if ($total_pedidos > 30) {
    $sql_clear = "TRUNCATE TABLE pedidos_fornecedores";
    $conexao->query($sql_clear);
    $mensagem = "Tabela de pedidos foi limpa automaticamente (limite de 30 pedidos excedido).";
}

// Listar pedidos pendentes e confirmados
$sql_pedidos = "SELECT pf.*, f.nome AS nome_fornecedor, c.nome AS categoria_nome 
                FROM pedidos_fornecedores pf 
                JOIN fornecedores f ON pf.fornecedor_id = f.id 
                LEFT JOIN categorias c ON pf.categoria_id = c.id 
                ORDER BY pf.criado_em DESC";
$resultado_pedidos = $conexao->query($sql_pedidos);
$pedidos = [];
while ($row = $resultado_pedidos->fetch_assoc()) {
    $pedidos[] = $row;
}

$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos a Fornecedores - Panificadora</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fornecedorSelect = document.querySelector('select[name="fornecedor_id"]');
            fornecedorSelect.addEventListener('change', function() {
                if (this.value) {
                    if (confirm('Deseja carregar os produtos associados a este fornecedor?')) {
                        fetch('produtos_por_fornecedor.php?fornecedor_id=' + this.value)
                            .then(response => response.json())
                            .then(data => {
                                const container = document.getElementById('produtos-container');
                                container.innerHTML = ''; // Limpa produtos existentes
                                let produtoCount = 0;
                                data.forEach(produto => {
                                    const newRow = document.createElement('div');
                                    newRow.className = 'produto-row mb-3';
                                    newRow.innerHTML = `
                                        <div class="row">
                                            <div class="col-3">
                                                <label class="form-label">Nome do Produto:</label>
                                                <input type="text" class="form-control" name="produtos[${produtoCount}][nome_produto]" value="${produto.nome_produto}" readonly>
                                            </div>
                                            <div class="col-2">
                                                <label class="form-label">Quantidade:</label>
                                                <input type="number" class="form-control" name="produtos[${produtoCount}][quantidade]" required min="1">
                                            </div>
                                            <div class="col-2">
                                                <label class="form-label">Preço Unitário:</label>
                                                <input type="number" class="form-control" name="produtos[${produtoCount}][preco_unitario]" value="${produto.preco_unitario}" step="0.01" required min="0">
                                            </div>
                                            <div class="col-2">
                                                <label class="form-label">Imposto Distrital:</label>
                                                <input type="number" class="form-control" name="produtos[${produtoCount}][imposto_distrital]" step="0.01" min="0" value="0">
                                            </div>
                                            <div class="col-2">
                                                <label class="form-label">Imposto Nacional:</label>
                                                <input type="number" class="form-control" name="produtos[${produtoCount}][imposto_nacional]" step="0.01" min="0" value="0">
                                            </div>
                                            <div class="col-1 d-flex align-items-end">
                                                <button type="button" class="btn btn-danger btn-sm remove-produto">Remover</button>
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-2">
                                                <label class="form-label">Taxa de Entrega:</label>
                                                <input type="number" class="form-control" name="produtos[${produtoCount}][taxa_entrega]" step="0.01" min="0" value="0">
                                            </div>
                                            <div class="col-2">
                                                <label class="form-label">Outras Taxas:</label>
                                                <input type="number" class="form-control" name="produtos[${produtoCount}][outras_taxas]" step="0.01" min="0" value="0">
                                            </div>
                                            <div class="col-3">
                                                <label class="form-label">Data de Validade:</label>
                                                <input type="date" class="form-control" name="produtos[${produtoCount}][data_validade]" value="${produto.data_validade || ''}">
                                            </div>
                                            <div class="col-3">
                                                <label class="form-label">Categoria:</label>
                                                <input type="text" class="form-control" value="${produto.categoria_nome || 'Sem categoria'}" readonly>
                                                <input type="hidden" name="produtos[${produtoCount}][categoria_id]" value="${produto.categoria_id || ''}">
                                            </div>
                                        </div>
                                    `;
                                    container.appendChild(newRow);
                                    produtoCount++;
                                });
                            })
                            .catch(error => console.error('Erro ao carregar produtos:', error));
                    }
                }
            });

            const container = document.getElementById('produtos-container');
            let produtoCount = 0;

            document.getElementById('addProduto').addEventListener('click', function() {
                const newRow = document.createElement('div');
                newRow.className = 'produto-row mb-3';
                newRow.innerHTML = `
                    <div class="row">
                        <div class="col-3">
                            <label class="form-label">Nome do Produto:</label>
                            <input type="text" class="form-control" name="produtos[${produtoCount}][nome_produto]" required placeholder="Ex.: Farinha de Trigo">
                        </div>
                        <div class="col-2">
                            <label class="form-label">Quantidade:</label>
                            <input type="number" class="form-control" name="produtos[${produtoCount}][quantidade]" required min="1">
                        </div>
                        <div class="col-2">
                            <label class="form-label">Preço Unitário:</label>
                            <input type="number" class="form-control" name="produtos[${produtoCount}][preco_unitario]" step="0.01" required min="0">
                        </div>
                        <div class="col-2">
                            <label class="form-label">Imposto Distrital:</label>
                            <input type="number" class="form-control" name="produtos[${produtoCount}][imposto_distrital]" step="0.01" min="0" value="0">
                        </div>
                        <div class="col-2">
                            <label class="form-label">Imposto Nacional:</label>
                            <input type="number" class="form-control" name="produtos[${produtoCount}][imposto_nacional]" step="0.01" min="0" value="0">
                        </div>
                        <div class="col-1 d-flex align-items-end">
                            <button type="button" class="btn btn-danger btn-sm remove-produto">Remover</button>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-2">
                            <label class="form-label">Taxa de Entrega:</label>
                            <input type="number" class="form-control" name="produtos[${produtoCount}][taxa_entrega]" step="0.01" min="0" value="0">
                        </div>
                        <div class="col-2">
                            <label class="form-label">Outras Taxas:</label>
                            <input type="number" class="form-control" name="produtos[${produtoCount}][outras_taxas]" step="0.01" min="0" value="0">
                        </div>
                        <div class="col-3">
                            <label class="form-label">Data de Validade:</label>
                            <input type="date" class="form-control" name="produtos[${produtoCount}][data_validade]">
                        </div>
                        <div class="col-3">
                            <label class="form-label">Categoria:</label>
                            <select name="produtos[${produtoCount}][categoria_id]" class="form-control">
                                <option value="">Sem categoria</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo $categoria['id']; ?>"><?php echo htmlspecialchars($categoria['nome']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                `;
                container.appendChild(newRow);
                produtoCount++;
            });

            container.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-produto')) {
                    e.target.closest('.produto-row').remove();
                }
            });
        });
    </script>
</head>

<body>
    <?php include 'navbar.php'; ?>
    <div class="container">
        <h3>Fazer Pedido a Fornecedor</h3>
        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($mensagem); ?></div>
        <?php endif; ?>
        <form method="POST" action="pedidos_fornecedores.php" class="mb-3">
            <button type="submit" name="verificar_estoque" class="btn btn-warning">Verificar Estoque Baixo</button>
        </form>

        <?php if (!empty($produtos_baixo_estoque)): ?>
            <h4>Produtos com Estoque Baixo</h4>
            <form method="POST" action="pedidos_fornecedores.php">
                <table class="table table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Produto</th>
                            <th>Estoque Atual</th>
                            <th>Estoque Mínimo</th>
                            <th>Fornecedor</th>
                            <th>Categoria</th>
                            <th>Preço Unitário</th>
                            <th>Data de Validade</th>
                            <th>Quantidade a Pedir</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produtos_baixo_estoque as $produto): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($produto['nome_produto']); ?></td>
                                <td><?php echo $produto['quantidade']; ?></td>
                                <td><?php echo $produto['estoque_minimo']; ?></td>
                                <td><?php echo htmlspecialchars($produto['nome_fornecedor']); ?></td>
                                <td><?php echo htmlspecialchars($produto['categoria_nome']); ?></td>
                                <td>R$ <?php echo number_format($produto['preco_unitario'], 2, ',', '.'); ?></td>
                                <td><?php echo $produto['data_validade'] ? date('d/m/Y', strtotime($produto['data_validade'])) : 'Não definida'; ?></td>
                                <td>
                                    <input type="hidden" name="produtos[<?php echo $produto['id']; ?>][fornecedor_id]" value="<?php echo $produto['fornecedor_id']; ?>">
                                    <input type="hidden" name="produtos[<?php echo $produto['id']; ?>][nome_produto]" value="<?php echo htmlspecialchars($produto['nome_produto']); ?>">
                                    <input type="hidden" name="produtos[<?php echo $produto['id']; ?>][preco_unitario]" value="<?php echo $produto['preco_unitario']; ?>">
                                    <input type="hidden" name="produtos[<?php echo $produto['id']; ?>][data_validade]" value="<?php echo $produto['data_validade'] ?? ''; ?>">
                                    <input type="hidden" name="produtos[<?php echo $produto['id']; ?>][categoria_id]" value="<?php echo $produto['categoria_id'] ?? ''; ?>">
                                    <input type="number" class="form-control" name="produtos[<?php echo $produto['id']; ?>][quantidade]" min="0" value="0">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="submit" name="confirmar_automatizacao" class="btn btn-primary">Confirmar Automatização</button>
            </form>
        <?php endif; ?>

        <form method="POST" action="pedidos_fornecedores.php">
            <div class="mb-3">
                <label for="fornecedor_id" class="form-label">Fornecedor:</label>
                <select name="fornecedor_id" class="form-control" required>
                    <option value="">Selecione um fornecedor</option>
                    <?php foreach ($fornecedores as $fornecedor): ?>
                        <option value="<?php echo $fornecedor['id']; ?>"><?php echo htmlspecialchars($fornecedor['nome']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div id="produtos-container"></div>
            <button type="button" id="addProduto" class="btn btn-secondary mb-3">Adicionar Produto</button>
            <button type="submit" name="fazer_pedido" class="btn btn-primary">Fazer Pedido</button>
        </form>

        <h3 class="mt-5">Pedidos Realizados</h3>
        <?php if (empty($pedidos)): ?>
            <p>Nenhum pedido realizado.</p>
        <?php else: ?>
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Fornecedor</th>
                        <th>Produto</th>
                        <th>Categoria</th>
                        <th>Quantidade</th>
                        <th>Preço Unitário</th>
                        <th>Imposto Distrital</th>
                        <th>Imposto Nacional</th>
                        <th>Taxa de Entrega</th>
                        <th>Outras Taxas</th>
                        <th>Data de Validade</th>
                        <th>Status</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $pedido): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($pedido['nome_fornecedor']); ?></td>
                            <td><?php echo htmlspecialchars($pedido['nome_produto']); ?></td>
                            <td><?php echo htmlspecialchars($pedido['categoria_nome'] ?? 'Sem categoria'); ?></td>
                            <td><?php echo $pedido['quantidade']; ?></td>
                            <td>R$ <?php echo number_format($pedido['preco_unitario'], 2, ',', '.'); ?></td>
                            <td>R$ <?php echo number_format($pedido['imposto_distrital'], 2, ',', '.'); ?></td>
                            <td>R$ <?php echo number_format($pedido['imposto_nacional'], 2, ',', '.'); ?></td>
                            <td>R$ <?php echo number_format($pedido['taxa_entrega'], 2, ',', '.'); ?></td>
                            <td>R$ <?php echo number_format($pedido['outras_taxas'], 2, ',', '.'); ?></td>
                            <td><?php echo $pedido['data_validade'] ? date('d/m/Y', strtotime($pedido['data_validade'])) : 'Não definida'; ?></td>
                            <td>
                                <?php
                                if ($pedido['status'] === 'pendente') {
                                    echo 'Pendente';
                                } elseif ($pedido['status'] === 'confirmado') {
                                    echo 'Confirmado';
                                } elseif ($pedido['status'] === 'negado') {
                                    echo 'Negado';
                                }
                                ?>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($pedido['criado_em'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
</body>

</html>