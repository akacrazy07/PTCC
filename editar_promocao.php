<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true || $_SESSION['perfil'] !== 'admin') {
    header("Location: login.html");
    exit();
}
require_once 'conexao.php';

// verificar se o ID foi passado
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: gerenciar_promocoes.php?erro=id_nao_fornecido");
    exit();
}
$id = $_GET['id'];
$sql = "SELECT * FROM promocoes WHERE id = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();
$promocao = $resultado->fetch_assoc();
$stmt->close();

// verificar se a promoção existe
if (!$promocao) {
    header("Location: gerenciar_promocoes.php?erro=promocao_nao_encontrada");
    exit();
}

// listar produtos para o formulário
$sql_produtos = "SELECT id, nome_produto FROM produtos";
$resultado_produtos = $conexao->query($sql_produtos);
$produtos = $resultado_produtos->fetch_all(MYSQLI_ASSOC);

$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Promoção - Panificadora</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'navbar.php'; ?>
    <div class="container">
        <form method="POST" action="gerenciar_promocoes.php">
            <input type="hidden" name="id" value="<?php echo $promocao['id']; ?>">
            <label for="nome">Nome da Promoção:</label>
            <input type="text" name="nome" value="<?php echo htmlspecialchars($promocao['nome']); ?>" required><br>

            <label for="produto_id">Produto:</label>
            <select name="produto_id" required>
                <option value="">Selecione um produto</option>
                <?php foreach ($produtos as $produto): ?>
                    <option value="<?php echo $produto['id']; ?>" <?php if ($promocao['produto_id'] == $produto['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($produto['nome_produto']); ?>
                    </option>
                <?php endforeach; ?>
            </select><br>

            <label for="tipo">Tipo de Promoção:</label>
            <select name="tipo" required>
                <option value="percentual" <?php if ($promocao['tipo'] === 'percentual') echo 'selected'; ?>>Desconto Percentual</option>
                <option value="leve_pague" <?php if ($promocao['tipo'] === 'leve_pague') echo 'selected'; ?>>Leve X, Pague Y</option>
            </select><br>

            <label for="valor">Valor:</label>
            <input type="number" step="0.01" name="valor" value="<?php echo htmlspecialchars($promocao['valor']); ?>" required><br>

            <label for="data_inicio">Data Início:</label>
            <input type="date" name="data_inicio" value="<?php echo htmlspecialchars($promocao['data_inicio']); ?>" required><br>

            <label for="data_fim">Data Fim:</label>
            <input type="date" name="data_fim" value="<?php echo htmlspecialchars($promocao['data_fim']); ?>" required><br>

            <button type="submit">Salvar Alterações</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
</body>
</html>