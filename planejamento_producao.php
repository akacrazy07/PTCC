<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true || !in_array($_SESSION['perfil'], ['admin', 'gerente'])) {
    header("Location: login.html");
    exit();
}
require_once 'conexao.php';

$mensagem = '';

// buscar produtos com estoque baixo ou vendas altas
$sql = "SELECT p.id, p.nome_produto, p.quantidade, p.estoque_minimo, 
               COALESCE(AVG(v.quantidade_vendida), 0) as media_vendas_diaria
        FROM produtos p
        LEFT JOIN vendas v ON p.id = v.produto_id 
            AND v.data_venda >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY p.id, p.nome_produto, p.quantidade, p.estoque_minimo
        HAVING p.quantidade < p.estoque_minimo OR media_vendas_diaria > 0";
$resultado = $conexao->query($sql);
$produtos = $resultado->fetch_all(MYSQLI_ASSOC);

// registrar produção
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['produzir'])) {
    $quantidades = $_POST['quantidade_produzir'];
    $erro = false;

    foreach ($quantidades as $produto_id => $quantidade) {
        $produto_id = intval($produto_id);
        $quantidade = intval($quantidade);
        if ($quantidade > 0) {
            $stmt = $conexao->prepare("UPDATE produtos SET quantidade = quantidade + ? WHERE id = ?");
            $stmt->bind_param("ii", $quantidade, $produto_id);
            if ($stmt->execute()) {
                $produto_nome = $conexao->query("SELECT nome_produto FROM produtos WHERE id = $produto_id")->fetch_assoc()['nome_produto'];
                $mensagem .= "Produção de $quantidade unidade(s) de '$produto_nome' registrada com sucesso! ";
                // registrar log
                $usuario_id = $_SESSION['usuario_id'];
                $acao = "Registrou produção de $quantidade unidade(s) de '$produto_nome'";
                $stmt_log = $conexao->prepare("INSERT INTO logs (usuario_id, acao) VALUES (?, ?)");
                $stmt_log->bind_param("is", $usuario_id, $acao);
                $stmt_log->execute();
                $stmt_log->close();
            } else {
                $mensagem = "Erro ao registrar produção: " . $conexao->error;
                $erro = true;
            }
            $stmt->close();
            if ($erro) break;
        }
    }
}

$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planejamento de Produção - Panificadora</title>
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
            <?php endif; ?>
            <?php if ($_SESSION['perfil'] === 'admin'): ?>
                <a href="gerenciar_fornecedores.php">Gerenciar Fornecedores</a>
                <a href="gerenciar_usuarios.php">Gerenciar Usuários</a>
                <a href="ver_logs.php">Ver Logs</a>
                <a href="exportar_dados.php">Exportar Dados</a>
                <a href="gerenciar_backups.php">Gerenciar Backups</a>
            <?php endif; ?>
            <a href="logout.php">Sair</a>
        </nav>
    </header>
    <div class="container">
        <h2>Planejamento de Produção</h2>
        <?php if (!empty($mensagem)): ?>
            <p class="mensagem"><?php echo $mensagem; ?></p>
        <?php endif; ?>
        <?php if (empty($produtos)): ?>
            <p>Nenhum produto precisa de produção no momento.</p>
        <?php else: ?>
            <form action="planejamento_producao.php" method="post">
                <table>
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Estoque Atual</th>
                            <th>Estoque Mínimo</th>
                            <th>Média Vendas/Dia (7 dias)</th>
                            <th>Sugestão de Produção</th>
                            <th>Quantidade a Produzir</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produtos as $produto): ?>
                            <?php
                            $sugestao = max(0, $produto['estoque_minimo'] - $produto['quantidade']) + ceil($produto['media_vendas_diaria'] * 2); // 2 dias de estoque extra
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($produto['nome_produto']); ?></td>
                                <td><?php echo htmlspecialchars($produto['quantidade']); ?></td>
                                <td><?php echo htmlspecialchars($produto['estoque_minimo']); ?></td>
                                <td><?php echo number_format($produto['media_vendas_diaria'], 2, ',', '.'); ?></td>
                                <td><?php echo $sugestao; ?></td>
                                <td>
                                    <input type="number" name="quantidade_produzir[<?php echo $produto['id']; ?>]" value="0" min="0">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="submit" name="produzir">Registrar Produção</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>