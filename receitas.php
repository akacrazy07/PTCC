<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true ||
    !in_array($_SESSION['perfil'], ['admin', 'gerente'])) {
    header("Location: login.html");
    exit();
}
require_once 'conexao.php';
require_once 'funcoes.php';
$mensagem = '';
$calculo = '';

// Cadastrar receita
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['nome']) && isset($_POST['ingredientes'])) {
        $nome = $_POST['nome'];
        $ingredientes = [];
        foreach ($_POST['ingredientes'] as $ingrediente) {
            $nome_ing = $ingrediente['nome'];
            $quantidade = $ingrediente['quantidade'] . $ingrediente['unidade'];
            if (!empty($nome_ing) && !empty($quantidade)) {
                $ingredientes[$nome_ing] = $quantidade;
            }
        }
        $ingredientes_json = json_encode($ingredientes);
        if (empty($nome) || empty($ingredientes)) {
            $mensagem = "Erro: Nome ou ingredientes vazios!";
        } else {
            $stmt = $conexao->prepare("INSERT INTO receitas (nome, ingredientes) VALUES (?, ?)");
            $stmt->bind_param("ss", $nome, $ingredientes_json);
            if ($stmt->execute()) {
                $mensagem = "Receita cadastrada com sucesso!";
                $acao = "cadastrou uma receita: $nome";
                registrarLog($conexao, $_SESSION['usuario_id'], $acao);
            } else {
                $mensagem = "Erro ao cadastrar receita: " . $conexao->error;
            }
            $stmt->close();
        }
    } elseif (isset($_POST['calcular']) && isset($_POST['receita_id']) && isset($_POST['quantidade'])) {
        $receita_id = intval($_POST['receita_id']);
        $quantidade = intval($_POST['quantidade']);
        if ($quantidade <= 0) {
            $calculo = "Erro: Quantidade inválida!";
        } else {
            $stmt = $conexao->prepare("SELECT nome, ingredientes FROM receitas WHERE id = ?");
            $stmt->bind_param("i", $receita_id);
            $stmt->execute();
            $resultado = $stmt->get_result();
            if ($receita = $resultado->fetch_assoc()) {
                $ingredientes = json_decode($receita['ingredientes'], true);
                $calculo = "Para $quantidade unidades de " . htmlspecialchars($receita['nome']) . ":<br>";
                foreach ($ingredientes as $nome => $quant) {
                    $valor = floatval(preg_replace('/[^0-9.]/', '', $quant));
                    $unidade = preg_replace('/[0-9.]+/', '', $quant);
                    $total = $valor * $quantidade;
                    $calculo .= "- " . htmlspecialchars($nome) . ": " . $total . $unidade . "<br>";
                }
            } else {
                $calculo = "Receita não encontrada!";
            }
            $stmt->close();
        }
    } elseif (isset($_POST['concluir_task']) && ($_SESSION['perfil'] === 'gerente' || $_SESSION['perfil'] === 'admin')) {
        $task_id = intval($_POST['task_id']);
        $stmt = $conexao->prepare("UPDATE tasks SET status = 'concluido' WHERE id = ?");
        $stmt->bind_param("i", $task_id);
        if ($stmt->execute()) {
            $mensagem = "Task marcada como concluída!";
            $acao = "concluiu uma task: $task_id";
            registrarLog($conexao, $_SESSION['usuario_id'], $acao);
        } else {
            $mensagem = "Erro ao concluir task: " . $conexao->error;
        }
        $stmt->close();
    }
}

// Listar receitas
$sql_receitas = "SELECT id, nome FROM receitas";
$resultado_receitas = $conexao->query($sql_receitas);
$receitas = [];
while ($row = $resultado_receitas->fetch_assoc()) {
    $receitas[] = $row;
}

// Listar tasks
$sql_tasks = "SELECT * FROM tasks WHERE status = 'pendente' ORDER BY criado_em DESC";
$resultado_tasks = $conexao->query($sql_tasks);
$tasks = [];
while ($row = $resultado_tasks->fetch_assoc()) {
    $tasks[] = $row;
}

// Excluir receita
if (isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    $id_excluir = intval($_GET['excluir']);
    $stmt = $conexao->prepare("DELETE FROM receitas WHERE id = ?");
    $stmt->bind_param("i", $id_excluir);
    if ($stmt->execute()) {
        $mensagem = "Receita excluída com sucesso!";
        $acao = "excluiu uma receita: $id_excluir";
        registrarLog($conexao, $_SESSION['usuario_id'], $acao);
    } else {
        $mensagem = "Erro ao excluir receita: " . $conexao->error;
    }
    $stmt->close();
}

$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Receitas - Panificadora (TCC Offline)</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container">
        <h3>Cadastrar Receita</h3>
        <?php if (!empty($mensagem)): ?>
            <p class="mensagem"><?php echo htmlspecialchars($mensagem); ?></p>
        <?php endif; ?>
        <form action="receitas.php" method="post" id="receitaForm">
            <div class="mb-3">
                <label for="nome" class="form-label">Nome da Receita</label>
                <input type="text" class="form-control" id="nome" name="nome" placeholder="Ex.: Pão Integral" required>
            </div>
            <div id="ingredientes-container">
    <div class="ingrediente-row mb-3">
        <div class="row">
            <div class="col-5">
                <label for="ingredientes[0][nome]" class="form-label">Nome do Ingrediente</label>
                <input type="text" class="form-control" name="ingredientes[0][nome]" placeholder="Ex.: Farinha" required>
            </div>
            <div class="col-4">
                <label for="ingredientes[0][quantidade]" class="form-label">Quantidade</label>
                <input type="number" class="form-control" name="ingredientes[0][quantidade]" step="0.01" min="0" required>
            </div>
            <div class="col-3">
                <label for="ingredientes[0][unidade]" class="form-label">Unidade</label>
                <select class="form-control" name="ingredientes[0][unidade]" required>
                    <option value="g">gramas (g)</option>
                    <option value="kg">quilogramas (kg)</option>
                    <option value="mg">miligramas (mg)</option>
                    <option value="t">tonelada (t)</option>
                    <option value="ml">mililitros (ml)</option>
                    <option value="l">litros (l)</option>
                    <option value="m³">metro cúbico (m³)</option>
                    <option value="gal">galão (gal)</option>
                    <option value="lata">lata</option>
                    <option value="pacote">pacote</option>
                    <option value="saco">saco</option>
                    <option value="caixa">caixa</option>
                    <option value="un">unidade (un)</option>
                    <option value="cs">colher de sopa (cs)</option>
                    <option value="cc">colher de chá (cc)</option>
                    <option value="xíc">xícara (xíc)</option>
                    <option value="pç">pedaço (pç)</option>
                </select>
            </div>
        </div>
    </div>
</div>
<button type="button" id="addIngrediente" class="btn btn-secondary mb-3">Adicionar Ingrediente</button>
<button type="submit" class="btn btn-primary">Cadastrar Receita</button>
        </form> <br>
        <h3>Calcular Insumos</h3>
        <?php if (!empty($calculo)): ?>
            <p class="calculo"><?php echo $calculo; ?></p>
        <?php endif; ?>
        <form action="receitas.php" method="post">
            <select name="receita_id" required>
                <option value="">Selecione uma receita</option>
                <?php foreach ($receitas as $receita): ?>
                    <option value="<?php echo $receita['id']; ?>"><?php echo htmlspecialchars($receita['nome']); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="quantidade" placeholder="Quantidade a produzir" required min="1"> <br>
            <button type="submit" class="btn btn-primary" name="calcular">Calcular</button>
        </form> <br>
        <h3>Receitas Cadastradas</h3>
        <?php if (empty($receitas)): ?>
    <p>Nenhuma receita cadastrada ainda.</p>
<?php else: ?>
    <table class="table table-striped table-hover">
        <thead class="table-dark">
            <tr>
                <th scope="col">Nome da Receita</th>
                <th scope="col">Ação</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($receitas as $receita): ?>
                <tr>
                    <td><?php echo htmlspecialchars($receita['nome']); ?></td>
                    <td>
                        <a href="receitas.php?excluir=<?php echo $receita['id']; ?>" 
                           class="btn btn-danger btn-sm" 
                           onclick="return confirm('Tem certeza que quer excluir esta receita?');">
                           Excluir
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
        <h3 class="mt-5">Pedidos Personalizados</h3>
        <?php if (empty($tasks)): ?>
            <p>Nenhum pedido pendente.</p>
        <?php else: ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Nome do Pedido</th>
                        <th>Descrição</th>
                        <th>Data de Entrega</th>
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
                            <td><?php echo htmlspecialchars($task['criado_por']); ?></td>
                            <td>
                                <form action="receitas.php" method="post" style="display:inline;">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <button type="submit" name="concluir_task" class="btn btn-success btn-sm">Concluir</button>
                                </form>
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
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('ingredientes-container');
    const addButton = document.getElementById('addIngrediente');
    let ingredienteCount = 1;

    addButton.addEventListener('click', function() {
        const newRow = document.createElement('div');
        newRow.className = 'ingrediente-row mb-3';
        newRow.innerHTML = `
            <div class="row">
                <div class="col-5">
                    <input type="text" class="form-control" name="ingredientes[${ingredienteCount}][nome]" placeholder="Ex.: Açúcar" required>
                </div>
                <div class="col-4">
                    <input type="number" class="form-control" name="ingredientes[${ingredienteCount}][quantidade]" step="0.01" min="0" required>
                </div>
                <div class="col-3">
                    <select class="form-control" name="ingredientes[${ingredienteCount}][unidade]" required>
                        <option value="g">gramas (g)</option>
                        <option value="kg">quilogramas (kg)</option>
                        <option value="mg">miligramas (mg)</option>
                        <option value="t">tonelada (t)</option>
                        <option value="ml">mililitros (ml)</option>
                        <option value="l">litros (l)</option>
                        <option value="m³">metro cúbico (m³)</option>
                        <option value="gal">galão (gal)</option>
                        <option value="lata">lata</option>
                        <option value="pacote">pacote</option>
                        <option value="saco">saco</option>
                        <option value="caixa">caixa</option>
                        <option value="un">unidade (un)</option>
                        <option value="cs">colher de sopa (cs)</option>
                        <option value="cc">colher de chá (cc)</option>
                        <option value="xíc">xícara (xíc)</option>
                        <option value="pç">pedaço (pç)</option>
                    </select>
                </div>
            </div>
        `;
        container.appendChild(newRow);
        ingredienteCount++;
    });
});
</script>
</body>
</html>