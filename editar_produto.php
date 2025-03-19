<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: login.html");
    exit();
}
require_once 'conexao.php';

$mensagem = '';
$id = intval($_GET['id']);

// carregar dados do produto
$stmt = $conexao->prepare("SELECT * FROM produtos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$produto = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome_produto = $_POST['nome_produto'];
    $descricao = $_POST['descricao'];
    $quantidade = intval($_POST['quantidade']);
    $preco = floatval($_POST['preco']);
    $estoque_minimo = intval($_POST['estoque_minimo']);

    if (empty($nome_produto) || $quantidade < 0 || $preco < 0 || $estoque_minimo < 0) {
        $mensagem = "Erro: Nome vazio ou valores inválidos!";
    } else {
        $imagem_nome = $produto['imagem'];
        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == UPLOAD_ERR_OK) {
            $imagem_tmp = $_FILES['imagem']['tmp_name'];
            $imagem_nome_original = $_FILES['imagem']['name'];
            $extensao = strtolower(pathinfo($imagem_nome_original, PATHINFO_EXTENSION));
            $permitidos = ['jpg', 'jpeg', 'png'];

            if (in_array($extensao, $permitidos)) {
                $imagem_nome = uniqid() . '.' . $extensao;
                $destino = 'imagens/' . $imagem_nome;
                if (move_uploaded_file($imagem_tmp, $destino)) {
                    // remove a imagem antiga, se existir
                    if ($produto['imagem'] && file_exists("imagens/" . $produto['imagem'])) {
                        unlink("imagens/" . $produto['imagem']);
                    }
                } else {
                    $mensagem = "Erro ao salvar a nova imagem!";
                }
            } else {
                $mensagem = "Formato de imagem inválido! Use JPG, JPEG ou PNG.";
            }
        }

        if (empty($mensagem)) {
            $stmt = $conexao->prepare("UPDATE produtos SET nome_produto = ?, descricao = ?, quantidade = ?, preco = ?, imagem = ?, estoque_minimo = ? WHERE id = ?");
            $stmt->bind_param("ssidsii", $nome_produto, $descricao, $quantidade, $preco, $imagem_nome, $estoque_minimo, $id);
            if ($stmt->execute()) {
                $mensagem = "Produto atualizado com sucesso!";
            } else {
                $mensagem = "Erro ao atualizar produto: " . $conexao->error;
            }
            $stmt->close();
        }
    }
}

$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Produto - Panificadora</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Gestão de Estoque - Panificadora</h1>
        <nav>
            <a href="controle_estoque.php">Dashboard</a>
            <a href="adicionar_produto.php">Adicionar Produto</a>
            <a href="listar_produtos.php">Listar Produtos</a>
            <a href="registrar_venda.php">Registrar Venda</a>
            <a href="relatorios.php">Relatórios</a>
            <a href="receitas.php">Receitas</a>
            <a href="desperdicio.php">Desperdício</a>
            <a href="logout.php">Sair</a>
        </nav>
    </header>
    <div class="container">
        <h2>Editar Produto</h2>
        <?php if (!empty($mensagem)): ?>
            <p class="mensagem"><?php echo $mensagem; ?></p>
        <?php endif; ?>
        <form action="editar_produto.php?id=<?php echo $id; ?>" method="post" enctype="multipart/form-data">
            <input type="text" name="nome_produto" value="<?php echo htmlspecialchars($produto['nome_produto']); ?>" placeholder="Nome do Produto" required>
            <textarea name="descricao" placeholder="Descrição (opcional)"><?php echo htmlspecialchars($produto['descricao']); ?></textarea>
            <input type="number" name="quantidade" value="<?php echo $produto['quantidade']; ?>" placeholder="Quantidade" required min="0">
            <input type="number" name="preco" value="<?php echo $produto['preco']; ?>" placeholder="Preço (ex: 9.99)" step="0.01" required min="0">
            <input type="number" name="estoque_minimo" value="<?php echo htmlspecialchars($produto['estoque_minimo'] ?? 0); ?>" placeholder="Estoque Mínimo" required min="0">
            <div class="imagem-atual">
                <?php if ($produto['imagem']): ?>
                    <p>Imagem Atual:</p>
                    <img src="imagens/<?php echo htmlspecialchars($produto['imagem']); ?>" alt="Imagem Atual" class="produto-imagem">
                <?php else: ?>
                    <p>Sem imagem atual.</p>
                <?php endif; ?>
            </div>
            <input type="file" name="imagem" accept=".jpg,.jpeg,.png" placeholder="Nova Imagem (opcional)">
            <button type="submit">Salvar Alterações</button>
        </form>
    </div>
</body>
</html>