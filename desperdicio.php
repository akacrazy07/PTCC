<?php
session_start ();
if (!isset ($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true ||
!in_array ($_SESSION['perfil'], ['admin', 'gerente'])) {
    header("Location: login.html");
    exit();
}
require_once 'conexao.php';
require_once 'funcoes.php';

$mensagem = '';

// listar produtos para registro
$sql_produtos = "SELECT id, nome_produto FROM produtos";
$resultado_produtos = $conexao->query($sql_produtos);
$produtos = [];
while ($row = $resultado_produtos->fetch_assoc()) {
    $produtos[] = $row;
}

//registar desperdício
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['produto_id']) && isset($_POST['quantidade'])) {
    $produto_id = intval($_POST['produto_id']);
    $quantidade = intval($_POST['quantidade']);

    if ($quantidade <= 0) {
        $mensagem = "Erro: Quantidade inválida";
    } else {
        $stmt = $conexao->prepare("INSERT INTO desperdicio (produto_id, quantidade) VALUES (?, ?)");
        $stmt->bind_param("ii", $produto_id, $quantidade);
        if ($stmt->execute()) {
            $mensagem = "Desperdício registrado com sucesso!";
            $acao = "Registrou desperdício de $quantidade unidades do produto ID $produto_id";
            registrarLog($conexao, $_SESSION['usuario_id'], $acao);
        } else {
            $mensagem = "Erro ao registrar desperdício" . $conexao->error;
        }
        $stmt->close();
    }
}
// relatório semanal
$sql_relatorio = "SELECT p.nome_produto, SUM(d.quantidade) as total_desperdicio FROM desperdicio d
JOIN produtos p ON d.produto_id = p.id
WHERE d.data >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
GROUP BY d.produto_id, p.nome_produto
ORDER BY total_desperdicio DESC";
$resultado_relatorio = $conexao->query($sql_relatorio);
$desperdicio_semanal = [];
while ($row = $resultado_relatorio->fetch_assoc()) {
    $desperdicio_semanal[] = $row;
}
$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Rastreamento de desperdico </title>
        <link rel="stylesheet" href="style.css">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
</html>
<body>
<?php include 'navbar.php'; ?> 
    <div class="container">
        <h3>Registrar Desperdício</h3>
        <?php if (!empty($mensagem)): ?>
            <p class="mensagem"><?php echo $mensagem; ?></p>
        <?php endif; ?>
        <form action="desperdicio.php" method="post">
            <select name="produto_id" required>
                <option value="">Selecione um produto</option>
                <?php foreach ($produtos as $produto): ?>
                    <option value="<?php echo $produto['id']; ?>"><?php echo htmlspecialchars($produto['nome_produto']); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="quantidade" placeholder="Quantidade desperdiçada" required min="1">
            <button type="submit">Registrar</button>
        </form>
        <br>
        <h3>Desperdício dos Últimos 7 Dias</h3>
        <?php if (empty($desperdicio_semanal)): ?>
            <p>Nenhum desperdício registrado nos últimos 7 dias.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Total Desperdiçado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($desperdicio_semanal as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['nome_produto']); ?></td>
                            <td><?php echo $item['total_desperdicio']; ?></td>
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