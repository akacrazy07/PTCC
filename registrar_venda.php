<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: login.html");
    exit();
}
require_once 'conexao.php';

$mensagem_venda = '';
$produtos_estoque = [];

$stmt = $conexao->prepare ("SELECT id, nome_produto, preco, quantidade, estoque_minimo FROM produtos WHERE quantidade > 0");
$stmt->execute();
$resultado_produtos = $stmt->get_result();
while ($produto = $resultado_produtos->fetch_assoc()) {
    $produtos_estoque[] = $produto;
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['produtos_vendidos']) && isset($_POST['selecionar_produto'])) {
    $produtos_vendidos = $_POST['produtos_vendidos'];
    $selecionados = $_POST['selecionar_produto'];
    $lucro_total = 0;
    $erro = false;

    foreach ($produtos_vendidos as $produto_id =>$quantidade_vendida) {
        $produto_id = intval($produto_id);
        $quantidade_vendida = intval($quantidade_vendida);
        // verifica se o produto foi selecionado e se a quantidade vendida é maior que zero
        if (isset($selecionados[$produto_id]) && $quantidade_vendida > 0) {
            $stmt = $conexao->prepare("SELECT preco, quantidade, nome_produto, estoque_minimo FROM produtos WHERE id = ?");
            $stmt->bind_param('i', $produto_id);
            $stmt->execute();
            $resultado_produto = $stmt->get_result();
            if ($produto = $resultado_produto->fetch_assoc()) {
                $preco_unitario = $produto['preco'];
                $estoque_atual = $produto['quantidade'];
                $estoque_minimo = $produto['estoque_minimo'];
                if ($quantidade_vendida <= $estoque_atual) {
                    $stmt_venda = $conexao->prepare("INSERT INTO vendas (produto_id, quantidade_vendida, preco_unitario_venda) VALUES (?, ?, ?)");
                    $stmt_venda->bind_param('iid', $produto_id, $quantidade_vendida, $preco_unitario);
                    if ($stmt_venda->execute()) {
                        $novo_estoque = $estoque_atual - $quantidade_vendida;
                        $stmt_update = $conexao->prepare("UPDATE produtos SET quantidade = ? WHERE id = ?");
                        $stmt_update->bind_param('ii', $novo_estoque, $produto_id);
                        if ($stmt_update->execute()) {
                            $lucro_total += $preco_unitario * $quantidade_vendida;
                            if ($novo_estoque < $estoque_minimo) {
                                $mensagem_venda .= "Alerta: Estoque de" . htmlspecialchars($produto['nome_produto']) . " está abaixo do mínimo ($novo_estoque < $estoque_minimo)! ";
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
                    $mensagem_venda = "Estoque insuficiente para o produto:" . htmlspecialchars($produto['nome_produto']);
                    $erro = true;
                    break;
                }
            }
            $stmt->close();
        }
    }
    if (!$erro && $lucro_total > 0) {
        $mensagem_venda = "Venda registrada com sucesso. Lucro total: R$ " . number_format($lucro_total, 2, ',', '.');
    } elseif (!$erro) {
        $mensagem_venda = "Nenhum produto ou quantidade selecionado para venda.";
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
            <a href="adicionar_produto.php">Adicionar Produto</a>
            <a href="listar_produtos.php">Listar Produtos</a>
            <a href="registrar_venda.php">Registrar Venda</a>
            <a href="relatorios.php">Relatórios</a>
            <a href="receitas.php">Receitas</a>
            <a href="desperdicio.php">Desperdício</a>
            <a href="logout.php">Sair</a>
        </nav>
    </header>
    <div class="container">
        <h2>Registrar Venda</h2>
        <?php if (!empty($mensagem_venda)): ?>
            <p><?php echo $mensagem_venda; ?></p>
        <?php endif; ?>
        <form action="registrar_venda.php" method="post">
            <?php if (empty($produtos_estoque)): ?>
                <p>Nenhum produto em estoque para vender.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Preço Unitário</th>
                            <th>Estoque Atual</th>
                            <th>Quantidade Vendida</th>
                            <th>Selecionar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produtos_estoque as $produto): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($produto['nome_produto']); ?></td>
                                <td>R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></td>
                                <td><?php echo htmlspecialchars($produto['quantidade']); ?></td>
                                <td>
                                    <input type="number" name="produtos_vendidos[<?php echo $produto['id']; ?>]" value="0" min="0" max="<?php echo $produto['quantidade']; ?>">
                                </td>
                                <td>
                                    <input type="checkbox" name="selecionar_produto[<?php echo $produto['id']; ?>]" value="1">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="submit">Registrar Venda</button>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>