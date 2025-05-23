<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true ||
!in_array($_SESSION['perfil'], ['admin', 'gerente'])) {
    header("Location: login.html");
    exit();
}
require_once 'conexao.php';

$mensagem = '';

// Buscar categorias
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

    // Validação
    if (empty($nome_produto) || $quantidade <= 0 || $preco <= 0 || $estoque_minimo <= 0) {
        $mensagem = "Erro: Nome vazio ou valores inválidos!";
    } else {
        // Upload de imagem
        $imagem_nome = null;
        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
            $imagem_tmp = $_FILES['imagem']['tmp_name'];
            $imagem_nome_original = $_FILES['imagem']['name'];
            $extensao = strtolower(pathinfo($imagem_nome_original, PATHINFO_EXTENSION));
            $permitidos = ['jpg', 'jpeg', 'png'];

            if (in_array($extensao, $permitidos)) {
                $imagem_nome = uniqid() . '.' . $extensao; // Nome único
                $destino = 'imagens/' . $imagem_nome;
                if (move_uploaded_file($imagem_tmp, $destino)) {
                    $mensagem = 'Imagem enviada com sucesso!';
                } else {
                    $mensagem = 'Erro ao enviar imagem!';
                }
            } else {
                $mensagem = "Erro: Imagem inválida! Use JPG, JPEG ou PNG.";
            }
        }

        // Inserir no banco
        if (empty($mensagem) || $mensagem == 'Imagem enviada com sucesso!') {
            $sql = "INSERT INTO produtos (nome_produto, descricao, quantidade, preco, imagem, estoque_minimo, categoria_id, data_validade) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conexao->prepare($sql);
            $stmt->bind_param("ssidsiis", $nome_produto, $descricao, $quantidade, $preco, $imagem_nome, $estoque_minimo, $categoria_id, $data_validade);
            if ($stmt->execute()) {
                $novo_produto_id = $conexao->insert_id; // ID do produto recém-criado
                $usuario_id = $_SESSION['usuario_id'];

                // Registrar no histórico de preços (preço inicial)
                $preco_antigo = 0.00;
                $stmt_historico = $conexao->prepare("INSERT INTO historico_precos (produto_id, preco_antigo, preco_novo, usuario_id) VALUES (?, ?, ?, ?)");
                $stmt_historico->bind_param("iddi", $novo_produto_id, $preco_antigo, $preco, $usuario_id);
                $stmt_historico->execute();
                $stmt_historico->close();

                // Registrar log
                $acao = "Adicionou produto '$nome_produto' com preço inicial R$ " . number_format($preco, 2, ',', '.');
                $stmt_log = $conexao->prepare("INSERT INTO logs (usuario_id, acao) VALUES (?, ?)");
                $stmt_log->bind_param("is", $usuario_id, $acao);
                $stmt_log->execute();
                $stmt_log->close();

                $mensagem = 'Produto adicionado com sucesso!';
            } else {
                $mensagem = 'Erro ao adicionar produto: ' . $conexao->error;
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
    <title>Adicionar Produto - Panificadora</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'navbar.php'; ?>
    <div class="container">
        <?php if (!empty($mensagem)): ?>
            <p><?php echo $mensagem; ?></p>
        <?php endif; ?>
        <form action="adicionar_produto.php" method="post" enctype="multipart/form-data">
            <input type="text" name="nome_produto" placeholder="Nome do Produto" required>
            <textarea name="descricao" placeholder="Descrição (opcional)"></textarea>
            <input type="number" name="quantidade" placeholder="Quantidade Inicial" required min="0">
            <input type="number" name="preco" placeholder="Preço (ex: 9.99)" step="0.01" required min="0">
            <input type="number" name="estoque_minimo" placeholder="Estoque Mínimo" required min="0">
            <select name="categoria_id" required>
                <option value="">Selecione uma categoria</option>
                <?php foreach ($categorias as $categoria): ?>
                    <option value="<?php echo $categoria['id']; ?>"><?php echo htmlspecialchars($categoria['nome']); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="data_validade" placeholder="Data de Validade (opcional)">
            <input type="file" name="imagem" accept=".jpg, .jpeg, .png" placeholder="Imagem do Produto">
            <button type="submit" class="btn btn-primary">Adicionar Produto</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
</body>
</html>