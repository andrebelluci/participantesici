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
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participantes - ICI</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link rel="stylesheet" href="assets/css/styles.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>
    <header id="main-header">
        <div class="logo">
            <!-- Link para a página inicial -->
            <a href="home.php">
                <img id="logo-image" src="assets/images/logo.png" alt="Logo Instituto Céu Interior">
            </a>
        </div>
        <nav>
            <a href="home.php" class="<?= is_active('home.php') ?>">Home</a>
            <a href="rituais.php" class="<?= is_active('rituais.php') ?>">Rituais</a>
            <a href="participantes.php" class="<?= is_active('participantes.php') ?>">Participantes</a>
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