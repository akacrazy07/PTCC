<?php
$senha = '12345';
$hash = password_hash($senha, PASSWORD_DEFAULT);
echo $hash;
?>