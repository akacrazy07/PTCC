<?php
session_start();
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = $_POST['usuario'];
    $senha = $_POST['senha'];
}

$sql = "SELECT * FROM usuarios WHERE usuario = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param('s', $usuario);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado && $resultado->num_rows > 0) {
    $usuario_db = $resultado->fetch_assoc();
    if (password_verify($senha,$usuario_db['senha'])) {
        $_SESSION['usuario_logado'] = true;
        $_SESSION['nome_usuario'] = $usuario_db['usuario'];
        header ("Location: controle_estoque.php");
        exit();
    } else {
        $erro = "Senha incorreta.";
    }
} else {
    $erro = "Usuário não encontrado.";
}
if (isset($erro)) {
    echo "<script>alert ('$erro'); window.location.href='login.html';</script>";
    exit();
}
$stmt->close();
$conexao->close();
?>