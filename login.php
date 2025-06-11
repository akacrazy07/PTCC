<?php
session_start();
require_once 'conexao.php';

// Configurações de bloqueio
$max_tentativas = 3;
$tempo_bloqueio_inicial = 60; // segundos (1 minuto)

if (!isset($_SESSION['tentativas_login'])) {
    $_SESSION['tentativas_login'] = 0;
}
if (!isset($_SESSION['tempo_bloqueio'])) {
    $_SESSION['tempo_bloqueio'] = 0;
}
if (!isset($_SESSION['bloqueio_ate'])) {
    $_SESSION['bloqueio_ate'] = 0;
}

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.html");
    exit();
}

// Verifica se está bloqueado
if (time() < $_SESSION['bloqueio_ate']) {
    $restante = $_SESSION['bloqueio_ate'] - time();
    $minutos = floor($restante / 60);
    $segundos = $restante % 60;
    $erro = "Muitas tentativas de login. Tente novamente em $minutos minuto(s) e $segundos segundo(s).";
    header("Location: login.html?error=" . urlencode($erro));
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
                // Login bem-sucedido: zera tentativas e bloqueio
                $_SESSION['tentativas_login'] = 0;
                $_SESSION['tempo_bloqueio'] = 0;
                $_SESSION['bloqueio_ate'] = 0;
                $_SESSION['usuario_logado'] = true;
                $_SESSION['nome_usuario'] = $usuario_db['usuario'];
                $_SESSION['perfil'] = $usuario_db['perfil'];
                $_SESSION['usuario_id'] = $usuario_db['id'];
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

    // Se houve erro, incrementa tentativas
    if (isset($erro)) {
        $_SESSION['tentativas_login']++;
        if ($_SESSION['tentativas_login'] >= $max_tentativas) {
            // Aplica bloqueio progressivo
            if ($_SESSION['tempo_bloqueio'] == 0) {
                $_SESSION['tempo_bloqueio'] = $tempo_bloqueio_inicial;
            } else {
                $_SESSION['tempo_bloqueio'] *= 2;
            }
            $_SESSION['bloqueio_ate'] = time() + $_SESSION['tempo_bloqueio'];
            $_SESSION['tentativas_login'] = 0; // zera tentativas após bloquear
            $erro = "Muitas tentativas de login. Tente novamente em " . ($_SESSION['tempo_bloqueio'] / 60) . " minuto(s).";
        }
        header("Location: login.html?error=" . urlencode($erro));
        exit();
    }
    $stmt->close();
}
$conexao->close();
?>