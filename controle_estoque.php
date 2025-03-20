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

// produtos com estoque baixo (avisar na dashboard)

$sql_estoque_baixo = "SELECT nome_produto, quantidade, estoque_minimo FROM produtos WHERE quantidade < estoque_minimo";
$resultado_estoque_baixo = $conexao->query($sql_estoque_baixo);
$produtos_baixo = [];
while ($produto = $resultado_estoque_baixo->fetch_assoc()) {
    $produtos_baixo[] = $produto;
}

// produtos próximos do vencimento (menos de 7 dias)
$sql_validade = "SELECT nome_produto, data_validade FROM produtos WHERE data_validade IS NOT NULL AND DATEDIFF(data_validade, CURDATE()) <= 7 AND data_validade >= CURDATE()";
$resultado_validade = $conexao->query($sql_validade);
$produtos_validade = [];
while ($produto = $resultado_validade->fetch_assoc()) {
    $produtos_validade[] = $produto;
}

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
        <h1>Gestão de Estoque - Panificadora </h1>
        <nav>
        <a href="controle_estoque.php">Dashboard</a>
            <?php if (in_array($_SESSION['perfil'], ['admin', 'gerente'])): ?>
                <a href="adicionar_produto.php">Adicionar Produto</a>
            <?php endif; ?>
            <a href="listar_produtos.php">Listar Produtos</a>
            <a href="registrar_venda.php">Registrar Venda</a>
            <?php if (in_array($_SESSION['perfil'], ['admin', 'gerente'])): ?>
                <a href="relatorios.php">Relatórios</a>
                <a href="receitas.php">Receitas</a>
                <a href="desperdicio.php">Desperdício</a>
            <?php endif; ?>
            <?php if ($_SESSION['perfil'] === 'admin'): ?>
                <a href="gerenciar_usuarios.php">Gerenciar Usuários</a>
            <?php endif; ?>
            <a href="logout.php">Sair</a>
        </nav>
    </header>
    <div class="container">
        <h2>Bem-vindo(a), <?php echo htmlspecialchars($_SESSION['nome_usuario']); ?>!</h2>
        <div class="dashboard-info">
            <p>Total de Produtos Cadastrados: <?php echo $dados_produtos['total_produtos']; ?></p>
            <p>Quantidade em Estoque: <?php echo $dados_produtos['total_estoque'] ?? 0; ?></p>
            <?php if (in_array($_SESSION['perfil'], ['admin', 'gerente'])): ?>
                <p>Vendas Hoje: R$ <?php echo number_format($dados_vendas['total_vendas'] ?? 0, 2, ',', '.'); ?></p>
            <?php endif; ?>
        </div>
        <?php if (!empty($produtos_baixo)): ?>
            <div class="alertas-estoque">
                <h3>Alertas de Estoque Baixo</h3>
                <ul>
                    <?php foreach ($produtos_baixo as $produto): ?>
                        <li><?php echo htmlspecialchars($produto['nome_produto']) . ": " . $produto['quantidade'] . " (mínimo: " . $produto['estoque_minimo'] . ")"; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php if (!empty($produtos_validade)): ?>
            <div class="alertas-validade">
                <h3>Alertas de Validade Próxima</h3>
                <ul>
                    <?php foreach ($produtos_validade as $produto): ?>
                        <?php $dias_restantes = (new DateTime())->diff(new DateTime($produto['data_validade']))->days; ?>
                        <li><?php echo htmlspecialchars($produto['nome_produto']) . ": Vence em " . date('d/m/Y', strtotime($produto['data_validade'])) . " (faltam $dias_restantes dias)"; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php if ($_SESSION['perfil'] === 'admin'): ?>
            <div class="alertas-backup">
                <h3>Alerta de Backup</h3>
                <p>Lembrete diário: Faça o backup do sistema hoje!</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>