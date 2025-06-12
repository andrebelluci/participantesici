<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../functions/check_auth.php';
function is_active($pagina_url)
{
    // Pega a URL atual (ex: '/participantesici/public_html/rituais')
    $current_url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    // Remove a base se necessário
    $base_path = '/participantesici/public_html/';
    $current_relative = str_replace($base_path, '', $current_url);

    // Compara com a URL passada (ex: 'rituais')
    return trim($current_relative, '/') === trim($pagina_url, '/') ? 'active' : '';
}

// Define o tempo limite
$timeout = 3600;

// Registra o horário da última atividade
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    // Se o tempo limite for ultrapassado, destrói a sessão e redireciona para o login
    session_unset();
    session_destroy();
    header("Location: /participantesici/public_html/login?timeout=1"); // Pode adicionar um parâmetro para informar o motivo
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
    <link rel="icon" type="image/x-icon" href="/participantesici/public_html/assets/images/favicon.ico">
    <link rel="stylesheet" href="/participantesici/public_html/assets/css/styles.css">
    <link rel="stylesheet" href="/participantesici/public_html/assets/css/styles-480.css">
    <link rel="stylesheet" href="/participantesici/public_html/assets/css/styles-1366.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>
    <script src="/participantesici/public_html/assets/js/global-scripts.js"></script>
    <header id="main-header">
        <div class="logo">
            <!-- Link para a página inicial -->
            <a href="/participantesici/public_html/home">
                <img id="logo-image" src="/participantesici/public_html/assets/images/logo.png"
                    alt="Logo Instituto Céu Interior">
            </a>
        </div>
        <!-- Botão do Menu Hamburguer -->
        <button id="menu-toggle" class="hamburger-menu">
            <i class="fa-solid fa-bars"></i>
        </button>
        <!-- Menu de Navegação -->
        <nav id="main-nav">
            <a href="/participantesici/public_html/home" class="<?= basename($_SERVER['PHP_SELF']) === 'home.php' ? 'active' : '' ?>">Home</a>
            <a href="/participantesici/public_html/participantes" class="<?= is_active('participantes') ?>">Participantes</a>
            <a href="/participantesici/public_html/rituais" class="<?= is_active('rituais') ?>">Rituais</a>
            <div class="dropdown">
                <!-- Aplicar classe .active ao span "Perfil" -->
                <span class="<?= basename($_SERVER['PHP_SELF']) === 'alterar_senha.php' ? 'active' : '' ?>">Perfil</span>
                <div class="dropdown-content">
                    <a href="/participantesici/public_html/alterar_senha">Alterar Senha</a>
                </div>
            </div>
            <a href="/participantesici/public_html/logout">Sair</a>
        </nav>
    </header>

    <main>