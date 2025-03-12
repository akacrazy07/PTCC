<?php
$db_host = 'localhost'; // endereço do servidor de banco de dados
$db_nome = 'panificadora_db'; // nome do banco de dados
$db_usuario = 'root'; // usuário do banco de dados
$db_senha = ''; // senha do banco de dados

$conexao = new mysqli($db_host, $db_usuario, $db_senha, $db_nome);

if ($conexao->connect_error) {
    die ("Erro de conexão: " . $conexao->connect_error);
}
?>