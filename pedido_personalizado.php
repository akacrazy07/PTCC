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
    $hora_entrega = $_POST['hora_entrega'] ?: null;
    $criado_por = $_SESSION['nome_usuario'];

    if (empty($nome_pedido) || empty($data_entrega)) {
        $mensagem = "Erro: Nome do pedido e data de entrega são obrigatórios!";
    } else {
        $stmt = $conexao->prepare("INSERT INTO tasks (nome_pedido, descricao, data_entrega, hora_entrega, criado_por) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $nome_pedido, $descricao, $data_entrega, $hora_entrega, $criado_por);
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

// Marcar task como concluída (apenas para gerente ou admin)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['concluir']) && in_array($_SESSION['perfil'], ['gerente', 'admin'])) {
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

// Editar task (apenas para gerente ou admin)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editar']) && in_array($_SESSION['perfil'], ['gerente', 'admin'])) {
    $task_id = intval($_POST['task_id']);
    $nome_pedido = $_POST['nome_pedido'];
    $descricao = $_POST['descricao'];
    $data_entrega = $_POST['data_entrega'];
    $hora_entrega = $_POST['hora_entrega'] ?: null;

    if (empty($nome_pedido) || empty($data_entrega)) {
        $mensagem = "Erro: Nome do pedido e data de entrega são obrigatórios!";
    } else {
        $stmt = $conexao->prepare("UPDATE tasks SET nome_pedido = ?, descricao = ?, data_entrega = ?, hora_entrega = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $nome_pedido, $descricao, $data_entrega, $hora_entrega, $task_id);
        if ($stmt->execute()) {
            $mensagem = "Task editada com sucesso!";
            $acao = "editou uma task: $nome_pedido";
            registrarLog($conexao, $_SESSION['usuario_id'], $acao);
        } else {
            $mensagem = "Erro ao editar task: " . $conexao->error;
        }
        $stmt->close();
    }
}

// Limpar tabela se ultrapassar 30 tasks ou se solicitado manualmente
$sql_count = "SELECT COUNT(*) as total FROM tasks";
$resultado_count = $conexao->query($sql_count);
$total_tasks = $resultado_count->fetch_assoc()['total'];
if ((isset($_POST['limpar_tabela']) && in_array($_SESSION['perfil'], ['gerente', 'admin'])) || $total_tasks > 30) {
    $sql_clear = "TRUNCATE TABLE tasks";
    $conexao->query($sql_clear);
    $mensagem = "Tabela de tasks foi limpa automaticamente (limite de 30 tasks excedido ou ação manual).";
}

// Cancelar task (apenas para gerente ou admin)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancelar']) && in_array($_SESSION['perfil'], ['gerente', 'admin'])) {
    $task_id = intval($_POST['task_id']);
    $stmt = $conexao->prepare("UPDATE tasks SET status = 'cancelado' WHERE id = ?");
    $stmt->bind_param("i", $task_id);
    if ($stmt->execute()) {
        $mensagem = "Task cancelada com sucesso!";
        $acao = "cancelou uma task: $task_id";
        registrarLog($conexao, $_SESSION['usuario_id'], $acao);
    } else {
        $mensagem = "Erro ao cancelar task: " . $conexao->error;
    }
    $stmt->close();
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
            <div class="mb-3">
                <label for="hora_entrega" class="form-label">Horário de Entrega</label>
                <input type="time" class="form-control" id="hora_entrega" name="hora_entrega">
            </div>
            <button type="submit" name="adicionar" class="btn btn-primary">Adicionar Task</button>
        </form>

        <!-- Modal de Edição -->
        <div class="modal fade" id="editarTaskModal" tabindex="-1" aria-labelledby="editarTaskModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editarTaskModalLabel">Editar Pedido Personalizado</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="pedido_personalizado.php" method="post">
                            <input type="hidden" name="task_id" id="edit_task_id">
                            <div class="mb-3">
                                <label for="edit_nome_pedido" class="form-label">Nome do Pedido</label>
                                <input type="text" class="form-control" id="edit_nome_pedido" name="nome_pedido" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_descricao" class="form-label">Descrição</label>
                                <textarea class="form-control" id="edit_descricao" name="descricao"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="edit_data_entrega" class="form-label">Data de Entrega</label>
                                <input type="date" class="form-control" id="edit_data_entrega" name="data_entrega" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_hora_entrega" class="form-label">Horário de Entrega</label>
                                <input type="time" class="form-control" id="edit_hora_entrega" name="hora_entrega">
                            </div>
                            <button type="submit" name="editar" class="btn btn-primary">Salvar Alterações</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Ver Mais -->
        <div class="modal fade" id="verMaisModal" tabindex="-1" aria-labelledby="verMaisModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="verMaisModalLabel">Descrição Completa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p id="ver_mais_descricao"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    </div>
                </div>
            </div>
        </div>

        <h3 class="mt-5">Tasks de Pedidos Personalizados</h3>
        <form action="pedido_personalizado.php" method="post" style="display:inline;">
            <button type="submit" name="limpar_tabela" class="btn btn-danger mb-3" onclick="return confirm('Tem certeza que deseja limpar todas as tasks?')">Limpar Tabela</button>
        </form>
        <?php if (empty($tasks)): ?>
            <p>Nenhuma task cadastrada ainda.</p>
        <?php else: ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Nome do Pedido</th>
                        <th>Descrição</th>
                        <th>Data de Entrega</th>
                        <th>Horário de Entrega</th>
                        <th>Status</th>
                        <th>Criado Por</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $task): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($task['nome_pedido']); ?></td>
                            <td>
                                <?php
                                $descricao = $task['descricao'] ?? 'Sem descrição';
                                if (strlen($descricao) > 10) {
                                    echo '<button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#verMaisModal" data-descricao="' . htmlspecialchars($descricao) . '">Ver Mais</button>';
                                } else {
                                    echo htmlspecialchars($descricao);
                                }
                                ?>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($task['data_entrega'])); ?></td>
                            <td><?php echo $task['hora_entrega'] ? date('H:i', strtotime($task['hora_entrega'])) : 'Não informado'; ?></td>
                            <td><?php echo $task['status'] === 'pendente' ? 'Pendente' : 'Concluído'; ?></td>
                            <td><?php echo htmlspecialchars($task['criado_por']); ?></td>
                            <td>
                                <?php if ($task['status'] === 'pendente' && in_array($_SESSION['perfil'], ['gerente', 'admin'])): ?>
                                    <form action="pedido_personalizado.php" method="post" style="display:inline;">
                                        <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                        <button type="submit" name="concluir" class="btn btn-success btn-sm">Concluir</button>
                                    </form>
                                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editarTaskModal"
                                        data-id="<?php echo $task['id']; ?>"
                                        data-nome="<?php echo htmlspecialchars($task['nome_pedido']); ?>"
                                        data-descricao="<?php echo htmlspecialchars($task['descricao'] ?? ''); ?>"
                                        data-data="<?php echo $task['data_entrega']; ?>"
                                        data-hora="<?php echo $task['hora_entrega'] ?? ''; ?>">
                                        Editar
                                    </button>
                                    <form action="pedido_personalizado.php" method="post" style="display:inline;">
                                        <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                        <button type="submit" name="cancelar" class="btn btn-warning btn-sm" onclick="return confirm('Tem certeza que deseja cancelar este pedido?')">Cancelar</button>
                                    </form>
                                <?php elseif ($task['status'] === 'cancelado'): ?>
                                    <span class="badge bg-warning text-dark">Cancelado</span>
                                <?php elseif ($task['status'] === 'concluido'): ?>
                                    <span class="badge bg-success">Concluído</span>
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
    <script>
        // Preencher o modal de edição com os dados da task
        document.addEventListener('DOMContentLoaded', function () {
            const editarTaskModal = document.getElementById('editarTaskModal');
            editarTaskModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget; // Botão que abriu o modal
                const id = button.getAttribute('data-id');
                const nome = button.getAttribute('data-nome');
                const descricao = button.getAttribute('data-descricao');
                const data = button.getAttribute('data-data');
                const hora = button.getAttribute('data-hora');

                // Preencher os campos do formulário
                const modal = this;
                modal.querySelector('#edit_task_id').value = id;
                modal.querySelector('#edit_nome_pedido').value = nome;
                modal.querySelector('#edit_descricao').value = descricao;
                modal.querySelector('#edit_data_entrega').value = data;
                modal.querySelector('#edit_hora_entrega').value = hora;
            });

            // Preencher o modal de ver mais com a descrição
            const verMaisModal = document.getElementById('verMaisModal');
            verMaisModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget; // Botão que abriu o modal
                const descricao = button.getAttribute('data-descricao') || '';
                let descricaoFormatada = '';
                for (let i = 0; i < descricao.length; i += 30) {
                    descricaoFormatada += descricao.slice(i, i + 30) + '<br>';
                }
                descricaoFormatada = descricaoFormatada.replace(/<br>$/, '');
                this.querySelector('#ver_mais_descricao').innerHTML = descricaoFormatada;
            });
        });
    </script>
</body>

</html>