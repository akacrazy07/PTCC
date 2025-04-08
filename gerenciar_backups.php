<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true || $_SESSION['perfil'] !== 'admin') {
    header("Location: login.html");
    exit();
}
require_once 'conexao.php';

// configurações do backup
$backup_dir = 'backups/';
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}
$db_host = 'localhost'; // endereço do servidor de banco de dados
$db_user = 'root'; // usuário do banco de dados
$db_pass = ''; // senha do banco de dados, se houver
$db_name = 'panificadora_db'; // nome da database

// função pra realizar o backup
function realizarBackup($conexao, $backup_dir, $db_host, $db_user, $db_pass, $db_name, $usuario_id = null) {
    $data = date('Y-m-d_H-i-s');
    $arquivo = $backup_dir . "backup_$data.sql";

    // comando mysqldump pra gerar o backup
    $comando = "mysqldump --host=$db_host --user=$db_user --password=$db_pass $db_name > $arquivo";
    exec($comando, $output, $return_var);

    if ($return_var === 0) {
        // backup bem-sucedido, registrar no banco
        $sql = "INSERT INTO backups (data_backup, caminho_arquivo, usuario_id) VALUES (NOW(), ?, ?)";
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("si", $arquivo, $usuario_id);
        $stmt->execute();
        $stmt->close();
        return true;
    } else {
        return false;
    }
}

// fazer backup manual
if (isset($_POST['fazer_backup'])) {
    if (realizarBackup($conexao, $backup_dir, $db_host, $db_user, $db_pass, $db_name, $_SESSION['usuario_id'])) {
        header("Location: gerenciar_backups.php?sucesso=1");
    } else {
        header("Location: gerenciar_backups.php?erro=1");
    }
    exit();
}

// excluir backup
if (isset($_GET['excluir'])) {
    $id = $_GET['excluir'];
    $sql = "SELECT caminho_arquivo FROM backups WHERE id = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $backup = $resultado->fetch_assoc();
    $stmt->close();

    if ($backup && file_exists($backup['caminho_arquivo'])) {
        unlink($backup['caminho_arquivo']);
    }

    $sql = "DELETE FROM backups WHERE id = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: gerenciar_backups.php");
    exit();
}

// listar backups
$sql_backups = "SELECT b.*, u.nome as nome_usuario 
                FROM backups b 
                LEFT JOIN usuarios u ON b.usuario_id = u.id 
                ORDER BY b.data_backup DESC";
$resultado_backups = $conexao->query($sql_backups);
$backups = [];
while ($row = $resultado_backups->fetch_assoc()) {
    $backups[] = $row;
}

$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Backups - Panificadora</title>
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
                <a href="gerenciar_fornecedores.php">Gerenciar Fornecedores</a>
                <a href="exportar_dados.php">Exportar Dados</a>
            <?php endif; ?>
            <?php if ($_SESSION['perfil'] === 'admin'): ?>
                <a href="gerenciar_usuarios.php">Gerenciar Usuários</a>
                <a href="ver_logs.php">Ver Logs</a>
                <a href="gerenciar_backups.php">Gerenciar Backups</a>
            <?php endif; ?>
            <a href="logout.php">Sair</a>
        </nav>
    </header>
    <div class="container">
        <h2>Gerenciar Backups</h2>
        <?php if (isset($_GET['sucesso'])): ?>
            <p style="color: green;">Backup realizado com sucesso!</p>
        <?php endif; ?>
        <?php if (isset($_GET['erro'])): ?>
            <p style="color: red;">Erro ao realizar o backup. Verifique as configurações do servidor.</p>
        <?php endif; ?>

        <!-- fazer backup manual -->
        <form method="POST" action="gerenciar_backups.php">
            <button type="submit" name="fazer_backup">Fazer Backup Manual</button>
        </form>

        <!-- listar backups -->
        <h3>Backups Realizados</h3>
        <table border="1">
            <thead>
                <tr>
                    <th>Data do Backup</th>
                    <th>Realizado por</th>
                    <th>Arquivo</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($backups)): ?>
                    <?php foreach ($backups as $backup): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i:s', strtotime($backup['data_backup'])); ?></td>
                            <td><?php echo $backup['nome_usuario'] ?? 'Automático'; ?></td>
                            <td><?php echo basename($backup['caminho_arquivo']); ?></td>
                            <td>
                                <a href="<?php echo $backup['caminho_arquivo']; ?>" download>Baixar</a> |
                                <a href="gerenciar_backups.php?excluir=<?php echo $backup['id']; ?>" 
                                   onclick="return confirm('Tem certeza que deseja excluir este backup?')">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">Nenhum backup realizado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>