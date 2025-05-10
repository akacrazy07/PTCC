<?php
session_start();
require_once 'conexao.php';

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = $_POST['usuario'];
    $cpf = $_POST['cpf'];
    $senha = $_POST['senha'];

    if (empty($usuario) || empty($cpf) || empty($senha)) {
        $erro = "Todos os campos são obrigatórios.";
    } else {
        $sql = "SELECT * FROM usuarios WHERE usuario = ? AND cpf = ?";
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param('ss', $usuario, $cpf);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado && $resultado->num_rows > 0) {
            $usuario_db = $resultado->fetch_assoc();
            if (password_verify($senha, $usuario_db['senha'])) {
                $_SESSION['usuario_logado'] = true;
                $_SESSION['nome_usuario'] = $usuario_db['usuario'];
                $_SESSION['perfil'] = $usuario_db['perfil'];
                $_SESSION['usuario_id'] = $usuario_db['id'];
                // Exibir mensagem de boas-vindas antes de redirecionar
                $welcome_message = "Bem-vindo(a) " . ucfirst($usuario_db['perfil']) . " " . $usuario_db['usuario'] . "!";
                echo "<script>alert('$welcome_message'); window.location.href='controle_estoque.php';</script>";
                exit();
            } else {
                $erro = "Senha incorreta.";
            }
        } else {
            $erro = "Usuário ou CPF não encontrado.";
        }
    }

    if (isset($erro)) {
        header("Location: login.html?error=" . urlencode($erro));
        exit();
    }
    $stmt->close();
}
$conexao->close();
?>