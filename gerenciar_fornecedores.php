<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true || !in_array($_SESSION['perfil'], ['admin'])) {
    header("Location: login.html");
    exit();
}

// Função para validar CPF (copiada do gerenciar_usuarios.php)
function validarCPF($cpf)
{
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) != 11) return false;
    if (preg_match('/(\d)\1{10}/', $cpf)) return false;
    for ($t = 9; $t < 11; $t++) {
        $d = 0;
        for ($c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) return false;
    }
    return true;
}

require_once 'conexao.php';
require_once 'funcoes.php';
$mensagem = '';

// Cadastrar ou atualizar fornecedor
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $telefone = $_POST['telefone'];
    $endereco = !empty($_POST['endereco']) ? $_POST['endereco'] : null;
    $cnpj = !empty($_POST['cnpj']) ? $_POST['cnpj'] : null;
    $cpf = !empty($_POST['cpf']) ? $_POST['cpf'] : null;
    $email = !empty($_POST['email']) ? $_POST['email'] : null;
    $descrição = !empty($_POST['descrição']) ? $_POST['descrição'] : null;
    $comentarios = !empty($_POST['comentarios']) ? $_POST['comentarios'] : null;
    $id = !empty($_POST['id']) ? $_POST['id'] : null;

    // Checagem de duplicidade (CPF, CNPJ, Email)
    if ($cpf) {
        $sql_check = "SELECT id FROM fornecedores WHERE cpf = ?".($id ? " AND id != ?" : "");
        $stmt_check = $conexao->prepare($sql_check);
        if ($id) {
            $stmt_check->bind_param("si", $cpf, $id);
        } else {
            $stmt_check->bind_param("s", $cpf);
        }
        $stmt_check->execute();
        $stmt_check->store_result();
        if ($stmt_check->num_rows > 0) {
            $mensagem = "Já existe um fornecedor com este CPF!";
            $stmt_check->close();
            header("Location: gerenciar_fornecedores.php?mensagem=" . urlencode($mensagem));
            exit();
        }
        $stmt_check->close();
    }
    if ($cnpj) {
        $sql_check = "SELECT id FROM fornecedores WHERE cnpj = ?".($id ? " AND id != ?" : "");
        $stmt_check = $conexao->prepare($sql_check);
        if ($id) {
            $stmt_check->bind_param("si", $cnpj, $id);
        } else {
            $stmt_check->bind_param("s", $cnpj);
        }
        $stmt_check->execute();
        $stmt_check->store_result();
        if ($stmt_check->num_rows > 0) {
            $mensagem = "Já existe um fornecedor com este CNPJ!";
            $stmt_check->close();
            header("Location: gerenciar_fornecedores.php?mensagem=" . urlencode($mensagem));
            exit();
        }
        $stmt_check->close();
    }
    if ($email) {
        $sql_check = "SELECT id FROM fornecedores WHERE email = ?".($id ? " AND id != ?" : "");
        $stmt_check = $conexao->prepare($sql_check);
        if ($id) {
            $stmt_check->bind_param("si", $email, $id);
        } else {
            $stmt_check->bind_param("s", $email);
        }
        $stmt_check->execute();
        $stmt_check->store_result();
        if ($stmt_check->num_rows > 0) {
            $mensagem = "Já existe um fornecedor com este e-mail!";
            $stmt_check->close();
            header("Location: gerenciar_fornecedores.php?mensagem=" . urlencode($mensagem));
            exit();
        }
        $stmt_check->close();
    }

    // Validação de CPF (se informado)
    if ($cpf && !validarCPF($cpf)) {
        $mensagem = "CPF inválido!";
        header("Location: gerenciar_fornecedores.php?mensagem=" . urlencode($mensagem));
        exit();
    }

    if ($id) {
        // Atualizar fornecedor existente
        $sql = "UPDATE fornecedores SET nome = ?, telefone = ?, endereco = ?, cnpj = ?, cpf = ?, email = ?, descrição = ?, comentarios = ? WHERE id = ?";
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("ssssssssi", $nome, $telefone, $endereco, $cnpj, $cpf, $email, $descrição, $comentarios, $id);
    } else {
        // Cadastrar novo fornecedor
        $sql = "INSERT INTO fornecedores (nome, telefone, endereco, cnpj, cpf, email, descrição, comentarios) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("ssssssss", $nome, $telefone, $endereco, $cnpj, $cpf, $email, $descrição, $comentarios);
    }
    if ($stmt->execute()) {
        $mensagem = $id ? "Fornecedor atualizado com sucesso!" : "Fornecedor cadastrado com sucesso!";
        $acao = "Cadastrou novo fornecedor de nome {$nome}";
        registrarLog($conexao, $_SESSION['usuario_id'], $acao);
    } else {
        $mensagem = "Erro: " . $conexao->error;
    }
    $stmt->close();
    header("Location: gerenciar_fornecedores.php?mensagem=" . urlencode($mensagem));
    exit();
}

// Excluir fornecedor
if (isset($_GET['excluir'])) {
    $id = $_GET['excluir'];
    $conexao->begin_transaction();
    try {
        // Deletar pedidos associados em pedidos_fornecedores
        $sql_pedidos = "DELETE FROM pedidos_fornecedores WHERE fornecedor_id = ?";
        $stmt_pedidos = $conexao->prepare($sql_pedidos);
        $stmt_pedidos->bind_param("i", $id);
        $stmt_pedidos->execute();
        $stmt_pedidos->close();

        // Deletar produtos associados (que deletará vendas automaticamente via CASCADE)
        $sql_produtos = "DELETE FROM produtos WHERE fornecedor_id = ?";
        $stmt_produtos = $conexao->prepare($sql_produtos);
        $stmt_produtos->bind_param("i", $id);
        $stmt_produtos->execute();
        $stmt_produtos->close();

        // Deletar produtos associados em produtos_fornecedores
        $sql_produtos_fornecedores = "DELETE FROM produtos_fornecedores WHERE fornecedor_id = ?";
        $stmt_produtos_fornecedores = $conexao->prepare($sql_produtos_fornecedores);
        $stmt_produtos_fornecedores->bind_param("i", $id);
        $stmt_produtos_fornecedores->execute();
        $stmt_produtos_fornecedores->close();

        // Deletar o fornecedor
        $sql_fornecedor = "DELETE FROM fornecedores WHERE id = ?";
        $stmt_fornecedor = $conexao->prepare($sql_fornecedor);
        $stmt_fornecedor->bind_param("i", $id);
        $stmt_fornecedor->execute();
        $stmt_fornecedor->close();

        $conexao->commit();
        $mensagem = "Fornecedor excluído com sucesso!";
        $acao = "Excluiu o fornecedor ID {$id}";
        registrarLog($conexao, $_SESSION['usuario_id'], $acao);
    } catch (Exception $e) {
        $conexao->rollback();
        $mensagem = "Erro ao excluir fornecedor: " . $e->getMessage();
    }
    header("Location: gerenciar_fornecedores.php?mensagem=" . urlencode($mensagem));
    exit();
}

// Listar fornecedores
$sql_fornecedores = "SELECT * FROM fornecedores";
$resultado_fornecedores = $conexao->query($sql_fornecedores);
$fornecedores = [];
while ($row = $resultado_fornecedores->fetch_assoc()) {
    $fornecedores[] = $row;
}

$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Fornecedores - Panificadora</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Formatação de Telefone
            const telefoneInput = document.querySelector('input[name="telefone"]');
            telefoneInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '').substring(0, 11); // Limita a 11 números
                if (value.length > 0) {
                    value = '(' + value.substring(0, 2) + ') ' + value.substring(2, 7) + '-' + value.substring(7, 11);
                }
                e.target.value = value;
            });

            // Formatação de CNPJ
            const cnpjInput = document.querySelector('input[name="cnpj"]');
            cnpjInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '').substring(0, 14); // Limita a 14 números
                if (value.length > 0) {
                    value = value.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5');
                }
                e.target.value = value;
            });

            // Formatação de CPF
            const cpfInput = document.querySelector('input[name="cpf"]');
            cpfInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '').substring(0, 11); // Limita a 11 números
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
    <div class="container">
        <!-- Mensagem de feedback -->
        <?php if (isset($_GET['mensagem'])): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($_GET['mensagem']); ?></div>
        <?php endif; ?>

        <!-- Formulário para cadastrar/editar fornecedor -->
        <h3>Cadastrar Fornecedor</h3>
        <form method="POST" action="gerenciar_fornecedores.php">
            <input type="hidden" name="id" value="">
            <div class="mb-3">
                <label for="nome" class="form-label">Nome do Fornecedor:</label>
                <input type="text" class="form-control" name="nome" required>
            </div>
            <div class="mb-3">
                <label for="telefone" class="form-label">Telefone:</label>
                <input type="text" class="form-control" name="telefone" required placeholder="Ex.: (11) 99999-9999">
            </div>
            <div class="mb-3">
                <label for="cnpj" class="form-label">CNPJ (opcional):</label>
                <input type="text" class="form-control" name="cnpj" placeholder="Ex.: 12.345.678/0001-99">
            </div>
            <div class="mb-3">
                <label for="cpf" class="form-label">CPF:</label>
                <input type="text" class="form-control" name="cpf" placeholder="Ex.: 123.456.789-00">
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">E-mail (opcional):</label>
                <input type="email" class="form-control" name="email" placeholder="Ex.: fornecedor@exemplo.com">
            </div>
            <div class="mb-3">
                <label for="endereco" class="form-label">Endereço</label>
                <textarea class="form-control" name="endereco"></textarea>
            </div>
            <div class="mb-3">
                <label for="descrição" class="form-label">Descrição</label>
                <textarea class="form-control" name="descrição" placeholder="Ex.: Produtos fornecidos"></textarea>
            </div>
            <div class="mb-3">
                <label for="comentarios" class="form-label">Comentários (opcional):</label>
                <textarea class="form-control" name="comentarios" placeholder="Ex.: Observações sobre o fornecedor"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Salvar Fornecedor</button>
        </form>

        <!-- Listar fornecedores -->
        <h3 class="mt-5">Fornecedores Cadastrados</h3>
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Nome</th>
                    <th>Telefone</th>
                    <th>CNPJ</th>
                    <th>CPF</th>
                    <th>E-mail</th>
                    <th>Endereço</th>
                    <th>Descrição</th>
                    <th>Comentários</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($fornecedores)): ?>
                    <?php foreach ($fornecedores as $fornecedor): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($fornecedor['nome']); ?></td>
                            <td><?php echo htmlspecialchars($fornecedor['telefone']); ?></td>
                            <td><?php echo $fornecedor['cnpj'] ? htmlspecialchars($fornecedor['cnpj']) : '-'; ?></td>
                            <td><?php echo $fornecedor['cpf'] ? htmlspecialchars($fornecedor['cpf']) : '-'; ?></td>
                            <td><?php echo $fornecedor['email'] ? htmlspecialchars($fornecedor['email']) : '-'; ?></td>
                            <td><?php echo $fornecedor['endereco'] ? htmlspecialchars($fornecedor['endereco']) : '-'; ?></td>
                            <td><?php echo $fornecedor['descrição'] ? htmlspecialchars($fornecedor['descrição']) : '-'; ?></td>
                            <td><?php echo $fornecedor['comentarios'] ? htmlspecialchars($fornecedor['comentarios']) : '-'; ?></td>
                            <td>
                                <a href="editar_fornecedor.php?id=<?php echo $fornecedor['id']; ?>" class="btn btn-primary btn-sm">Editar</a>
                                <a href="gerenciar_fornecedores.php?excluir=<?php echo $fornecedor['id']; ?>"
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Tem certeza que deseja excluir este fornecedor? Todos os pedidos e produtos associados serão excluídos.')">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9">Nenhum fornecedor cadastrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
</body>

</html>