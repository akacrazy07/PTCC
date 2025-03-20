<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true ||
!in_array($_SESSION['perfil'], ['admin', 'gerente'])) {
    header("Location: login.html");
    exit();
}
require_once 'conexao.php';

// total de vendas (no dia atual)
$sql_vendas_hoje = "SELECT SUM(quantidade_vendida * preco_unitario_venda) AS total_hoje FROM vendas WHERE DATE(data_venda) = CURDATE()";
$resultado_vendas_hoje = $conexao->query($sql_vendas_hoje);
$total_hoje = $resultado_vendas_hoje->fetch_assoc()['total_hoje'] ?? 0;

// produtos mais vendidos (na última semana)
$sql_mais_vendidos = "SELECT p.nome_produto, SUM(v.quantidade_vendida) AS total_vendido FROM vendas v JOIN produtos p ON v.produto_id = p.id WHERE v.data_venda >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY v.produto_id, p.nome_produto ORDER BY total_vendido DESC LIMIT 5";
$resultado_mais_vendidos = $conexao->query($sql_mais_vendidos);
$mais_vendidos = [];
while ($row = $resultado_mais_vendidos->fetch_assoc()) {
$mais_vendidos[] = $row;
}
$conexao->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios de Vendas </title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Gestão de Estoque - Panificadora</h1>
        <nav>
            <a href="controle_estoque.php">Dashboard</a>
            <?php if (in_array($_SESSION['perfil'], ['admin', 'gerente'])): ?>
                <a href="adicionar_produto.php">Adicionar Produto</a>
                <a href="listar_produtos.php">Listar Produtos</a>
            <?php endif; ?>
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
        <h2>Relatórios de Vendas</h2>
        <div class="relatorio-section">
            <h3>Total de Vendas Hoje</h3>
            <p>R$ <?php echo number_format($total_hoje, 2, ',', '.'); ?></p>
        </div>
        <div class="relatorio-section">
            <h3>Produtos Mais Vendidos (Últimos 7 Dias)</h3>
            <?php if (empty($mais_vendidos)): ?>
                <p>Nenhuma venda registrada nos últimos 7 dias.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Quantidade Vendida</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mais_vendidos as $produto): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($produto['nome_produto']); ?></td>
                                <td><?php echo $produto['total_vendido']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>