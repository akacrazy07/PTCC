<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true || !in_array($_SESSION['perfil'], ['admin', 'gerente'])) {
    header("Location: login.html");
    exit();
}
require_once 'conexao.php';

// Carregar produtos para o filtro
$sql_produtos = "SELECT id, nome_produto FROM produtos";
$resultado_produtos = $conexao->query($sql_produtos);
$produtos = $resultado_produtos->fetch_all(MYSQLI_ASSOC);

// Carregar histórico de preços
$historico = [];
$produto_id_filtro = isset($_POST['produto_id']) ? intval($_POST['produto_id']) : 0;

// Ajustei 'u.nome' para 'u.usuario', pois a coluna correta em 'usuarios' é 'usuario'
$sql_historico = "SELECT h.*, p.nome_produto, u.usuario as nome_usuario 
                 FROM historico_precos h 
                 JOIN produtos p ON h.produto_id = p.id 
                 JOIN usuarios u ON h.usuario_id = u.id";
if ($produto_id_filtro) {
    $sql_historico .= " WHERE h.produto_id = ?";
    $stmt = $conexao->prepare($sql_historico);
    $stmt->bind_param('i', $produto_id_filtro);
    $stmt->execute();
    $resultado_historico = $stmt->get_result();
} else {
    $resultado_historico = $conexao->query($sql_historico);
}

while ($row = $resultado_historico->fetch_assoc()) {
    $historico[] = $row;
}

$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Preços - Panificadora</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'navbar.php'; ?>
    <div class="container">
        <div class="filter-section">
            <form method="POST" action="historico_precos.php">
                <label for="produto_id">Filtrar por Produto:</label>
                <select name="produto_id">
                    <option value="">Todos os Produtos</option>
                    <?php foreach ($produtos as $produto): ?>
                        <option value="<?php echo $produto['id']; ?>" <?php echo $produto_id_filtro == $produto['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($produto['nome_produto']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </form>
        </div>

        <?php if (empty($historico)): ?>
            <p>Nenhum histórico de preços encontrado.</p>
        <?php else: ?>
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Preço Antigo</th>
                        <th>Preço Novo</th>
                        <th>Data da Alteração</th>
                        <th>Usuário</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historico as $registro): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($registro['nome_produto']); ?></td>
                            <td>R$ <?php echo number_format($registro['preco_antigo'], 2, ',', '.'); ?></td>
                            <td>R$ <?php echo number_format($registro['preco_novo'], 2, ',', '.'); ?></td>
                            <td><?php echo date('d/m/Y H:i:s', strtotime($registro['data_alteracao'])); ?></td>
                            <td><?php echo htmlspecialchars($registro['nome_usuario']); ?></td>
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