<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true || !in_array($_SESSION['perfil'], ['admin', 'gerente'])) {
    header("Location: login.html");
    exit();
}
require_once 'conexao.php';

// listar categorias e fornecedores para os filtros
$sql_categorias = "SELECT id, nome FROM categorias";
$resultado_categorias = $conexao->query($sql_categorias);
$categorias = $resultado_categorias->fetch_all(MYSQLI_ASSOC);

$sql_fornecedores = "SELECT id, nome FROM fornecedores";
$resultado_fornecedores = $conexao->query($sql_fornecedores);
$fornecedores = $resultado_fornecedores->fetch_all(MYSQLI_ASSOC);

// inicializar variáveis para os resultados
$resultados_produtos = [];
$resultados_vendas = [];
$resultados_desperdicio = [];

// pesquisa de Produtos
if (isset($_POST['pesquisar_produtos'])) {
    $nome_produto = $_POST['nome_produto'] ?? '';
    $categoria_id = $_POST['categoria_id'] ?? '';
    $quantidade_min = $_POST['quantidade_min'] ?? '';
    $quantidade_max = $_POST['quantidade_max'] ?? '';
    $preco_min = $_POST['preco_min'] ?? '';
    $preco_max = $_POST['preco_max'] ?? '';
    $fornecedor_id = $_POST['fornecedor_id'] ?? '';

    $sql = "SELECT p.nome_produto, c.nome as nome_categoria, p.quantidade, p.preco_unitario_venda, f.nome as nome_fornecedor 
            FROM produtos p 
            LEFT JOIN categorias c ON p.categoria_id = c.id 
            LEFT JOIN fornecedores f ON p.fornecedor_id = f.id 
            WHERE 1=1";
    $params = [];
    $types = '';

    if ($nome_produto) {
        $sql .= " AND p.nome_produto LIKE ?";
        $params[] = "%$nome_produto%";
        $types .= 's';
    }
    if ($categoria_id) {
        $sql .= " AND p.categoria_id = ?";
        $params[] = $categoria_id;
        $types .= 'i';
    }
    if ($quantidade_min !== '') {
        $sql .= " AND p.quantidade >= ?";
        $params[] = $quantidade_min;
        $types .= 'i';
    }
    if ($quantidade_max !== '') {
        $sql .= " AND p.quantidade <= ?";
        $params[] = $quantidade_max;
        $types .= 'i';
    }
    if ($preco_min !== '') {
        $sql .= " AND p.preco_unitario_venda >= ?";
        $params[] = $preco_min;
        $types .= 'd';
    }
    if ($preco_max !== '') {
        $sql .= " AND p.preco_unitario_venda <= ?";
        $params[] = $preco_max;
        $types .= 'd';
    }
    if ($fornecedor_id) {
        $sql .= " AND p.fornecedor_id = ?";
        $params[] = $fornecedor_id;
        $types .= 'i';
    }

    $stmt = $conexao->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $resultado = $stmt->get_result();
    while ($row = $resultado->fetch_assoc()) {
        $resultados_produtos[] = $row;
    }
    $stmt->close();
}

// pesquisa de Vendas
if (isset($_POST['pesquisar_vendas'])) {
    $data_inicio = $_POST['data_inicio'] ?? '';
    $data_fim = $_POST['data_fim'] ?? '';
    $produto_id = $_POST['produto_id'] ?? '';
    $quantidade_min = $_POST['quantidade_min_vendas'] ?? '';
    $quantidade_max = $_POST['quantidade_max_vendas'] ?? '';
    $valor_min = $_POST['valor_min'] ?? '';
    $valor_max = $_POST['valor_max'] ?? '';

    $sql = "SELECT v.data_venda, p.nome_produto, v.quantidade_vendida, v.preco_unitario_venda, 
            (v.quantidade_vendida * v.preco_unitario_venda) as total 
            FROM vendas v 
            JOIN produtos p ON v.produto_id = p.id 
            WHERE 1=1";
    $params = [];
    $types = '';

    if ($data_inicio) {
        $sql .= " AND v.data_venda >= ?";
        $params[] = $data_inicio;
        $types .= 's';
    }
    if ($data_fim) {
        $sql .= " AND v.data_venda <= ?";
        $params[] = $data_fim . ' 23:59:59';
        $types .= 's';
    }
    if ($produto_id) {
        $sql .= " AND v.produto_id = ?";
        $params[] = $produto_id;
        $types .= 'i';
    }
    if ($quantidade_min !== '') {
        $sql .= " AND v.quantidade_vendida >= ?";
        $params[] = $quantidade_min;
        $types .= 'i';
    }
    if ($quantidade_max !== '') {
        $sql .= " AND v.quantidade_vendida <= ?";
        $params[] = $quantidade_max;
        $types .= 'i';
    }
    if ($valor_min !== '') {
        $sql .= " AND (v.quantidade_vendida * v.preco_unitario_venda) >= ?";
        $params[] = $valor_min;
        $types .= 'd';
    }
    if ($valor_max !== '') {
        $sql .= " AND (v.quantidade_vendida * v.preco_unitario_venda) <= ?";
        $params[] = $valor_max;
        $types .= 'd';
    }

    $stmt = $conexao->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $resultado = $stmt->get_result();
    while ($row = $resultado->fetch_assoc()) {
        $resultados_vendas[] = $row;
    }
    $stmt->close();
}

// pesquisa de Desperdício
if (isset($_POST['pesquisar_desperdicio'])) {
    $data_inicio = $_POST['data_inicio_desperdicio'] ?? '';
    $data_fim = $_POST['data_fim_desperdicio'] ?? '';
    $produto_id = $_POST['produto_id_desperdicio'] ?? '';
    $motivo = $_POST['motivo'] ?? '';
    $usuario_id = $_POST['usuario_id'] ?? '';

    $sql = "SELECT d.data_desperdicio, p.nome_produto, d.quantidade, d.motivo, u.nome as nome_usuario 
            FROM desperdicio d 
            JOIN produtos p ON d.produto_id = p.id 
            LEFT JOIN usuarios u ON d.usuario_id = u.id 
            WHERE 1=1";
    $params = [];
    $types = '';

    if ($data_inicio) {
        $sql .= " AND d.data_desperdicio >= ?";
        $params[] = $data_inicio;
        $types .= 's';
    }
    if ($data_fim) {
        $sql .= " AND d.data_desperdicio <= ?";
        $params[] = $data_fim . ' 23:59:59';
        $types .= 's';
    }
    if ($produto_id) {
        $sql .= " AND d.produto_id = ?";
        $params[] = $produto_id;
        $types .= 'i';
    }
    $stmt = $conexao->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $resultado = $stmt->get_result();
    while ($row = $resultado->fetch_assoc()) {
        $resultados_desperdicio[] = $row;
    }
    $stmt->close();
}

// Listar produtos e usuários para os filtros
$sql_produtos = "SELECT id, nome_produto FROM produtos";
$resultado_produtos = $conexao->query($sql_produtos);
$produtos = $resultado_produtos->fetch_all(MYSQLI_ASSOC);

$sql_usuarios = "SELECT id, nome FROM usuarios";
$resultado_usuarios = $conexao->query($sql_usuarios);
$usuarios = $resultado_usuarios->fetch_all(MYSQLI_ASSOC);

$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesquisa Avançada - Panificadora</title>
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
        <h2>Pesquisa Avançada</h2>

        <!-- pesquisa de Produtos -->
        <div class="search-section">
            <h3>Pesquisar Produtos</h3>
            <form method="POST" action="pesquisa_avancada.php">
                <label for="nome_produto">Nome do Produto:</label>
                <input type="text" name="nome_produto" value="<?php echo isset($_POST['nome_produto']) ? htmlspecialchars($_POST['nome_produto']) : ''; ?>">

                <label for="categoria_id">Categoria:</label>
                <select name="categoria_id">
                    <option value="">Todas</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?php echo $categoria['id']; ?>" <?php echo (isset($_POST['categoria_id']) && $_POST['categoria_id'] == $categoria['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($categoria['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="quantidade_min">Quantidade Mínima:</label>
                <input type="number" name="quantidade_min" value="<?php echo isset($_POST['quantidade_min']) ? htmlspecialchars($_POST['quantidade_min']) : ''; ?>">

                <label for="quantidade_max">Quantidade Máxima:</label>
                <input type="number" name="quantidade_max" value="<?php echo isset($_POST['quantidade_max']) ? htmlspecialchars($_POST['quantidade_max']) : ''; ?>">

                <label for="preco_min">Preço Mínimo:</label>
                <input type="number" step="0.01" name="preco_min" value="<?php echo isset($_POST['preco_min']) ? htmlspecialchars($_POST['preco_min']) : ''; ?>">

                <label for="preco_max">Preço Máximo:</label>
                <input type="number" step="0.01" name="preco_max" value="<?php echo isset($_POST['preco_max']) ? htmlspecialchars($_POST['preco_max']) : ''; ?>">

                <label for="fornecedor_id">Fornecedor:</label>
                <select name="fornecedor_id">
                    <option value="">Todos</option>
                    <?php foreach ($fornecedores as $fornecedor): ?>
                        <option value="<?php echo $fornecedor['id']; ?>" <?php echo (isset($_POST['fornecedor_id']) && $_POST['fornecedor_id'] == $fornecedor['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($fornecedor['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select><br>

                <button type="submit" name="pesquisar_produtos">Pesquisar</button>
                <button type="button" onclick="window.location.href='pesquisa_avancada.php'">Limpar Filtros</button>
            </form>

            <?php if (!empty($resultados_produtos)): ?>
                <table class="result-table">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Categoria</th>
                            <th>Quantidade</th>
                            <th>Preço Unitário</th>
                            <th>Fornecedor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resultados_produtos as $produto): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($produto['nome_produto']); ?></td>
                                <td><?php echo htmlspecialchars($produto['nome_categoria'] ?? 'Sem Categoria'); ?></td>
                                <td><?php echo htmlspecialchars($produto['quantidade']); ?></td>
                                <td><?php echo number_format($produto['preco_unitario_venda'], 2, ',', '.'); ?></td>
                                <td><?php echo htmlspecialchars($produto['nome_fornecedor'] ?? '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php elseif (isset($_POST['pesquisar_produtos'])): ?>
                <p>Nenhum produto encontrado com os filtros selecionados.</p>
            <?php endif; ?>
        </div>

        <!-- pesquisa de Vendas -->
        <div class="search-section">
            <h3>Pesquisar Vendas</h3>
            <form method="POST" action="pesquisa_avancada.php">
                <label for="data_inicio">Data Início:</label>
                <input type="date" name="data_inicio" value="<?php echo isset($_POST['data_inicio']) ? htmlspecialchars($_POST['data_inicio']) : ''; ?>">

                <label for="data_fim">Data Fim:</label>
                <input type="date" name="data_fim" value="<?php echo isset($_POST['data_fim']) ? htmlspecialchars($_POST['data_fim']) : ''; ?>">

                <label for="produto_id">Produto:</label>
                <select name="produto_id">
                    <option value="">Todos</option>
                    <?php foreach ($produtos as $produto): ?>
                        <option value="<?php echo $produto['id']; ?>" <?php echo (isset($_POST['produto_id']) && $_POST['produto_id'] == $produto['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($produto['nome_produto']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="quantidade_min_vendas">Quantidade Mínima:</label>
                <input type="number" name="quantidade_min_vendas" value="<?php echo isset($_POST['quantidade_min_vendas']) ? htmlspecialchars($_POST['quantidade_min_vendas']) : ''; ?>">

                <label for="quantidade_max_vendas">Quantidade Máxima:</label>
                <input type="number" name="quantidade_max_vendas" value="<?php echo isset($_POST['quantidade_max_vendas']) ? htmlspecialchars($_POST['quantidade_max_vendas']) : ''; ?>">

                <label for="valor_min">Valor Mínimo:</label>
                <input type="number" step="0.01" name="valor_min" value="<?php echo isset($_POST['valor_min']) ? htmlspecialchars($_POST['valor_min']) : ''; ?>">

                <label for="valor_max">Valor Máximo:</label>
                <input type="number" step="0.01" name="valor_max" value="<?php echo isset($_POST['valor_max']) ? htmlspecialchars($_POST['valor_max']) : ''; ?>"><br>

                <button type="submit" name="pesquisar_vendas">Pesquisar</button>
                <button type="button" onclick="window.location.href='pesquisa_avancada.php'">Limpar Filtros</button>
            </form>

            <?php if (!empty($resultados_vendas)): ?>
                <table class="result-table">
                    <thead>
                        <tr>
                            <th>Data da Venda</th>
                            <th>Produto</th>
                            <th>Quantidade Vendida</th>
                            <th>Preço Unitário</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resultados_vendas as $venda): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($venda['data_venda'])); ?></td>
                                <td><?php echo htmlspecialchars($venda['nome_produto']); ?></td>
                                <td><?php echo htmlspecialchars($venda['quantidade_vendida']); ?></td>
                                <td><?php echo number_format($venda['preco_unitario_venda'], 2, ',', '.'); ?></td>
                                <td><?php echo number_format($venda['total'], 2, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php elseif (isset($_POST['pesquisar_vendas'])): ?>
                <p>Nenhuma venda encontrada com os filtros selecionados.</p>
            <?php endif; ?>
        </div>

        <!-- pesquisa de Desperdício -->
        <div class="search-section">
            <h3>Pesquisar Desperdício</h3>
            <form method="POST" action="pesquisa_avancada.php">
                <label for="data_inicio_desperdicio">Data Início:</label>
                <input type="date" name="data_inicio_desperdicio" value="<?php echo isset($_POST['data_inicio_desperdicio']) ? htmlspecialchars($_POST['data_inicio_desperdicio']) : ''; ?>">

                <label for="data_fim_desperdicio">Data Fim:</label>
                <input type="date" name="data_fim_desperdicio" value="<?php echo isset($_POST['data_fim_desperdicio']) ? htmlspecialchars($_POST['data_fim_desperdicio']) : ''; ?>">

                <label for="produto_id_desperdicio">Produto:</label>
                <select name="produto_id_desperdicio">
                    <option value="">Todos</option>
                    <?php foreach ($produtos as $produto): ?>
                        <option value="<?php echo $produto['id']; ?>" <?php echo (isset($_POST['produto_id_desperdicio']) && $_POST['produto_id_desperdicio'] == $produto['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($produto['nome_produto']); ?>
                        </option>
                    <?php endforeach; ?>
                </select><br>
                <button type="submit" name="pesquisar_desperdicio">Pesquisar</button>
                <button type="button" onclick="window.location.href='pesquisa_avancada.php'">Limpar Filtros</button>
            </form>

            <?php if (!empty($resultados_desperdicio)): ?>
                <table class="result-table">
                    <thead>
                        <tr>
                            <th>Data do Desperdício</th>
                            <th>Produto</th>
                            <th>Quantidade</th>
                            <th>Usuário</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resultados_desperdicio as $desperdicio): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($desperdicio['data_desperdicio'])); ?></td>
                                <td><?php echo htmlspecialchars($desperdicio['nome_produto']); ?></td>
                                <td><?php echo htmlspecialchars($desperdicio['quantidade']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php elseif (isset($_POST['pesquisar_desperdicio'])): ?>
                <p>Nenhum registro de desperdício encontrado com os filtros selecionados.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>