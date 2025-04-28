<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
function is_active($pagina)
{
    return basename($_SERVER['PHP_SELF']) === $pagina ? 'active' : '';
}

// Define o tempo limite
$timeout = 3600;

// Registra o horário da última atividade
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    // Se o tempo limite for ultrapassado, destrói a sessão e redireciona para o login
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1"); // Pode adicionar um parâmetro para informar o motivo
    exit;
}

// Atualiza o horário da última atividade
$_SESSION['last_activity'] = time();
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participantes - ICI</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/styles-480.css">
    <link rel="stylesheet" href="assets/css/styles-1366.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>
    <script src="assets/js/global-scripts.js"></script>
    <header id="main-header">
        <div class="logo">
            <!-- Link para a página inicial -->
            <a href="home.php">
                <img id="logo-image" src="assets/images/logo.png" alt="Logo Instituto Céu Interior">
            </a>
        </div>
        <!-- Botão do Menu Hamburguer -->
        <button id="menu-toggle" class="hamburger-menu">
            <i class="fa-solid fa-bars"></i>
        </button>
        <!-- Menu de Navegação -->
        <nav id="main-nav">
            <a href="home.php" class="<?= is_active('home.php') ?>">Home</a>
            <a href="participantes.php" class="<?= is_active('participantes.php') ?>">Participantes</a>
            <a href="rituais.php" class="<?= is_active('rituais.php') ?>">Rituais</a>
            <div class="dropdown">
                <!-- Aplicar classe .active ao span "Perfil" -->
                <span class="<?= basename($_SERVER['PHP_SELF']) === 'perfil.php' ? 'active' : '' ?>">Perfil</span>
                <div class="dropdown-content">
                    <a href="perfil.php">Alterar Senha</a>
                </div>
            </div>
            <a href="logout.php">Sair</a>
        </nav>
    </header>

    <main>