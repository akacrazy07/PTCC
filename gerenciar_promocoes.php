<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true || ($_SESSION['perfil'] !== 'admin' && $_SESSION['perfil'] !== 'gerente')) {
    header("Location: login.html");
    exit();
}
require_once 'conexao.php';
require_once 'funcoes.php';

// Listar produtos pra formulário
$sql_produtos = "SELECT id, nome_produto FROM produtos";
$resultado_produtos = $conexao->query($sql_produtos);
$produtos = $resultado_produtos->fetch_all(MYSQLI_ASSOC);

// Cadastrar ou atualizar promoção
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $tipo = $_POST['tipo'];
    $valor = $_POST['valor'];
    $data_inicio = $_POST['data_inicio'];
    $data_fim = $_POST['data_fim'];
    $produto_id = $_POST['produto_id'];
    $id = !empty($_POST['id']) ? $_POST['id'] : null;

    // Validar tipo
    $tipos_permitidos = ['percentual', 'compre_por'];
    if (!in_array($tipo, $tipos_permitidos)) {
        header("Location: gerenciar_promocoes.php?erro=tipo_invalido");
        exit();
    }

    // Validar datas
    if (empty($data_inicio) || empty($data_fim)) {
        header("Location: gerenciar_promocoes.php?erro=data_vazia");
        exit();
    }
    if (strtotime($data_inicio) === false || strtotime($data_fim) === false) {
        header("Location: gerenciar_promocoes.php?erro=data_invalida");
        exit();
    }
    if (strtotime($data_inicio) > strtotime($data_fim)) {
        header("Location: gerenciar_promocoes.php?erro=data_inicio_maior");
        exit();
    }
    if (empty($produto_id)) {
        header("Location: gerenciar_promocoes.php?erro=produto_nao_selecionado");
        exit();
    }

    if ($id) {
        // Atualizar promoção existente
        $sql = "UPDATE promocoes SET nome = ?, tipo = ?, valor = ?, data_inicio = ?, data_fim = ?, produto_id = ? WHERE id = ?";
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("ssdssii", $nome, $tipo, $valor, $data_inicio, $data_fim, $produto_id, $id);
    } else {
        // Cadastrar nova promoção
        $sql = "INSERT INTO promocoes (nome, tipo, valor, data_inicio, data_fim, produto_id) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("ssdssi", $nome, $tipo, $valor, $data_inicio, $data_fim, $produto_id);
    }
    if ($stmt->execute()) {
        header("Location: gerenciar_promocoes.php?sucesso=1");
        $acao = "cadastrou/atualizou a promoção: " . $nome;
        registrarLog($conexao, $_SESSION['usuario_id'], $acao);
    } else {
        header("Location: gerenciar_promocoes.php?erro=sql");
    }
    $stmt->close();
    exit();
}

// Excluir promoção
if (isset($_GET['excluir'])) {
    $id = $_GET['excluir'];
    $sql = "DELETE FROM promocoes WHERE id = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: gerenciar_promocoes.php");
    exit();
}

// Listar promoções
$sql_promocoes = "SELECT p.*, pr.nome_produto 
                  FROM promocoes p 
                  JOIN produtos pr ON p.produto_id = pr.id";
$resultado_promocoes = $conexao->query($sql_promocoes);
$promocoes = [];
while ($row = $resultado_promocoes->fetch_assoc()) {
    $promocoes[] = $row;
}

$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Promoções - Panificadora</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .form-group {
            margin-bottom: 1.5rem;
        }

        .table-responsive {
            margin-top: 2rem;
        }

        .alert {
            margin-top: 1rem;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>
    <div class="container mt-5">
        <?php if (isset($_GET['sucesso'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Promoção salva com sucesso!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['erro'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php
                if ($_GET['erro'] === 'data_vazia') echo "Erro: As datas de início e fim são obrigatórias.";
                elseif ($_GET['erro'] === 'data_invalida') echo "Erro: Data inválida.";
                elseif ($_GET['erro'] === 'data_inicio_maior') echo "Erro: A data de início não pode ser maior que a data de fim.";
                elseif ($_GET['erro'] === 'produto_nao_selecionado') echo "Erro: Selecione um produto para a promoção.";
                elseif ($_GET['erro'] === 'tipo_invalido') echo "Erro: Tipo de promoção inválido.";
                else echo "Erro ao salvar a promoção.";
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Formulário para cadastrar/editar promoção -->
        <div class="card p-4 shadow-sm">
            <h2 class="mb-4">Cadastrar/Editar Promoção</h2>
            <form method="POST" action="gerenciar_promocoes.php">
                <input type="hidden" name="id" value="">
                <div class="form-group">
                    <label for="nome" class="form-label">Nome da Promoção:</label>
                    <input type="text" class="form-control" id="nome" name="nome" required>
                </div>
                <div class="form-group">
                    <label for="produto_id" class="form-label">Produto:</label>
                    <select class="form-select" name="produto_id" required>
                        <option value="">Selecione um produto</option>
                        <?php foreach ($produtos as $produto): ?>
                            <option value="<?php echo $produto['id']; ?>">
                                <?php echo htmlspecialchars($produto['nome_produto']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="tipo" class="form-label">Tipo de Promoção:</label>
                    <select class="form-select" name="tipo" required>
                        <option value="percentual">Desconto Percentual</option>
                        <option value="compre_por">Compre X por Y</option>
                    </select>
                    <small class="form-text text-muted">
                        - Desconto Percentual: Insira o percentual (ex.: 10 para 10%).<br>
                        - Compre X por Y: Insira "X por Y" (ex.: 3 por 2 para comprar 3 e pagar o valor de 2).
                    </small>
                </div>
                <div class="form-group">
                    <label for="valor" class="form-label">Valor:</label>
                    <input type="text" class="form-control" id="valor" name="valor" required placeholder="Ex.: 10 ou 3 por 2">
                </div>
                <div class="form-group">
                    <label for="data_inicio" class="form-label">Data Início:</label>
                    <input type="date" class="form-control" name="data_inicio" required>
                </div>
                <div class="form-group">
                    <label for="data_fim" class="form-label">Data Fim:</label>
                    <input type="date" class="form-control" name="data_fim" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 mt-3">Salvar Promoção</button>
            </form>
        </div>

        <!-- Listar promoções -->
        <div class="table-responsive mt-5">
            <h3 class="mb-4">Promoções Cadastradas</h3>
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Nome</th>
                        <th>Produto</th>
                        <th>Tipo</th>
                        <th>Valor</th>
                        <th>Data Início</th>
                        <th>Data Fim</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($promocoes)): ?>
                        <?php foreach ($promocoes as $promocao): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($promocao['nome']); ?></td>
                                <td><?php echo htmlspecialchars($promocao['nome_produto']); ?></td>
                                <td><?php echo $promocao['tipo'] === 'percentual' ? 'Desconto Percentual' : 'Compre X por Y'; ?></td>
                                <td>
                                    <?php echo $promocao['tipo'] === 'percentual'
                                        ? htmlspecialchars($promocao['valor']) . '%'
                                        : htmlspecialchars($promocao['valor']); ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($promocao['data_inicio'])); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($promocao['data_fim'])); ?></td>
                                <td>
                                    <a href="editar_promocao.php?id=<?php echo $promocao['id']; ?>" class="btn btn-warning btn-sm">Editar</a>
                                    <a href="gerenciar_promocoes.php?excluir=<?php echo $promocao['id']; ?>"
                                        class="btn btn-danger btn-sm ms-2"
                                        onclick="return confirm('Tem certeza que deseja excluir esta promoção?')">Excluir</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">Nenhuma promoção cadastrada.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>