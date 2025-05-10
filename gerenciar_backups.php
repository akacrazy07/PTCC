<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true || $_SESSION['perfil'] !== 'admin') {
    header("Location: login.html");
    exit();
}
require_once 'conexao.php';
require_once 'funcoes.php';

// Diretório para salvar os backups
$backup_dir = 'backups/';
if (!is_dir($backup_dir)) {
    if (!mkdir($backup_dir, 0777, true)) {
        die("Erro: Não foi possível criar o diretório 'backups/'. Verifique as permissões.");
    }
}

// Caminho completo para mysqldump e mysql 
$mysqldump_path = '"C:\xampp\mysql\bin\mysqldump.exe"'; 
$mysql_path = '"C:\xampp\mysql\bin\mysql.exe"';

// Realizar backup
if (isset($_POST['realizar_backup'])) {
    $backup_file = $backup_dir . 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    // Escapar a senha para evitar problemas com caracteres especiais
    $db_senha_escaped = str_replace('"', '\\"', $db_senha);
    $command = "$mysqldump_path --host=$db_host --user=$db_usuario --password=\"$db_senha_escaped\" --databases $db_nome > \"$backup_file\" 2>&1";
    exec($command, $output, $return_var);

    if ($return_var === 0) {
        $mensagem = "Backup realizado com sucesso! Arquivo: " . basename($backup_file);
        $acao = "Fez backup do banco de dados.";
        registrarLog($conexao, $_SESSION['usuario_id'], $acao);
    } else {
        $mensagem = "Erro ao realizar o backup. Detalhes: " . implode("\n", $output);
    }
}

// Restaurar backup
if (isset($_POST['restaurar_backup'])) {
    $backup_file = $_POST['backup_file'];
    // Verificar se o arquivo existe
    if (!file_exists($backup_file)) {
        $mensagem = "Erro: O arquivo de backup não existe.";
    } else {
        // Escapar a senha para evitar problemas com caracteres especiais
        $db_senha_escaped = str_replace('"', '\\"', $db_senha);
        $command = "$mysql_path --host=$db_host --user=$db_usuario --password=\"$db_senha_escaped\" $db_nome < \"$backup_file\" 2>&1";
        exec($command, $output, $return_var);

        if ($return_var === 0) {
            $mensagem = "Backup restaurado com sucesso!";
            $acao = "Restaurou o backup do banco de dados: " . basename($backup_file);
            registrarLog($conexao, $_SESSION['usuario_id'], $acao);
        } else {
            $mensagem = "Erro ao restaurar o backup. Detalhes: " . implode("\n", $output);
        }
    }
}

// Listar backups
$backups = glob($backup_dir . '*.sql');
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Backups - Panificadora</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'navbar.php'; ?>
    <div class="container">
        <?php if (isset($mensagem)): ?>
            <p><?php echo htmlspecialchars($mensagem); ?></p>
        <?php endif; ?>

        <!-- Formulário para realizar backup -->
        <h3>Realizar Backup</h3>
        <form method="POST" action="gerenciar_backups.php">
            <button type="submit" class="btn btn-primary" name="realizar_backup">Realizar Backup Agora</button>
        </form>

        <!-- Formulário para restaurar backup -->
        <h3>Restaurar Backup</h3>
        <form method="POST" action="gerenciar_backups.php">
            <label for="backup_file">Selecione o Backup:</label>
            <select name="backup_file" required>
                <option value="">Selecione um arquivo</option>
                <?php foreach ($backups as $backup): ?>
                    <option value="<?php echo $backup; ?>"><?php echo basename($backup); ?></option>
                <?php endforeach; ?>
            </select><br>
            <button type="submit" class="btn btn-primary" name="restaurar_backup">Restaurar Backup</button>
        </form>

        <!-- Listar backups -->
        <h3>Backups Disponíveis</h3>
        <ul>
            <?php if (!empty($backups)): ?>
                <?php foreach ($backups as $backup): ?>
                    <li><?php echo basename($backup); ?> (<?php echo date('d/m/Y H:i:s', filemtime($backup)); ?>)</li>
                <?php endforeach; ?>
            <?php else: ?>
                <li>Nenhum backup disponível.</li>
            <?php endif; ?>
        </ul>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
</body>
</html>