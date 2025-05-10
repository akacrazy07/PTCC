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
$id = intval($_GET['id']);

// carregar dados do produto
$stmt = $conexao->prepare("SELECT * FROM produtos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$produto = $stmt->get_result()->fetch_assoc();
$stmt->close();

// buscar categorias
$sql_categorias = "SELECT id, nome FROM categorias";
$resultado_categorias = $conexao->query($sql_categorias);
$categorias = [];
while ($row = $resultado_categorias->fetch_assoc()) {
    $categorias[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome_produto = $_POST['nome_produto'];
    $descricao = $_POST['descricao'];
    $quantidade = intval($_POST['quantidade']);
    $preco = floatval($_POST['preco']);
    $estoque_minimo = intval($_POST['estoque_minimo']);
    $categoria_id = intval($_POST['categoria_id']);
    $data_validade = !empty($_POST['data_validade']) ? $_POST['data_validade'] : null;
    $usuario_id = $_SESSION['usuario_id'];

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
                    // Remove a imagem antiga, se existir
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
            // Verificar se o preço foi alterado
            $preco_antigo = floatval($produto['preco']);
            $preco_alterado = ($preco_antigo != $preco);

            // Atualizar o produto
            $stmt = $conexao->prepare("UPDATE produtos SET nome_produto = ?, descricao = ?, quantidade = ?, preco = ?, imagem = ?, estoque_minimo = ?, categoria_id = ?, data_validade = ? WHERE id = ?");
            $stmt->bind_param("ssidsiisi", $nome_produto, $descricao, $quantidade, $preco, $imagem_nome, $estoque_minimo, $categoria_id, $data_validade, $id);
            if ($stmt->execute()) {
                // Registrar no histórico de preços, se o preço foi alterado
                if ($preco_alterado) {
                    $stmt_historico = $conexao->prepare("INSERT INTO historico_precos (produto_id, preco_antigo, preco_novo, usuario_id) VALUES (?, ?, ?, ?)");
                    $stmt_historico->bind_param("iddi", $id, $preco_antigo, $preco, $usuario_id);
                    $stmt_historico->execute();
                    $stmt_historico->close();

                    // Registrar log
                    $acao = "Alterou preço do produto ID $id de R$ " . number_format($preco_antigo, 2, ',', '.') . " para R$ " . number_format($preco, 2, ',', '.');
                    $stmt_log = $conexao->prepare("INSERT INTO logs (usuario_id, acao) VALUES (?, ?)");
                    $stmt_log->bind_param("is", $usuario_id, $acao);
                    $stmt_log->execute();
                    $stmt_log->close();
                }

                $mensagem = "Produto atualizado com sucesso!";
                $acao = "Atualizou produto ID $id";
                registrarLog($conexao, $_SESSION['usuario_id'], $acao);
                // Recarregar dados do produto após atualização
                $stmt = $conexao->prepare("SELECT * FROM produtos WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $produto = $stmt->get_result()->fetch_assoc();
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'navbar.php'; ?>
    <div class="container">
        <?php if (!empty($mensagem)): ?>
            <p class="mensagem"><?php echo $mensagem; ?></p>
        <?php endif; ?>
        <form action="editar_produto.php?id=<?php echo $id; ?>" method="post" enctype="multipart/form-data">
            <input type="text" name="nome_produto" value="<?php echo htmlspecialchars($produto['nome_produto']); ?>" placeholder="Nome do Produto" required>
            <textarea name="descricao" placeholder="Descrição (opcional)"><?php echo htmlspecialchars($produto['descricao']); ?></textarea>
            <input type="number" name="quantidade" value="<?php echo $produto['quantidade']; ?>" placeholder="Quantidade" required min="0">
            <input type="number" name="preco" value="<?php echo $produto['preco']; ?>" placeholder="Preço (ex: 9.99)" step="0.01" required min="0">
            <input type="number" name="estoque_minimo" value="<?php echo htmlspecialchars($produto['estoque_minimo'] ?? 0); ?>" placeholder="Estoque Mínimo" required min="0">
            <select name="categoria_id" required>
                <option value="">Selecione uma categoria</option>
                <?php foreach ($categorias as $categoria): ?>
                    <option value="<?php echo $categoria['id']; ?>" <?php echo $produto['categoria_id'] == $categoria['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($categoria['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="data_validade" value="<?php echo $produto['data_validade'] ? htmlspecialchars($produto['data_validade']) : ''; ?>" placeholder="Data de Validade (opcional)">
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
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
</body>
</html>