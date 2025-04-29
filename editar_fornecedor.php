<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true || !in_array($_SESSION['perfil'], ['admin', 'gerente'])) {
    header("Location: login.html");
    exit();
}
require_once 'conexao.php';

$id = $_GET['id'];
$sql = "SELECT * FROM fornecedores WHERE id = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();
$fornecedor = $resultado->fetch_assoc();
$stmt->close();

$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Fornecedor - Panificadora</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'navbar.php'; ?>
    <div class="container">
        <form method="POST" action="gerenciar_fornecedores.php">
            <input type="hidden" name="id" value="<?php echo $fornecedor['id']; ?>">
            <label for="nome">Nome do Fornecedor:</label>
            <input type="text" name="nome" value="<?php echo htmlspecialchars($fornecedor['nome']); ?>" required><br>

            <label for="contato">Contato:</label>
            <input type="text" name="contato" value="<?php echo htmlspecialchars($fornecedor['contato']); ?>" required><br>

            <label for="endereco">Endereço (opcional):</label>
            <textarea name="endereco"><?php echo htmlspecialchars($fornecedor['endereco'] ?? ''); ?></textarea><br>

            <button type="submit">Salvar Alterações</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
</body>
</html>