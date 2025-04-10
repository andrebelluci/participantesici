<?php
// Exemplo de criação de senha SHA2
$senha = "admin";
$senhaHash = hash('sha256', $senha);
echo $senhaHash;
?>