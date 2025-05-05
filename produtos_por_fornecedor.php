<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true || !in_array($_SESSION['perfil'], ['admin', 'gerente'])) {
    header("Location: login.html");
    exit();
}
require_once 'conexao.php';
require_once 'funcoes.php';
$mensagem = '';

// Listar fornecedores
$sql_fornecedores = "SELECT id, nome FROM fornecedores";
$resultado_fornecedores = $conexao->query($sql_fornecedores);
$fornecedores = [];
while ($row = $resultado_fornecedores->fetch_assoc()) {
    $fornecedores[] = $row;
}

// Listar categorias para o formulário
$sql_categorias = "SELECT id, nome FROM categorias";
$resultado_categorias = $conexao->query($sql_categorias);
$categorias = [];
while ($row = $resultado_categorias->fetch_assoc()) {
    $categorias[] = $row;
}

// Adicionar produto a fornecedor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar_produto'])) {
    $fornecedor_id = $_POST['fornecedor_id'];
    $nome_produto = $_POST['nome_produto'];
    $preco_unitario = floatval($_POST['preco_unitario']);
    $categoria_id = !empty($_POST['categoria_id']) ? intval($_POST['categoria_id']) : null;
    $data_validade = !empty($_POST['data_validade']) ? $_POST['data_validade'] : null;

    if (empty($fornecedor_id) || empty($nome_produto) || $preco_unitario <= 0) {
        $mensagem = "Erro: Preencha todos os campos obrigatórios corretamente!";
    } else {
        $sql = "INSERT INTO produtos_fornecedores (fornecedor_id, nome_produto, preco_unitario, categoria_id, data_validade) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("isdss", $fornecedor_id, $nome_produto, $preco_unitario, $categoria_id, $data_validade);
        if ($stmt->execute()) {
            $mensagem = "Produto associado ao fornecedor com sucesso!";
            $acao = "associou o produto $nome_produto ao fornecedor ID $fornecedor_id";
            registrarLog($conexao, $_SESSION['usuario_id'], $acao);
        } else {
            $mensagem = "Erro ao associar produto: " . $conexao->error;
        }
        $stmt->close();
    }
}

// Excluir produto associado
if (isset($_GET['excluir'])) {
    $id = intval($_GET['excluir']);
    $sql = "DELETE FROM produtos_fornecedores WHERE id = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $mensagem = "Produto removido do fornecedor com sucesso!";
    } else {
        $mensagem = "Erro ao remover produto: " . $conexao->error;
    }
    $stmt->close();
}

// Filtrar produtos por fornecedor
$fornecedor_filtro = isset($_GET['fornecedor_id']) ? intval($_GET['fornecedor_id']) : 0;
$sql_produtos = "SELECT pf.*, f.nome AS nome_fornecedor, c.nome AS categoria_nome 
                 FROM produtos_fornecedores pf 
                 JOIN fornecedores f ON pf.fornecedor_id = f.id 
                 LEFT JOIN categorias c ON pf.categoria_id = c.id";
if ($fornecedor_filtro > 0) {
    $sql_produtos .= " WHERE pf.fornecedor_id = ?";
}
$stmt_produtos = $conexao->prepare($sql_produtos);
if ($fornecedor_filtro > 0) {
    $stmt_produtos->bind_param("i", $fornecedor_filtro);
}
$stmt_produtos->execute();
$resultado_produtos = $stmt_produtos->get_result();
$produtos = [];
while ($row = $resultado_produtos->fetch_assoc()) {
    $produtos[] = $row;
}
$stmt_produtos->close();

// Responder com JSON para o fetch em pedidos_fornecedores.php
if (isset($_GET['fornecedor_id']) && !isset($_GET['excluir']) && !isset($_POST['adicionar_produto'])) {
    $fornecedor_id = intval($_GET['fornecedor_id']);
    $sql = "SELECT pf.nome_produto, pf.preco_unitario, pf.data_validade, pf.categoria_id, c.nome AS categoria_nome 
            FROM produtos_fornecedores pf 
            LEFT JOIN categorias c ON pf.categoria_id = c.id 
            WHERE pf.fornecedor_id = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $fornecedor_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $produtos_json = [];
    while ($row = $resultado->fetch_assoc()) {
        $produtos_json[] = [
            'nome_produto' => $row['nome_produto'],
            'preco_unitario' => $row['preco_unitario'],
            'data_validade' => $row['data_validade'],
            'categoria_id' => $row['categoria_id'],
            'categoria_nome' => $row['categoria_nome'] ?? 'Sem categoria'
        ];
    }
    $stmt->close();
    header('Content-Type: application/json');
    echo json_encode($produtos_json);
    exit();
}

$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos por Fornecedor - Panificadora</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <?php include 'navbar.php'; ?>
    <div class="container">
        <h3>Associar Produto a Fornecedor</h3>
        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($mensagem); ?></div>
        <?php endif; ?>
        <form method="POST" action="produtos_por_fornecedor.php">
            <div class="mb-3">
                <label for="fornecedor_id" class="form-label">Fornecedor:</label>
                <select name="fornecedor_id" class="form-control" required>
                    <option value="">Selecione um fornecedor</option>
                    <?php foreach ($fornecedores as $fornecedor): ?>
                        <option value="<?php echo $fornecedor['id']; ?>"><?php echo htmlspecialchars($fornecedor['nome']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="nome_produto" class="form-label">Nome do Produto:</label>
                <input type="text" class="form-control" name="nome_produto" required placeholder="Ex.: Farinha de Trigo">
            </div>
            <div class="mb-3">
                <label for="preco_unitario" class="form-label">Preço Unitário:</label>
                <input type="number" class="form-control" name="preco_unitario" step="0.01" required min="0">
            </div>
            <div class="mb-3">
                <label for="categoria_id" class="form-label">Categoria:</label>
                <select name="categoria_id" class="form-control">
                    <option value="">Sem categoria</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?php echo $categoria['id']; ?>"><?php echo htmlspecialchars($categoria['nome']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="data_validade" class="form-label">Data de Validade:</label>
                <input type="date" class="form-control" name="data_validade">
            </div>
            <button type="submit" name="adicionar_produto" class="btn btn-primary">Associar Produto</button>
        </form>

        <h3 class="mt-5">Produtos Associados a Fornecedores</h3>
        <form method="GET" action="produtos_por_fornecedor.php" class="mb-3">
            <select name="fornecedor_id" onchange="this.form.submit()" class="form-select w-auto">
                <option value="0">Todos os Fornecedores</option>
                <?php foreach ($fornecedores as $fornecedor): ?>
                    <option value="<?php echo $fornecedor['id']; ?>" <?php echo $fornecedor_filtro == $fornecedor['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($fornecedor['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php if (empty($produtos)): ?>
            <p>Nenhum produto associado a fornecedores.</p>
        <?php else: ?>
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Fornecedor</th>
                        <th>Produto</th>
                        <th>Categoria</th>
                        <th>Preço Unitário</th>
                        <th>Data de Validade</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produtos as $produto): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($produto['nome_fornecedor']); ?></td>
                            <td><?php echo htmlspecialchars($produto['nome_produto']); ?></td>
                            <td><?php echo htmlspecialchars($produto['categoria_nome'] ?? 'Sem categoria'); ?></td>
                            <td>R$ <?php echo number_format($produto['preco_unitario'], 2, ',', '.'); ?></td>
                            <td><?php echo $produto['data_validade'] ? date('d/m/Y', strtotime($produto['data_validade'])) : 'Não definida'; ?></td>
                            <td>
                                <a href="produtos_por_fornecedor.php?excluir=<?php echo $produto['id']; ?>"
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Tem certeza que deseja remover este produto do fornecedor?')">Excluir</a>
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