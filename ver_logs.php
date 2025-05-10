<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true || $_SESSION['perfil'] !== 'admin') {
    header("Location: login.html");
    exit();
}
require_once 'conexao.php';

// Inicializar variáveis para os filtros
$categoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$usuario_id = isset($_GET['usuario_id']) ? $_GET['usuario_id'] : '';

// Buscar categorias disponíveis (perfis)
$categorias = ['admin', 'gerente', 'vendedor'];

// Buscar usuários com base na categoria selecionada
$usuarios = [];
if (!empty($categoria)) {
    $sql_usuarios = "SELECT id, usuario FROM usuarios WHERE perfil = ? ORDER BY usuario";
    $stmt_usuarios = $conexao->prepare($sql_usuarios);
    $stmt_usuarios->bind_param("s", $categoria);
    $stmt_usuarios->execute();
    $resultado_usuarios = $stmt_usuarios->get_result();
    $usuarios = $resultado_usuarios->fetch_all(MYSQLI_ASSOC);
    $stmt_usuarios->close();
}

// Buscar logs com base nos filtros
$sql = "SELECT l.id, l.usuario_id, u.usuario, u.perfil, l.acao, l.data_acao 
        FROM logs l 
        LEFT JOIN usuarios u ON l.usuario_id = u.id 
        WHERE 1=1";
$params = [];
$types = "";

if (!empty($categoria)) {
    $sql .= " AND u.perfil = ?";
    $params[] = $categoria;
    $types .= "s";
}

if (!empty($usuario_id) && $usuario_id !== 'todos') {
    $sql .= " AND l.usuario_id = ?";
    $params[] = $usuario_id;
    $types .= "i";
}

$sql .= " ORDER BY l.data_acao DESC";

$stmt = $conexao->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resultado = $stmt->get_result();
$logs = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs de Ações - Panificadora</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .filter-group {
            margin-bottom: 20px;
        }

        .filter-group select {
            width: 200px;
            margin-right: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>
    <div class="container mt-4">
        <h2>Logs de Ações</h2>

        <!-- Filtros -->
        <div class="filter-group">
            <form method="GET" action="ver_logs.php">
                <label for="categoria">Categoria:</label>
                <select id="categoria" name="categoria" onchange="this.form.submit()">
                    <option value="">Todas</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo $cat; ?>" <?php echo $categoria === $cat ? 'selected' : ''; ?>>
                            <?php echo ucfirst($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <?php if (!empty($categoria)): ?>
                    <label for="usuario_id">Usuário:</label>
                    <select id="usuario_id" name="usuario_id" onchange="this.form.submit()">
                        <option value="todos" <?php echo $usuario_id === 'todos' ? 'selected' : ''; ?>>Todos</option>
                        <?php foreach ($usuarios as $usuario): ?>
                            <option value="<?php echo $usuario['id']; ?>" <?php echo $usuario_id === $usuario['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($usuario['usuario']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </form>
        </div>

        <!-- Tabela de Logs -->
        <?php if (empty($logs)): ?>
            <p>Nenhum log registrado ainda.</p>
        <?php else: ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuário</th>
                        <th>Categoria</th>
                        <th>Ação</th>
                        <th>Data e Hora</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($log['id']); ?></td>
                            <td><?php echo htmlspecialchars($log['usuario'] ?? 'Usuário Desconhecido'); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($log['perfil'] ?? 'Desconhecido')); ?></td>
                            <td><?php echo htmlspecialchars($log['acao']); ?></td>
                            <td><?php echo date('d/m/Y H:i:s', strtotime($log['data_acao'])); ?></td>
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