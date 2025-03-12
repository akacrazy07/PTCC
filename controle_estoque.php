<?php
session_start ();
if (!isset ($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: login.html");
    exit();
}
require_once 'conexao.php';
$sql_produtos = "SELECT COUNT(*) as total_produtos, SUM(quantidade) as total_estoque FROM produtos";
$resultado_produtos = $conexao->query($sql_produtos);
$dados_produtos = $resultado_produtos->fetch_assoc();

$sql_vendas = "SELECT SUM(quantidade_vendida * preco_unitario_venda) as total_vendas FROM vendas WHERE DATE(data_venda) = CURDATE()";
$resultado_vendas = $conexao->query($sql_vendas);
$dados_vendas = $resultado_vendas->fetch_assoc();

//query para produtos com estoque baixo

$sql_estoque_baixo = "SELECT nome_produto, quantidade, estoque_minimo FROM produtos WHERE quantidade < estoque_minimo";
$resultado_estoque_baixo = $conexao->query($sql_estoque_baixo);
$produtos_baixo = [];
while ($produto = $resultado_estoque_baixo->fetch_assoc()) {
    $produtos_baixo[] = $produto;
}
$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Controle de estoque panificadora </title>
        <link rel="stylesheet" href="style.css">
    </head>
<body>
    <header>
        <h1>gestão de estoque - Panificadora </h1>
        <nav>
            <a href="controle_estoque.php">Dashboard</a>
            <a href="adicionar_produto.php">Adicionar Produto</a>
            <a href="listar_produtos.php">Listar Produtos</a>
            <a href="registrar_venda.php">Registrar Venda</a>
            <a href="logout.php">Sair</a>
        </nav>
    </header>
    <div class="container">
        <h2>Bem-vindo (a), <?php echo htmlspecialchars($_SESSION['nome_usuario']); ?>!</h2>
        <div class="dashboard-info">
            <p>Total de Produtos Cadastrados: <?php echo $dados_produtos['total_produtos']; ?></p>
            <p>Quantidade em Estoque: <?php echo $dados_produtos['total_estoque'] ?? 0; ?></p>
            <p>Vendas hoje: R$ <?php echo number_format($dados_vendas['total_vendas'] ?? 0, 2, ',', '.'); ?></p>
        </div>
        <?php if (!empty($produtos_baixo)): ?>
            <div class="alertas-estoque">
                <h3>Alertas de Estoque baixo</h3>
                <ul>
                    <?php foreach ($produtos_baixo as $produto): ?>
                        <li><?php echo htmlspecialchars($produto['nome_produto']) . ": " . $produto['quantidade'] . " (mínimo: " . $produto['estoque_minimo'] . ")"; ?></li>
                    <?php endforeach; ?>
                </ul>
    </div>
<?php endif; ?>
</div>
</body>
</html>