<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true || !in_array($_SESSION['perfil'], ['admin', 'gerente'])) {
    header("Location: login.html");
    exit();
}
require_once 'conexao.php';

// cadastrar ou atualizar fornecedor
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $contato = $_POST['contato'];
    $endereco = !empty($_POST['endereco']) ? $_POST['endereco'] : null;
    $id = !empty($_POST['id']) ? $_POST['id'] : null;

    if ($id) {
        // atualizar fornecedor existente
        $sql = "UPDATE fornecedores SET nome = ?, contato = ?, endereco = ? WHERE id = ?";
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("sssi", $nome, $contato, $endereco, $id);
    } else {
        // cadastrar novo fornecedor
        $sql = "INSERT INTO fornecedores (nome, contato, endereco) VALUES (?, ?, ?)";
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("sss", $nome, $contato, $endereco);
    }
    $stmt->execute();
    $stmt->close();
    header("Location: gerenciar_fornecedores.php");
    exit();
}

// excluir fornecedor
if (isset($_GET['excluir'])) {
    $id = $_GET['excluir'];
    $sql = "DELETE FROM fornecedores WHERE id = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: gerenciar_fornecedores.php");
    exit();
}

// listar fornecedores
$sql_fornecedores = "SELECT * FROM fornecedores";
$resultado_fornecedores = $conexao->query($sql_fornecedores);
$fornecedores = [];
while ($row = $resultado_fornecedores->fetch_assoc()) {
    $fornecedores[] = $row;
}

$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Fornecedores - Panificadora</title>
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
        <h2>Gerenciar Fornecedores</h2>

        <!-- Formulário para cadastrar/editar fornecedor -->
        <form method="POST" action="gerenciar_fornecedores.php">
            <input type="hidden" name="id" value="">
            <label for="nome">Nome do Fornecedor:</label>
            <input type="text" name="nome" required><br>

            <label for="contato">Contato:</label>
            <input type="text" name="contato" required placeholder="Ex.: (11) 99999-9999 ou email@exemplo.com"><br>

            <label for="endereco">Endereço (opcional):</label>
            <textarea name="endereco"></textarea><br>

            <button type="submit">Salvar Fornecedor</button>
        </form>

        <!-- Listar fornecedores -->
        <h3>Fornecedores Cadastrados</h3>
        <table border="1">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Contato</th>
                    <th>Endereço</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($fornecedores)): ?>
                    <?php foreach ($fornecedores as $fornecedor): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($fornecedor['nome']); ?></td>
                            <td><?php echo htmlspecialchars($fornecedor['contato']); ?></td>
                            <td><?php echo $fornecedor['endereco'] ? htmlspecialchars($fornecedor['endereco']) : '-'; ?></td>
                            <td>
                                <a href="editar_fornecedor.php?id=<?php echo $fornecedor['id']; ?>">Editar</a> |
                                <a href="gerenciar_fornecedores.php?excluir=<?php echo $fornecedor['id']; ?>" 
                                   onclick="return confirm('Tem certeza que deseja excluir este fornecedor?')">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">Nenhum fornecedor cadastrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>