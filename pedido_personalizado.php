<?php
session_start();
if (
    !isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true ||
    !in_array($_SESSION['perfil'], ['vendedor', 'gerente', 'admin'])
) {
    header("Location: login.html");
    exit();
}
require_once 'conexao.php';
require_once 'funcoes.php';
$mensagem = '';

// Adicionar task
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['adicionar'])) {
    $nome_pedido = $_POST['nome_pedido'];
    $descricao = $_POST['descricao'];
    $data_entrega = $_POST['data_entrega'];
    $criado_por = $_SESSION['nome_usuario'];

    if (empty($nome_pedido) || empty($data_entrega)) {
        $mensagem = "Erro: Nome do pedido e data de entrega são obrigatórios!";
    } else {
        $stmt = $conexao->prepare("INSERT INTO tasks (nome_pedido, descricao, data_entrega, criado_por) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nome_pedido, $descricao, $data_entrega, $criado_por);
        if ($stmt->execute()) {
            $mensagem = "Task adicionada com sucesso!";
            $acao = "adicionou uma task: $nome_pedido";
            registrarLog($conexao, $_SESSION['usuario_id'], $acao);
        } else {
            $mensagem = "Erro ao adicionar task: " . $conexao->error;
        }
        $stmt->close();
    }
}

// Marcar task como concluída (apenas para gerente)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['concluir']) && $_SESSION['perfil'] === 'gerente') {
    $task_id = intval($_POST['task_id']);
    $stmt = $conexao->prepare("UPDATE tasks SET status = 'concluido' WHERE id = ?");
    $stmt->bind_param("i", $task_id);
    if ($stmt->execute()) {
        $mensagem = "Task marcada como concluída!";
    } else {
        $mensagem = "Erro ao concluir task: " . $conexao->error;
    }
    $stmt->close();
}

// Limpar tabela se ultrapassar 30 tasks
$sql_count = "SELECT COUNT(*) as total FROM tasks";
$resultado_count = $conexao->query($sql_count);
$total_tasks = $resultado_count->fetch_assoc()['total'];
if ($total_tasks > 30) {
    $sql_clear = "TRUNCATE TABLE tasks";
    $conexao->query($sql_clear);
    $mensagem = "Tabela de tasks foi limpa automaticamente (limite de 30 tasks excedido).";
}

// Listar tasks
$sql_tasks = "SELECT * FROM tasks ORDER BY criado_em DESC";
$resultado_tasks = $conexao->query($sql_tasks);
$tasks = [];
while ($row = $resultado_tasks->fetch_assoc()) {
    $tasks[] = $row;
}

$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos Personalizados - Panificadora</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <?php include 'navbar.php'; ?>
    <div class="container">
        <h3>Adicionar Pedido Personalizado</h3>
        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($mensagem); ?></div>
        <?php endif; ?>
        <form action="pedido_personalizado.php" method="post">
            <div class="mb-3">
                <label for="nome_pedido" class="form-label">Nome do Pedido</label>
                <input type="text" class="form-control" id="nome_pedido" name="nome_pedido" placeholder="Ex.: Bolo Personalizado" required>
            </div>
            <div class="mb-3">
                <label for="descricao" class="form-label">Descrição</label>
                <textarea class="form-control" id="descricao" name="descricao" placeholder="Detalhes do pedido"></textarea>
            </div>
            <div class="mb-3">
                <label for="data_entrega" class="form-label">Data de Entrega</label>
                <input type="date" class="form-control" id="data_entrega" name="data_entrega" required>
            </div>
            <button type="submit" name="adicionar" class="btn btn-primary">Adicionar Task</button>
        </form>

        <h3 class="mt-5">Tasks de Pedidos Personalizados</h3>
        <?php if (empty($tasks)): ?>
            <p>Nenhuma task cadastrada ainda.</p>
        <?php else: ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Nome do Pedido</th>
                        <th>Descrição</th>
                        <th>Data de Entrega</th>
                        <th>Status</th>
                        <th>Criado Por</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $task): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($task['nome_pedido']); ?></td>
                            <td><?php echo htmlspecialchars($task['descricao'] ?? 'Sem descrição'); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($task['data_entrega'])); ?></td>
                            <td><?php echo $task['status'] === 'pendente' ? 'Pendente' : 'Concluído'; ?></td>
                            <td><?php echo htmlspecialchars($task['criado_por']); ?></td>
                            <td>
                                <?php if ($task['status'] === 'pendente' && $_SESSION['perfil'] === 'gerente'): ?>
                                    <form action="pedido_personalizado.php" method="post" style="display:inline;">
                                        <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                        <button type="submit" name="concluir" class="btn btn-success btn-sm">Concluir</button>
                                    </form>
                                <?php endif; ?>
                            </td>
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