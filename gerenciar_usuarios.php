<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true || $_SESSION['perfil'] !== 'admin') {
    header("Location: login.html");
    exit();
}
require_once 'conexao.php';

// Função para validar CPF
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

// Função para verificar a senha do admin primário
function verificarSenhaAdminPrimario($conexao, $senha_fornecida)
{
    $stmt = $conexao->prepare("SELECT senha FROM usuarios WHERE id = 1 AND perfil = 'admin'");
    $stmt->execute();
    $resultado = $stmt->get_result();
    if ($resultado->num_rows > 0) {
        $admin_primario = $resultado->fetch_assoc();
        $stmt->close();
        return password_verify($senha_fornecida, $admin_primario['senha']);
    }
    $stmt->close();
    return false;
}

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['adicionar'])) {
    $usuario = $_POST['usuario'];
    $cpf = $_POST['cpf'];
    $email = $_POST['email'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $perfil = $_POST['perfil'];

    // Checagem de duplicidade (usuario, CPF, Email)
    $stmt_check = $conexao->prepare("SELECT id FROM usuarios WHERE usuario = ? OR cpf = ? OR email = ?");
    $stmt_check->bind_param("sss", $usuario, $cpf, $email);
    $stmt_check->execute();
    $stmt_check->store_result();
    if ($stmt_check->num_rows > 0) {
        $mensagem = "Já existe um usuário com este nome, CPF ou e-mail!";
        $stmt_check->close();
    } else {
        $stmt_check->close();
        if (!validarCPF($cpf)) {
            $mensagem = "CPF inválido!";
        } else {
            $stmt = $conexao->prepare("INSERT INTO usuarios (usuario, cpf, email, senha, perfil) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $usuario, $cpf, $email, $senha, $perfil);
            if ($stmt->execute()) {
                $mensagem = "Usuário adicionado com sucesso!";
                $usuario_id = $_SESSION['usuario_id'];
                $acao = "Adicionou usuário '$usuario' ($perfil)";
                $stmt_log = $conexao->prepare("INSERT INTO logs (usuario_id, acao) VALUES (?, ?)");
                $stmt_log->bind_param("is", $usuario_id, $acao);
                $stmt_log->execute();
                $stmt_log->close();
            } else {
                $mensagem = "Erro ao adicionar usuário: " . $conexao->error;
            }
            $stmt->close();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['excluir'])) {
    $id_excluir = intval($_POST['excluir']);
    $stmt = $conexao->prepare("SELECT usuario, perfil FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id_excluir);
    $stmt->execute();
    $usuario_excluido = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($usuario_excluido['perfil'] === 'admin') {
        if (isset($_POST['senha_admin']) && verificarSenhaAdminPrimario($conexao, $_POST['senha_admin'])) {
            $stmt = $conexao->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->bind_param("i", $id_excluir);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $mensagem = "Usuário excluído com sucesso!";
                $usuario_id = $_SESSION['usuario_id'];
                $acao = "Excluiu usuário '{$usuario_excluido['usuario']}' ({$usuario_excluido['perfil']})";
                $stmt_log = $conexao->prepare("INSERT INTO logs (usuario_id, acao) VALUES (?, ?)");
                $stmt_log->bind_param("is", $usuario_id, $acao);
                $stmt_log->execute();
                $stmt_log->close();
            } else {
                $mensagem = "Erro ao excluir usuário.";
            }
            $stmt->close();
        } else {
            $mensagem = "Senha do admin primário incorreta ou não fornecida!";
        }
    } else {
        $stmt = $conexao->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $id_excluir);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $mensagem = "Usuário excluído com sucesso!";
            $usuario_id = $_SESSION['usuario_id'];
            $acao = "Excluiu usuário '{$usuario_excluido['usuario']}' ({$usuario_excluido['perfil']})";
            $stmt_log = $conexao->prepare("INSERT INTO logs (usuario_id, acao) VALUES (?, ?)");
            $stmt_log->bind_param("is", $usuario_id, $acao);
            $stmt_log->execute();
            $stmt_log->close();
        } else {
            $mensagem = "Erro ao excluir usuário.";
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editar'])) {
    $id = intval($_POST['id']);
    $usuario = $_POST['usuario'];
    $cpf = $_POST['cpf'];
    $email = $_POST['email'];
    $perfil = $_POST['perfil'];
    $senha = !empty($_POST['senha']) ? password_hash($_POST['senha'], PASSWORD_DEFAULT) : null;

    $stmt = $conexao->prepare("SELECT perfil FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $usuario_editado = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!validarCPF($cpf)) {
        $mensagem = "CPF inválido!";
    } elseif ($usuario_editado['perfil'] === 'admin' && (!isset($_POST['senha_admin']) || !verificarSenhaAdminPrimario($conexao, $_POST['senha_admin']))) {
        $mensagem = "Senha do admin primário incorreta ou não fornecida!";
    } else {
        $sql = "UPDATE usuarios SET usuario = ?, cpf = ?, email = ?, perfil = ?";
        if ($senha) {
            $sql .= ", senha = ?";
        }
        $sql .= " WHERE id = ?";
        $stmt = $conexao->prepare($sql);
        if ($senha) {
            $stmt->bind_param("sssssi", $usuario, $cpf, $email, $perfil, $senha, $id);
        } else {
            $stmt->bind_param("ssssi", $usuario, $cpf, $email, $perfil, $id);
        }
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $mensagem = "Usuário atualizado com sucesso!";
            $usuario_id = $_SESSION['usuario_id'];
            $acao = "Editou usuário '$usuario' ($perfil)";
            $stmt_log = $conexao->prepare("INSERT INTO logs (usuario_id, acao) VALUES (?, ?)");
            $stmt_log->bind_param("is", $usuario_id, $acao);
            $stmt_log->execute();
            $stmt_log->close();
        } else {
            $mensagem = "Erro ao editar usuário.";
        }
        $stmt->close();
    }
}

$usuarios = $conexao->query("SELECT id, usuario, cpf, email, perfil FROM usuarios")->fetch_all(MYSQLI_ASSOC);
$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Panificadora</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container-custom {
            width: 90%;
            max-width: 1200px;
            margin: 40px auto;
        }

        .form-group-spacing input,
        .form-group-spacing select {
            margin: 5px 0;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 400px;
            border-radius: 5px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>
    <div class="container container-custom">
        <?php if (!empty($mensagem)): ?>
            <p class="mensagem <?php echo strpos($mensagem, 'sucesso') !== false ? 'mensagem-success' : 'mensagem-error'; ?>">
                <?php echo $mensagem; ?>
            </p>
        <?php endif; ?>
        <h3>Adicionar Usuário</h3>
        <form action="gerenciar_usuarios.php" method="post" class="form-group-spacing">
            <input type="text" name="usuario" placeholder="Usuário" required>
            <input type="text" name="cpf" placeholder="CPF (ex: 123.456.789-00)" required oninput="formatarCPF(this)">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="senha" placeholder="Senha" required>
            <select name="perfil" required class="form-select">
                <option value="admin">Admin</option>
                <option value="gerente">Gerente</option>
                <option value="vendedor">Vendedor</option>
            </select>
            <button type="submit" name="adicionar" class="btn btn-primary">Adicionar</button>
        </form> <br>
        <h3>Usuários Cadastrados</h3>
        <?php if (empty($usuarios)): ?>
            <p>Nenhum usuário cadastrado.</p>
        <?php else: ?>
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Usuário</th>
                        <th>CPF</th>
                        <th>Email</th>
                        <th>Perfil</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($usuario['usuario']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['cpf']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['perfil']); ?></td>
                            <td>
                                <button class="btn btn-primary btn-sm" onclick="abrirModalEditar(<?php echo $usuario['id']; ?>, '<?php echo $usuario['perfil']; ?>', '<?php echo htmlspecialchars($usuario['usuario']); ?>', '<?php echo htmlspecialchars($usuario['cpf']); ?>', '<?php echo htmlspecialchars($usuario['email']); ?>')">Editar</button>
                                <button class="btn btn-danger btn-sm" onclick="abrirModalExcluir(<?php echo $usuario['id']; ?>, '<?php echo $usuario['perfil']; ?>')">Excluir</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Modal para confirmação de exclusão -->
    <div id="modalExcluir" class="modal">
        <div class="modal-content">
            <span class="close" onclick="fecharModalExcluir()">×</span>
            <h3>Confirmação de Exclusão</h3>
            <p>Tem certeza que quer excluir este usuário?</p>
            <form id="formExcluir" action="gerenciar_usuarios.php" method="post">
                <input type="hidden" name="excluir" id="excluirId">
                <div id="senhaAdminExcluir" style="display: none;">
                    <p>Insira a senha do admin primário:</p>
                    <input type="password" name="senha_admin" id="senhaAdminInputExcluir">
                </div>
                <button type="submit" class="btn btn-danger">Confirmar Exclusão</button>
            </form>
        </div>
    </div>

    <!-- Modal para edição -->
    <div id="modalEditar" class="modal">
        <div class="modal-content">
            <span class="close" onclick="fecharModalEditar()">×</span>
            <h3>Editar Usuário</h3>
            <form id="formEditar" action="gerenciar_usuarios.php" method="post">
                <input type="hidden" name="id" id="editarId">
                <input type="text" name="usuario" id="editarUsuario" required>
                <input type="text" name="cpf" id="editarCpf" required oninput="formatarCPF(this)">
                <input type="email" name="email" id="editarEmail" required>
                <select name="perfil" id="editarPerfil" required class="form-select">
                    <option value="admin">Admin</option>
                    <option value="gerente">Gerente</option>
                    <option value="vendedor">Vendedor</option>
                </select>
                <input type="password" name="senha" placeholder="Nova senha (opcional)">
                <div id="senhaAdminEditar" style="display: none;">
                    <p>Insira a senha do admin primário:</p>
                    <input type="password" name="senha_admin" id="senhaAdminInputEditar">
                </div>
                <br>
                <button type="submit" name="editar" class="btn btn-success">Salvar</button>
            </form>
        </div>
    </div>

    <script>
        function formatarCPF(campo) {
            let value = campo.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);

            if (value.length > 9) {
                value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
            } else if (value.length > 6) {
                value = value.replace(/(\d{3})(\d{3})(\d{3})/, '$1.$2.$3');
            } else if (value.length > 3) {
                value = value.replace(/(\d{3})(\d{3})/, '$1.$2');
            } else if (value.length > 0) {
                value = value.replace(/(\d{3})/, '$1');
            }

            campo.value = value;
        }

        function abrirModalExcluir(id, perfil) {
            document.getElementById('excluirId').value = id;
            const senhaAdminDiv = document.getElementById('senhaAdminExcluir');
            const senhaAdminInput = document.getElementById('senhaAdminInputExcluir');
            if (perfil === 'admin') {
                senhaAdminDiv.style.display = 'block';
                senhaAdminInput.required = true;
            } else {
                senhaAdminDiv.style.display = 'none';
                senhaAdminInput.required = false;
            }
            document.getElementById('modalExcluir').style.display = 'block';
        }

        function fecharModalExcluir() {
            document.getElementById('modalExcluir').style.display = 'none';
        }

        function abrirModalEditar(id, perfil, usuario, cpf, email) {
            document.getElementById('editarId').value = id;
            document.getElementById('editarUsuario').value = usuario;
            document.getElementById('editarCpf').value = cpf;
            document.getElementById('editarEmail').value = email;
            document.getElementById('editarPerfil').value = perfil;
            const senhaAdminDiv = document.getElementById('senhaAdminEditar');
            const senhaAdminInput = document.getElementById('senhaAdminInputEditar');
            if (perfil === 'admin') {
                senhaAdminDiv.style.display = 'block';
                senhaAdminInput.required = true;
            } else {
                senhaAdminDiv.style.display = 'none';
                senhaAdminInput.required = false;
            }
            document.getElementById('modalEditar').style.display = 'block';
        }

        function fecharModalEditar() {
            document.getElementById('modalEditar').style.display = 'none';
        }

        // Fechar modal ao clicar fora
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                fecharModalExcluir();
                fecharModalEditar();
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
</body>

</html>