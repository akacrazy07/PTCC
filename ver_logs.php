<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true || $_SESSION['perfil'] !== 'admin') {
    header("Location: login.html");
    exit();
}
require_once 'conexao.php';

// Buscar logs com informações do usuário
$sql = "SELECT l.id, l.usuario_id, u.usuario, l.acao, l.data_acao 
        FROM logs l 
        LEFT JOIN usuarios u ON l.usuario_id = u.id 
        ORDER BY l.data_acao DESC";
$resultado = $conexao->query($sql);
$logs = $resultado->fetch_all(MYSQLI_ASSOC);

$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs de Ações - Panificadora</title>
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
    <?php endif; ?>
    <?php if ($_SESSION['perfil'] === 'admin'): ?>
        <a href="gerenciar_usuarios.php">Gerenciar Usuários</a>
        <a href="ver_logs.php">Ver Logs</a>
    <?php endif; ?>
    <a href="logout.php">Sair</a>
        </nav>
    </header>
    <div class="container">
        <h2>Logs de Ações</h2>
        <?php if (empty($logs)): ?>
            <p>Nenhum log registrado ainda.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuário</th>
                        <th>Ação</th>
                        <th>Data e Hora</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($log['id']); ?></td>
                            <td><?php echo htmlspecialchars($log['usuario'] ?? 'Usuário Desconhecido'); ?></td>
                            <td><?php echo htmlspecialchars($log['acao']); ?></td>
                            <td><?php echo date('d/m/Y H:i:s', strtotime($log['data_acao'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>