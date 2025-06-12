<?php
session_start();

// Limpa todos os dados da sessão
$_SESSION = [];

// Destrói a sessão completamente
session_destroy();

// Redireciona para a página de login (usando caminho absoluto)
header("Location: /participantesici/public_html/login");
exit;
