<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true || !in_array($_SESSION['perfil'], ['admin', 'gerente'])) {
    header("Location: login.html");
    exit();
}
require_once 'conexao.php';

// Função pra registrar o log de exportação
function registrarLogExportacao($conexao, $usuario_id, $tipo_dados, $formato)
{
    $sql = "INSERT INTO log_exportacoes (usuario_id, tipo_dados, formato, data_exportacao) VALUES (?, ?, ?, NOW())";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("iss", $usuario_id, $tipo_dados, $formato);
    $stmt->execute();
    $stmt->close();
}

// Exportar Vendas
if (isset($_POST['exportar_vendas'])) {
    $data_inicio = $_POST['data_inicio'];
    $data_fim = $_POST['data_fim'];
    $formato = $_POST['formato'];
    $colunas = isset($_POST['colunas_vendas']) ? $_POST['colunas_vendas'] : [];

    $sql_vendas = "SELECT v.data_venda, p.nome_produto, v.quantidade_vendida, v.preco_unitario_venda, 
                   (v.quantidade_vendida * v.preco_unitario_venda) as total 
                   FROM vendas v 
                   JOIN produtos p ON v.produto_id = p.id 
                   WHERE v.data_venda BETWEEN ? AND ?";
    $stmt_vendas = $conexao->prepare($sql_vendas);
    $stmt_vendas->bind_param("ss", $data_inicio, $data_fim);
    $stmt_vendas->execute();
    $resultado_vendas = $stmt_vendas->get_result();

    if ($formato === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="vendas_' . $data_inicio . '_a_' . $data_fim . '.csv"');
        $output = fopen('php://output', 'w');
        $cabecalho = [];
        if (in_array('data', $colunas)) $cabecalho[] = 'Data da Venda';
        if (in_array('produto', $colunas)) $cabecalho[] = 'Produto';
        if (in_array('quantidade', $colunas)) $cabecalho[] = 'Quantidade Vendida';
        if (in_array('preco', $colunas)) $cabecalho[] = 'Preço Unitário';
        if (in_array('total', $colunas)) $cabecalho[] = 'Total';
        fputcsv($output, $cabecalho, ';');

        while ($row = $resultado_vendas->fetch_assoc()) {
            $linha = [];
            if (in_array('data', $colunas)) $linha[] = date('d/m/Y H:i:s', strtotime($row['data_venda']));
            if (in_array('produto', $colunas)) $linha[] = $row['nome_produto'];
            if (in_array('quantidade', $colunas)) $linha[] = $row['quantidade_vendida'];
            if (in_array('preco', $colunas)) $linha[] = number_format($row['preco_unitario_venda'], 2, ',', '.');
            if (in_array('total', $colunas)) $linha[] = number_format($row['total'], 2, ',', '.');
            fputcsv($output, $linha, ';');
        }
        fclose($output);
    } else {
        $dados = [];
        while ($row = $resultado_vendas->fetch_assoc()) {
            $linha = [];
            if (in_array('data', $colunas)) $linha['data_venda'] = date('d/m/Y H:i:s', strtotime($row['data_venda']));
            if (in_array('produto', $colunas)) $linha['produto'] = $row['nome_produto'];
            if (in_array('quantidade', $colunas)) $linha['quantidade_vendida'] = $row['quantidade_vendida'];
            if (in_array('preco', $colunas)) $linha['preco_unitario'] = $row['preco_unitario_venda'];
            if (in_array('total', $colunas)) $linha['total'] = $row['total'];
            $dados[] = $linha;
        }
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="vendas_' . $data_inicio . '_a_' . $data_fim . '.json"');
        echo json_encode($dados);
    }

    registrarLogExportacao($conexao, $_SESSION['usuario_id'], 'vendas', $formato);
    $stmt_vendas->close();
    exit();
}

// Exportar Estoque
if (isset($_POST['exportar_estoque'])) {
    $categoria_id = $_POST['categoria_id'] ?? null;
    $formato = $_POST['formato'];

    $sql_estoque = "SELECT p.nome_produto, c.nome as nome_categoria, p.quantidade 
                    FROM produtos p 
                    LEFT JOIN categorias c ON p.categoria_id = c.id";
    if ($categoria_id) {
        $sql_estoque .= " WHERE p.categoria_id = ?";
        $stmt_estoque = $conexao->prepare($sql_estoque);
        $stmt_estoque->bind_param("i", $categoria_id);
        $stmt_estoque->execute();
        $resultado_estoque = $stmt_estoque->get_result();
    } else {
        $resultado_estoque = $conexao->query($sql_estoque);
    }

    if ($formato === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="estoque_' . date('Y-m-d') . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Produto', 'Categoria', 'Quantidade em Estoque'], ';');

        while ($row = $resultado_estoque->fetch_assoc()) {
            fputcsv($output, [
                $row['nome_produto'],
                $row['nome_categoria'] ?? 'Sem Categoria',
                $row['quantidade']
            ], ';');
        }
        fclose($output);
    } else {
        $dados = [];
        while ($row = $resultado_estoque->fetch_assoc()) {
            $dados[] = [
                'produto' => $row['nome_produto'],
                'categoria' => $row['nome_categoria'] ?? 'Sem Categoria',
                'quantidade' => $row['quantidade']
            ];
        }
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="estoque_' . date('Y-m-d') . '.json"');
        echo json_encode($dados);
    }

    registrarLogExportacao($conexao, $_SESSION['usuario_id'], 'estoque', $formato);
    if (isset($stmt_estoque)) $stmt_estoque->close();
    exit();
}

// Exportar Fornecedores
if (isset($_POST['exportar_fornecedores'])) {
    $formato = $_POST['formato'];

    $sql_fornecedores = "SELECT nome, telefone, endereco, email FROM fornecedores";
    $resultado_fornecedores = $conexao->query($sql_fornecedores);

    if ($formato === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="fornecedores_' . date('Y-m-d') . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Nome', 'Telefone', 'Endereço', 'Email'], ';');

        while ($row = $resultado_fornecedores->fetch_assoc()) {
            fputcsv($output, [
                $row['nome'],
                $row['telefone'],
                $row['endereco'] ?? '-',
                $row['email'] ?? '-'
            ], ';');
        }
        fclose($output);
    } else {
        $dados = [];
        while ($row = $resultado_fornecedores->fetch_assoc()) {
            $dados[] = [
                'nome' => $row['nome'],
                'telefone' => $row['telefone'],
                'endereco' => $row['endereco'] ?? '-',
                'email' => $row['email'] ?? '-'
            ];
        }
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="fornecedores_' . date('Y-m-d') . '.json"');
        echo json_encode($dados);
    }

    registrarLogExportacao($conexao, $_SESSION['usuario_id'], 'fornecedores', $formato);
    exit();
}

// Exportar Produtos
if (isset($_POST['exportar_produtos'])) {
    $formato = $_POST['formato'];

    $sql_produtos = "SELECT p.nome_produto, c.nome as nome_categoria, p.quantidade, p.preco as preco_unitario_venda, f.nome as nome_fornecedor, p.data_validade 
                     FROM produtos p 
                     LEFT JOIN categorias c ON p.categoria_id = c.id 
                     LEFT JOIN fornecedores f ON p.fornecedor_id = f.id";
    $resultado_produtos = $conexao->query($sql_produtos);

    if ($formato === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="produtos_' . date('Y-m-d') . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Produto', 'Categoria', 'Quantidade', 'Preço Unitário', 'Fornecedor', 'Data de Validade'], ';');

        while ($row = $resultado_produtos->fetch_assoc()) {
            fputcsv($output, [
                $row['nome_produto'],
                $row['nome_categoria'] ?? 'Sem Categoria',
                $row['quantidade'],
                number_format($row['preco_unitario_venda'], 2, ',', '.'),
                $row['nome_fornecedor'] ?? '-',
                $row['data_validade'] ? date('d/m/Y', strtotime($row['data_validade'])) : 'Não definida'
            ], ';');
        }
        fclose($output);
    } else {
        $dados = [];
        while ($row = $resultado_produtos->fetch_assoc()) {
            $dados[] = [
                'produto' => $row['nome_produto'],
                'categoria' => $row['nome_categoria'] ?? 'Sem Categoria',
                'quantidade' => $row['quantidade'],
                'preco_unitario' => $row['preco_unitario_venda'],
                'fornecedor' => $row['nome_fornecedor'] ?? '-',
                'data_validade' => $row['data_validade'] ? date('d/m/Y', strtotime($row['data_validade'])) : 'Não definida'
            ];
        }
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="produtos_' . date('Y-m-d') . '.json"');
        echo json_encode($dados);
    }

    registrarLogExportacao($conexao, $_SESSION['usuario_id'], 'produtos', $formato);
    exit();
}

// Exportar Produção Planejada
if (isset($_POST['exportar_producao'])) {
    $data_inicio = $_POST['data_inicio'];
    $data_fim = $_POST['data_fim'];
    $formato = $_POST['formato'];

    $sql_producao = "SELECT pp.data_producao, p.nome_produto, pp.quantidade_planejada, pp.data_registro 
                     FROM producao_planejada pp 
                     JOIN produtos p ON pp.produto_id = p.id 
                     WHERE pp.data_producao BETWEEN ? AND ?";
    $stmt_producao = $conexao->prepare($sql_producao);
    $stmt_producao->bind_param("ss", $data_inicio, $data_fim);
    $stmt_producao->execute();
    $resultado_producao = $stmt_producao->get_result();

    if ($formato === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="producao_' . $data_inicio . '_a_' . $data_fim . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Data da Produção', 'Produto', 'Quantidade Planejada', 'Data do Registro'], ';');

        while ($row = $resultado_producao->fetch_assoc()) {
            fputcsv($output, [
                date('d/m/Y', strtotime($row['data_producao'])),
                $row['nome_produto'],
                $row['quantidade_planejada'],
                date('d/m/Y H:i:s', strtotime($row['data_registro']))
            ], ';');
        }
        fclose($output);
    } else {
        $dados = [];
        while ($row = $resultado_producao->fetch_assoc()) {
            $dados[] = [
                'data_producao' => date('d/m/Y', strtotime($row['data_producao'])),
                'produto' => $row['nome_produto'],
                'quantidade_planejada' => $row['quantidade_planejada'],
                'data_registro' => date('d/m/Y H:i:s', strtotime($row['data_registro']))
            ];
        }
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="producao_' . $data_inicio . '_a_' . $data_fim . '.json"');
        echo json_encode($dados);
    }

    registrarLogExportacao($conexao, $_SESSION['usuario_id'], 'producao_planejada', $formato);
    $stmt_producao->close();
    exit();
}

// Exportar Produtos por Fornecedores
if (isset($_POST['exportar_produtos_fornecedores'])) {
    $formato = $_POST['formato'];

    $sql_produtos_fornecedores = "SELECT pf.nome_produto, f.nome as nome_fornecedor, c.nome as nome_categoria, pf.preco_unitario, pf.data_validade 
                                  FROM produtos_fornecedores pf 
                                  JOIN fornecedores f ON pf.fornecedor_id = f.id 
                                  LEFT JOIN categorias c ON pf.categoria_id = c.id";
    $resultado_produtos_fornecedores = $conexao->query($sql_produtos_fornecedores);

    if ($formato === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="produtos_fornecedores_' . date('Y-m-d') . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Produto', 'Fornecedor', 'Categoria', 'Preço Unitário', 'Data de Validade'], ';');

        while ($row = $resultado_produtos_fornecedores->fetch_assoc()) {
            fputcsv($output, [
                $row['nome_produto'],
                $row['nome_fornecedor'],
                $row['nome_categoria'] ?? 'Sem Categoria',
                number_format($row['preco_unitario'], 2, ',', '.'),
                $row['data_validade'] ? date('d/m/Y', strtotime($row['data_validade'])) : 'Não definida'
            ], ';');
        }
        fclose($output);
    } else {
        $dados = [];
        while ($row = $resultado_produtos_fornecedores->fetch_assoc()) {
            $dados[] = [
                'produto' => $row['nome_produto'],
                'fornecedor' => $row['nome_fornecedor'],
                'categoria' => $row['nome_categoria'] ?? 'Sem Categoria',
                'preco_unitario' => $row['preco_unitario'],
                'data_validade' => $row['data_validade'] ? date('d/m/Y', strtotime($row['data_validade'])) : 'Não definida'
            ];
        }
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="produtos_fornecedores_' . date('Y-m-d') . '.json"');
        echo json_encode($dados);
    }

    registrarLogExportacao($conexao, $_SESSION['usuario_id'], 'produtos_fornecedores', $formato);
    exit();
}

// Exportar Pedidos a Fornecedores
if (isset($_POST['exportar_pedidos_fornecedores'])) {
    $data_inicio = $_POST['data_inicio'];
    $data_fim = $_POST['data_fim'];
    $formato = $_POST['formato'];

    $sql_pedidos = "SELECT pf.nome_produto, f.nome as nome_fornecedor, c.nome as nome_categoria, pf.quantidade, pf.preco_unitario, 
                    pf.imposto_distrital, pf.imposto_nacional, pf.taxa_entrega, pf.outras_taxas, pf.data_validade, pf.status, pf.criado_em 
                    FROM pedidos_fornecedores pf 
                    JOIN fornecedores f ON pf.fornecedor_id = f.id 
                    LEFT JOIN categorias c ON pf.categoria_id = c.id 
                    WHERE pf.criado_em BETWEEN ? AND ?";
    $stmt_pedidos = $conexao->prepare($sql_pedidos);
    $stmt_pedidos->bind_param("ss", $data_inicio, $data_fim);
    $stmt_pedidos->execute();
    $resultado_pedidos = $stmt_pedidos->get_result();

    if ($formato === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="pedidos_fornecedores_' . $data_inicio . '_a_' . $data_fim . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Produto', 'Fornecedor', 'Categoria', 'Quantidade', 'Preço Unitário', 'Imposto Distrital', 'Imposto Nacional', 'Taxa de Entrega', 'Outras Taxas', 'Data de Validade', 'Status', 'Data do Pedido'], ';');

        while ($row = $resultado_pedidos->fetch_assoc()) {
            fputcsv($output, [
                $row['nome_produto'],
                $row['nome_fornecedor'],
                $row['nome_categoria'] ?? 'Sem Categoria',
                $row['quantidade'],
                number_format($row['preco_unitario'], 2, ',', '.'),
                number_format($row['imposto_distrital'], 2, ',', '.'),
                number_format($row['imposto_nacional'], 2, ',', '.'),
                number_format($row['taxa_entrega'], 2, ',', '.'),
                number_format($row['outras_taxas'], 2, ',', '.'),
                $row['data_validade'] ? date('d/m/Y', strtotime($row['data_validade'])) : 'Não definida',
                $row['status'],
                date('d/m/Y H:i:s', strtotime($row['criado_em']))
            ], ';');
        }
        fclose($output);
    } else {
        $dados = [];
        while ($row = $resultado_pedidos->fetch_assoc()) {
            $dados[] = [
                'produto' => $row['nome_produto'],
                'fornecedor' => $row['nome_fornecedor'],
                'categoria' => $row['nome_categoria'] ?? 'Sem Categoria',
                'quantidade' => $row['quantidade'],
                'preco_unitario' => $row['preco_unitario'],
                'imposto_distrital' => $row['imposto_distrital'],
                'imposto_nacional' => $row['imposto_nacional'],
                'taxa_entrega' => $row['taxa_entrega'],
                'outras_taxas' => $row['outras_taxas'],
                'data_validade' => $row['data_validade'] ? date('d/m/Y', strtotime($row['data_validade'])) : 'Não definida',
                'status' => $row['status'],
                'data_pedido' => date('d/m/Y H:i:s', strtotime($row['criado_em']))
            ];
        }
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="pedidos_fornecedores_' . $data_inicio . '_a_' . $data_fim . '.json"');
        echo json_encode($dados);
    }

    registrarLogExportacao($conexao, $_SESSION['usuario_id'], 'pedidos_fornecedores', $formato);
    $stmt_pedidos->close();
    exit();
}

// Exportar Logs (apenas para admin no Modo Completo)
if (isset($_POST['exportar_logs'])) {
    $data_inicio = $_POST['data_inicio'];
    $data_fim = $_POST['data_fim'];
    $formato = $_POST['formato'];

    $sql_logs = "SELECT l.acao, l.data_acao, u.usuario as nome_usuario 
                 FROM logs l 
                 LEFT JOIN usuarios u ON l.usuario_id = u.id 
                 WHERE l.data_acao BETWEEN ? AND ?";
    $stmt_logs = $conexao->prepare($sql_logs);
    $stmt_logs->bind_param("ss", $data_inicio, $data_fim);
    $stmt_logs->execute();
    $resultado_logs = $stmt_logs->get_result();

    if ($formato === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="logs_' . $data_inicio . '_a_' . $data_fim . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Ação', 'Data da Ação', 'Usuário'], ';');

        while ($row = $resultado_logs->fetch_assoc()) {
            fputcsv($output, [
                $row['acao'],
                date('d/m/Y H:i:s', strtotime($row['data_acao'])),
                $row['nome_usuario'] ?? 'Desconhecido'
            ], ';');
        }
        fclose($output);
    } else {
        $dados = [];
        while ($row = $resultado_logs->fetch_assoc()) {
            $dados[] = [
                'acao' => $row['acao'],
                'data_acao' => date('d/m/Y H:i:s', strtotime($row['data_acao'])),
                'usuario' => $row['nome_usuario'] ?? 'Desconhecido'
            ];
        }
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="logs_' . $data_inicio . '_a_' . $data_fim . '.json"');
        echo json_encode($dados);
    }

    registrarLogExportacao($conexao, $_SESSION['usuario_id'], 'logs', $formato);
    $stmt_logs->close();
    exit();
}

// Listar categorias pra filtro de estoque
$sql_categorias = "SELECT id, nome FROM categorias";
$resultado_categorias = $conexao->query($sql_categorias);
$categorias = $resultado_categorias->fetch_all(MYSQLI_ASSOC);

// Listar logs de exportação
$sql_logs = "SELECT l.*, u.usuario as nome_usuario 
             FROM log_exportacoes l 
             LEFT JOIN usuarios u ON l.usuario_id = u.id 
             ORDER BY l.data_exportacao DESC 
             LIMIT 10";
$resultado_logs = $conexao->query($sql_logs);
$logs = [];
while ($row = $resultado_logs->fetch_assoc()) {
    $logs[] = $row;
}

$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exportar Dados - Panificadora</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <?php include 'navbar.php'; ?>
    <div class="container">

        <!-- Exportar Vendas -->
        <h3>Exportar Vendas</h3>
        <form method="POST" action="exportar_dados.php">
            <label for="data_inicio">Data Início:</label>
            <input type="date" name="data_inicio" value="<?php echo date('Y-m-d', strtotime('-7 days')); ?>" required>
            <label for="data_fim">Data Fim:</label>
            <input type="date" name="data_fim" value="<?php echo date('Y-m-d'); ?>" required><br>

            <label>Colunas a Exportar:</label><br>
            Data da Venda
            <input type="checkbox" name="colunas_vendas[]" value="data" checked>
            Produto
            <input type="checkbox" name="colunas_vendas[]" value="produto" checked>
            Quantidade Vendida
            <input type="checkbox" name="colunas_vendas[]" value="quantidade" checked>
            Preço Unitário
            <input type="checkbox" name="colunas_vendas[]" value="preco" checked>
            Total
            <input type="checkbox" name="colunas_vendas[]" value="total" checked><br>

            <label for="formato">Formato:</label>
            <select name="formato" required>
                <option value="csv">CSV</option>
                <option value="json">JSON</option>
            </select><br>

            <button type="submit" name="exportar_vendas">Exportar Vendas</button>
        </form>
        <br>

        <!-- Exportar Estoque -->
        <h3>Exportar Estoque</h3>
        <form method="POST" action="exportar_dados.php">
            <label for="categoria_id">Filtrar por Categoria (opcional):</label>
            <select name="categoria_id">
                <option value="">Todas</option>
                <?php foreach ($categorias as $categoria): ?>
                    <option value="<?php echo $categoria['id']; ?>"><?php echo htmlspecialchars($categoria['nome']); ?></option>
                <?php endforeach; ?>
            </select><br>

            <label for="formato">Formato:</label>
            <select name="formato" required>
                <option value="csv">CSV</option>
                <option value="json">JSON</option>
            </select><br>

            <button type="submit" name="exportar_estoque">Exportar Estoque</button>
        </form>
        <br>

        <!-- Exportar Fornecedores -->
        <h3>Exportar Fornecedores</h3>
        <form method="POST" action="exportar_dados.php">
            <label for="formato">Formato:</label>
            <select name="formato" required>
                <option value="csv">CSV</option>
                <option value="json">JSON</option>
            </select><br>

            <button type="submit" name="exportar_fornecedores">Exportar Fornecedores</button>
        </form>
        <br>

        <!-- Exportar Produtos -->
        <h3>Exportar Produtos</h3>
        <form method="POST" action="exportar_dados.php">
            <label for="formato">Formato:</label>
            <select name="formato" required>
                <option value="csv">CSV</option>
                <option value="json">JSON</option>
            </select><br>

            <button type="submit" name="exportar_produtos">Exportar Produtos</button>
        </form>
        <br>

        <!-- Exportar Produção Planejada -->
        <h3>Exportar Produção Planejada</h3>
        <form method="POST" action="exportar_dados.php">
            <label for="data_inicio">Data Início:</label>
            <input type="date" name="data_inicio" value="<?php echo date('Y-m-d', strtotime('-7 days')); ?>" required>
            <label for="data_fim">Data Fim:</label>
            <input type="date" name="data_fim" value="<?php echo date('Y-m-d'); ?>" required><br>

            <label for="formato">Formato:</label>
            <select name="formato" required>
                <option value="csv">CSV</option>
                <option value="json">JSON</option>
            </select><br>

            <button type="submit" name="exportar_producao">Exportar Produção Planejada</button>
        </form>
        <br>

        <!-- Exportar Produtos por Fornecedores -->
        <h3>Exportar Produtos por Fornecedores</h3>
        <form method="POST" action="exportar_dados.php">
            <label for="formato">Formato:</label>
            <select name="formato" required>
                <option value="csv">CSV</option>
                <option value="json">JSON</option>
            </select><br>

            <button type="submit" name="exportar_produtos_fornecedores">Exportar Produtos por Fornecedores</button>
        </form>
        <br>

        <!-- Exportar Pedidos a Fornecedores -->
        <h3>Exportar Pedidos a Fornecedores</h3>
        <form method="POST" action="exportar_dados.php">
            <label for="data_inicio">Data Início:</label>
            <input type="date" name="data_inicio" value="<?php echo date('Y-m-d', strtotime('-7 days')); ?>" required>
            <label for="data_fim">Data Fim:</label>
            <input type="date" name="data_fim" value="<?php echo date('Y-m-d'); ?>" required><br>

            <label for="formato">Formato:</label>
            <select name="formato" required>
                <option value="csv">CSV</option>
                <option value="json">JSON</option>
            </select><br>

            <button type="submit" name="exportar_pedidos_fornecedores">Exportar Pedidos a Fornecedores</button>
        </form>
        <br>

        <!-- Exportar Logs (Apenas para Admin no Modo Completo) -->
        <?php if ($_SESSION['perfil'] === 'admin' && $isCompleteMode): ?>
            <h3>Exportar Logs do Sistema</h3>
            <form method="POST" action="exportar_dados.php">
                <label for="data_inicio">Data Início:</label>
                <input type="date" name="data_inicio" value="<?php echo date('Y-m-d', strtotime('-7 days')); ?>" required>
                <label for="data_fim">Data Fim:</label>
                <input type="date" name="data_fim" value="<?php echo date('Y-m-d'); ?>" required><br>

                <label for="formato">Formato:</label>
                <select name="formato" required>
                    <option value="csv">CSV</option>
                    <option value="json">JSON</option>
                </select><br>

                <button type="submit" name="exportar_logs">Exportar Logs</button>
            </form>
            <br>
        <?php endif; ?>

        <!-- Log de Exportações -->
        <h3>Últimas Exportações (Log)</h3>
        <table border="1">
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th>Tipo de Dados</th>
                    <th>Formato</th>
                    <th>Data da Exportação</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($logs)): ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($log['nome_usuario']); ?></td>
                            <td><?php echo htmlspecialchars($log['tipo_dados']); ?></td>
                            <td><?php echo htmlspecialchars($log['formato']); ?></td>
                            <td><?php echo date('d/m/Y H:i:s', strtotime($log['data_exportacao'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">Nenhuma exportação registrada.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
</body>

</html>