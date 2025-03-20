<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true || $_SESSION['perfil'] !== 'admin') {
    header("Location: login.html");
    exit();
}
require_once 'conexao.php';

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['adicionar'])) {
    $usuario = $_POST['usuario'];
    $cpf = $_POST['cpf'];
    $email = $_POST['email'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $perfil = $_POST['perfil'];

    $stmt = $conexao->prepare("INSERT INTO usuarios (usuario, cpf, email, senha, perfil) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $usuario, $cpf, $email, $senha, $perfil);
    if ($stmt->execute()) {
        $mensagem = "Usuário adicionado com sucesso!";
        // registrar log de ação
        $usuario_id = $_SESSION['usuario_id'];
        $acao = "Adicionou usuário '$usuario' ($perfil)";
        $stmt_log = $conexao->prepare("INSERT INTO logs (usuario_id, acao) VALUES (?, ?)");
        $stmt_log->bind_param("is", $usuario_id, $acao);
        $stmt_log->execute();
        $stmt_log->close();
    } else {
        $mensagem = "Erro ao adicionar usuário: " . $conexao->error;
    }
    $stmt->close();
}
// excluir usuário
if (isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    $id_excluir = intval($_GET['excluir']);
    $stmt = $conexao->prepare("SELECT usuario, perfil FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id_excluir);
    $stmt->execute();
    $usuario_excluido = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $conexao->prepare("DELETE FROM usuarios WHERE id = ? AND perfil != 'admin'");
    $stmt->bind_param("i", $id_excluir);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $mensagem = "Usuário excluído com sucesso!";
        // registrar log de ação de exclusão
        $usuario_id = $_SESSION['usuario_id'];
        $acao = "Excluiu usuário '{$usuario_excluido['usuario']}' ({$usuario_excluido['perfil']})";
        $stmt_log = $conexao->prepare("INSERT INTO logs (usuario_id, acao) VALUES (?, ?)");
        $stmt_log->bind_param("is", $usuario_id, $acao);
        $stmt_log->execute();
        $stmt_log->close();
    } else {
        $mensagem = "Erro ao excluir usuário ou usuário é admin.";
    }
    $stmt->close();
}

// editar usuários
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editar'])) {
    $id = intval($_POST['id']);
    $usuario = $_POST['usuario'];
    $cpf = $_POST['cpf'];
    $email = $_POST['email'];
    $perfil = $_POST['perfil'];
    $senha = !empty($_POST['senha']) ? password_hash($_POST['senha'], PASSWORD_DEFAULT) : null;

    $sql = "UPDATE usuarios SET usuario = ?, cpf = ?, email = ?, perfil = ?";
    if ($senha) {
        $sql .= ", senha = ?";
    }
    $sql .= " WHERE id = ? AND perfil != 'admin'";
    $stmt = $conexao->prepare($sql);
    if ($senha) {
        $stmt->bind_param("sssssi", $usuario, $cpf, $email, $perfil, $senha, $id);
    } else {
        $stmt->bind_param("ssssi", $usuario, $cpf, $email, $perfil, $id);
    }
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $mensagem = "Usuário atualizado com sucesso!";
        $usuario_id = $_SESSION['usuario_id'];
        $acao = "Editou usuário '$usuario' ($perfil)";
        $stmt_log = $conexao->prepare("INSERT INTO logs (usuario_id, acao) VALUES (?, ?)");
        $stmt_log->bind_param("is", $usuario_id, $acao);
        $stmt_log->execute();
        $stmt_log->close();
    } else {
        $mensagem = "Erro ao editar usuário ou usuário é admin.";
    }
    $stmt->close();
}

// listar usuários existentes
$usuarios = $conexao->query("SELECT id, usuario, cpf, email, perfil FROM usuarios")->fetch_all(MYSQLI_ASSOC);
$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Panificadora</title>
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
            <?php if ($_SESSION['perfil'] === 'admin'): ?>
                <a href="gerenciar_usuarios.php">Gerenciar Usuários</a>
            <?php endif; ?>
            <a href="logout.php">Sair</a>
        </nav>
    </header>
    <div class="container">
        <h2>Gerenciar Usuários</h2>
        <?php if (!empty($mensagem)): ?>
            <p class="mensagem"><?php echo $mensagem; ?></p>
        <?php endif; ?>
        <h3>Adicionar Usuário</h3>
        <form action="gerenciar_usuarios.php" method="post">
            <input type="text" name="usuario" placeholder="Usuário" required>
            <input type="text" name="cpf" placeholder="CPF (ex: 123.456.789-00)" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="senha" placeholder="Senha" required>
            <select name="perfil" required>
                <option value="gerente">Gerente</option>
                <option value="vendedor">Vendedor</option>
            </select>
            <button type="submit" name="adicionar">Adicionar</button>
        </form>
        <h3>Usuários Cadastrados</h3>
        <?php if (empty($usuarios)): ?>
            <p>Nenhum usuário cadastrado.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Usuário</th>
                        <th>CPF</th>
                        <th>Email</th>
                        <th>Perfil</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($usuario['usuario']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['cpf']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['perfil']); ?></td>
                            <td>
                                <?php if ($usuario['perfil'] !== 'admin'): ?>
                                    <button onclick="document.getElementById('editar-<?php echo $usuario['id']; ?>').style.display='block'">Editar</button>
                                    <a href="gerenciar_usuarios.php?excluir=<?php echo $usuario['id']; ?>" class="btn btn-excluir" onclick="return confirm('Tem certeza que quer excluir este usuário?');">Excluir</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr id="editar-<?php echo $usuario['id']; ?>" style="display:none;">
                            <td colspan="5">
                                <form action="gerenciar_usuarios.php" method="post">
                                    <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">
                                    <input type="text" name="usuario" value="<?php echo htmlspecialchars($usuario['usuario']); ?>" required>
                                    <input type="text" name="cpf" value="<?php echo htmlspecialchars($usuario['cpf']); ?>" required>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                                    <select name="perfil" required>
                                        <option value="gerente" <?php echo $usuario['perfil'] === 'gerente' ? 'selected' : ''; ?>>Gerente</option>
                                        <option value="vendedor" <?php echo $usuario['perfil'] === 'vendedor' ? 'selected' : ''; ?>>Vendedor</option>
                                    </select>
                                    <input type="password" name="senha" placeholder="Nova senha (opcional)">
                                    <button type="submit" name="editar">Salvar</button>
                                    <button type="button" onclick="document.getElementById('editar-<?php echo $usuario['id']; ?>').style.display='none'">Cancelar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>