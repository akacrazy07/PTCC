<?php
function registrarLog($conexao, $usuario_id, $acao, $detalhes = null) {
    if (!$usuario_id) {
        $usuario_id = 0; // Usuário anônimo ou não logado
    }
    
    $sql = "INSERT INTO logs (usuario_id, acao, data_acao) VALUES (?, ?, NOW())";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("is", $usuario_id, $acao);
    $stmt->execute();
    $stmt->close();
}
?>