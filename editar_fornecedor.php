<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true || !in_array($_SESSION['perfil'], ['admin', 'gerente'])) {
    header("Location: login.html");
    exit();
}
require_once 'conexao.php';

$id = $_GET['id'];
$sql = "SELECT * FROM fornecedores WHERE id = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();
$fornecedor = $resultado->fetch_assoc();
$stmt->close();

$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Fornecedor - Panificadora</title>
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
                <a href="gerenciar_fornecedores.php">Gerenciar Fornecedores</a>
                <a href="gerenciar_promocoes.php">Gerenciar Promoções</a>
                <a href="editar_promocao.php">Editar Promoções</a>
                <a href="gerenciar_usuarios.php">Gerenciar Usuários</a>
                <a href="ver_logs.php">Ver Logs</a>
            <?php endif; ?>
            <a href="logout.php">Sair</a>
        </nav>
    </header>
    <div class="container">
        <h2>Editar Fornecedor</h2>
        <form method="POST" action="gerenciar_fornecedores.php">
            <input type="hidden" name="id" value="<?php echo $fornecedor['id']; ?>">
            <label for="nome">Nome do Fornecedor:</label>
            <input type="text" name="nome" value="<?php echo htmlspecialchars($fornecedor['nome']); ?>" required><br>

            <label for="contato">Contato:</label>
            <input type="text" name="contato" value="<?php echo htmlspecialchars($fornecedor['contato']); ?>" required><br>

            <label for="endereco">Endereço (opcional):</label>
            <textarea name="endereco"><?php echo htmlspecialchars($fornecedor['endereco'] ?? ''); ?></textarea><br>

            <button type="submit">Salvar Alterações</button>
        </form>
    </div>
</body>
</html>