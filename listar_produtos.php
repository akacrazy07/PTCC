<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: login.html");
    exit();
}
require_once 'conexao.php';

// Excluir produto (apenas admin e gerente)
if (isset($_GET['excluir']) && is_numeric($_GET['excluir']) && in_array($_SESSION['perfil'], ['admin', 'gerente'])) {
    $id = intval($_GET['excluir']);
    $stmt_img = $conexao->prepare("SELECT imagem, nome_produto FROM produtos WHERE id = ?");
    $stmt_img->bind_param("i", $id);
    $stmt_img->execute();
    $resultado_img = $stmt_img->get_result();
    $img = $resultado_img->fetch_assoc();
    $stmt_img->close();

    $stmt_vendas = $conexao->prepare("DELETE FROM vendas WHERE produto_id = ?");
    $stmt_vendas->bind_param("i", $id);
    $stmt_vendas->execute();
    $stmt_vendas->close();

    $stmt = $conexao->prepare("DELETE FROM produtos WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $mensagem = "Produto e vendas associadas excluídos com sucesso!";
        if ($img['imagem'] && file_exists("imagens/" . $img['imagem'])) {
            unlink("imagens/" . $img['imagem']);
        }
        // Registrar log
        $usuario_id = $_SESSION['usuario_id'];
        $acao = "Excluiu produto '{$img['nome_produto']}'";
        $stmt_log = $conexao->prepare("INSERT INTO logs (usuario_id, acao) VALUES (?, ?)");
        $stmt_log->bind_param("is", $usuario_id, $acao);
        $stmt_log->execute();
        $stmt_log->close();
    } else {
        $mensagem = "Erro ao excluir produto: " . $conexao->error;
    }
    $stmt->close();
}

// Filtrar por categoria
$categoria_filtro = isset($_GET['categoria']) ? intval($_GET['categoria']) : 0;
$sql = "SELECT p.*, c.nome AS categoria_nome FROM produtos p LEFT JOIN categorias c ON p.categoria_id = c.id";
if ($categoria_filtro > 0) {
    $sql .= " WHERE p.categoria_id = ?";
}
$stmt = $conexao->prepare($sql);
if ($categoria_filtro > 0) {
    $stmt->bind_param("i", $categoria_filtro);
}
$stmt->execute();
$resultado = $stmt->get_result();
$produtos = [];
while ($produto = $resultado->fetch_assoc()) {
    $produtos[] = $produto;
}
$stmt->close();

// Buscar categorias para o filtro
$sql_categorias = "SELECT id, nome FROM categorias";
$resultado_categorias = $conexao->query($sql_categorias);
$categorias = [];
while ($row = $resultado_categorias->fetch_assoc()) {
    $categorias[] = $row;
}

$conexao->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listar Produtos - Gestão Panificadora</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .alerta-validade { background-color: #ffcccc; }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>
    <div class="container">
        <h2>Produtos em Estoque</h2>
        <?php if (isset($mensagem)): ?>
            <p class="mensagem"><?php echo $mensagem; ?></p>
        <?php endif; ?>
        <form method="get" action="listar_produtos.php" class="mb-3">
            <select name="categoria" onchange="this.form.submit()" class="form-select w-auto">
                <option value="0">Todas as Categorias</option>
                <?php foreach ($categorias as $categoria): ?>
                    <option value="<?php echo $categoria['id']; ?>" <?php echo $categoria_filtro == $categoria['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($categoria['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php if (empty($produtos)): ?>
            <p>Nenhum produto cadastrado ainda.</p>
        <?php else: ?>
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Imagem</th>
                        <th>Nome</th>
                        <th>Categoria</th>
                        <th>Descrição</th>
                        <th>Quantidade</th>
                        <th>Preço</th>
                        <th>Data de Validade</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produtos as $produto): ?>
                        <?php
                        $hoje = new DateTime();
                        $validade = $produto['data_validade'] ? new DateTime($produto['data_validade']) : null;
                        $dias_restantes = $validade ? $hoje->diff($validade)->days : null;
                        $alerta = $validade && $dias_restantes <= 7 && $hoje <= $validade;
                        ?>
                        <tr <?php echo $alerta ? 'class="alerta-validade"' : ''; ?>>
                            <td>
                                <?php if ($produto['imagem']): ?>
                                    <img src="imagens/<?php echo htmlspecialchars($produto['imagem']); ?>" alt="<?php echo htmlspecialchars($produto['nome_produto']); ?>" class="produto-imagem">
                                <?php else: ?>
                                    Sem imagem
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($produto['nome_produto']); ?></td>
                            <td><?php echo htmlspecialchars($produto['categoria_nome'] ?? 'Sem categoria'); ?></td>
                            <td><?php echo htmlspecialchars($produto['descricao']); ?></td>
                            <td><?php echo htmlspecialchars($produto['quantidade']); ?></td>
                            <td>R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></td>
                            <td>
                                <?php echo $produto['data_validade'] ? htmlspecialchars(date('d/m/Y', strtotime($produto['data_validade']))) : 'Não definida'; ?>
                                <?php if ($alerta): ?>
                                    <span style="color: red;"> (Faltam <?php echo $dias_restantes; ?> dias)</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (in_array($_SESSION['perfil'], ['admin', 'gerente'])): ?>
                                    <a href="editar_produto.php?id=<?php echo $produto['id']; ?>" class="btn btn-primary btn-sm">Editar</a>
                                    <a href="listar_produtos.php?excluir=<?php echo $produto['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que quer excluir este produto?');">Excluir</a>
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