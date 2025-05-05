<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true || !in_array($_SESSION['perfil'], ['admin'])) {
    header("Location: login.html");
    exit();
}
require_once 'conexao.php';
require_once 'funcoes.php';

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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Formatação de Telefone
            const telefoneInput = document.querySelector('input[name="telefone"]');
            telefoneInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '').substring(0, 11);
                if (value.length > 0) {
                    value = '(' + value.substring(0, 2) + ') ' + value.substring(2, 7) + '-' + value.substring(7, 11);
                }
                e.target.value = value;
            });

            // Formatação de CNPJ
            const cnpjInput = document.querySelector('input[name="cnpj"]');
            cnpjInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '').substring(0, 14);
                if (value.length > 0) {
                    value = value.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5');
                }
                e.target.value = value;
            });

            // Formatação de CPF
            const cpfInput = document.querySelector('input[name="cpf"]');
            cpfInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '').substring(0, 11);
                if (value.length > 0) {
                    value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})$/, '$1.$2.$3-$4');
                }
                e.target.value = value;
            });

            // Validação de Email
            const emailInput = document.querySelector('input[name="email"]');
            emailInput.addEventListener('input', function(e) {
                if (e.target.value && !e.target.value.includes('@')) {
                    e.target.setCustomValidity('O e-mail deve conter o caractere @.');
                } else {
                    e.target.setCustomValidity('');
                }
            });
        });
    </script>
</head>

<body>
    <?php include 'navbar.php'; ?>
    <div class="container mt-4">
        <h2>Editar Fornecedor</h2>
        <form method="POST" action="gerenciar_fornecedores.php">
            <input type="hidden" name="id" value="<?php echo $fornecedor['id']; ?>">
            <div class="mb-3">
                <label for="nome" class="form-label">Nome do Fornecedor:</label>
                <input type="text" class="form-control" name="nome" value="<?php echo htmlspecialchars($fornecedor['nome']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="telefone" class="form-label">Telefone:</label>
                <input type="text" class="form-control" name="telefone" value="<?php echo htmlspecialchars($fornecedor['telefone'] ?? ''); ?>" required placeholder="Ex.: (11) 99999-9999">
            </div>
            <div class="mb-3">
                <label for="cnpj" class="form-label">CNPJ:</label>
                <input type="text" class="form-control" name="cnpj" value="<?php echo htmlspecialchars($fornecedor['cnpj'] ?? ''); ?>" placeholder="Ex.: 12.345.678/0001-99">
            </div>
            <div class="mb-3">
                <label for="cpf" class="form-label">CPF:</label>
                <input type="text" class="form-control" name="cpf" value="<?php echo htmlspecialchars($fornecedor['cpf'] ?? ''); ?>" placeholder="Ex.: 123.456.789-00">
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">E-mail:</label>
                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($fornecedor['email'] ?? ''); ?>" placeholder="Ex.: fornecedor@exemplo.com">
            </div>
            <div class="mb-3">
                <label for="endereco" class="form-label">Endereço:</label>
                <textarea class="form-control" name="endereco"><?php echo htmlspecialchars($fornecedor['endereco'] ?? ''); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="descrição" class="form-label">Descrição:</label>
                <textarea class="form-control" name="descrição" placeholder="Ex.: Produtos fornecidos"><?php echo htmlspecialchars($fornecedor['descrição'] ?? ''); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="comentarios" class="form-label">Comentários:</label>
                <textarea class="form-control" name="comentarios" placeholder="Ex.: Observações sobre o fornecedor"><?php echo htmlspecialchars($fornecedor['comentarios'] ?? ''); ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
</body>

</html>