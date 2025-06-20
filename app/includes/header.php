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
    return trim($current_relative, '/') === trim($pagina_url, '/') ? 'text-yellow-400' : '';
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="https://cdn.tailwindcss.com"></script>

</head>

<body class="bg-black min-h-screen flex flex-col">
    <header class="fixed top-0 left-0 right-0 z-50 bg-black text-white shadow-lg">
        <div class="flex items-center justify-between px-4 py-1 max-w-6xl mx-auto">
            <a href="/participantesici/public_html/home">
                <img src="/participantesici/public_html/assets/images/logo.png" alt="Logo Instituto Céu Interior"
                    class="h-10">
            </a>

            <!-- Botão Hamburguer -->
            <div class="sm:hidden relative">
                <button id="menu-toggle" class="text-white">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>

                <!-- Menu Mobile -->
                <nav id="mobile-nav"
                    class="hidden absolute right-0 mt-2 w-48 bg-black text-white shadow-md rounded-md z-50 flex flex-col"
                    aria-label="Menu de navegação mobile">
                    <a href="/participantesici/public_html/home"
                        class="px-4 py-2 hover:bg-gray-800 <?= basename($_SERVER['PHP_SELF']) === 'home.php' ? 'text-yellow-400' : '' ?>">Home</a>
                    <a href="/participantesici/public_html/participantes"
                        class="px-4 py-2 hover:bg-gray-800 <?= is_active('participantes') ?>">Participantes</a>
                    <a href="/participantesici/public_html/rituais"
                        class="px-4 py-2 hover:bg-gray-800 <?= is_active('rituais') ?>">Rituais</a>
                    <a href="/participantesici/public_html/alterar_senha"
                        class="px-4 py-2 hover:bg-gray-800 <?= basename($_SERVER['PHP_SELF']) === 'alterar_senha.php' ? 'text-yellow-400' : '' ?>">Alterar
                        Senha</a>
                    <a href="/participantesici/public_html/logout" class="px-4 py-2 hover:bg-gray-800">Sair</a>
                </nav>
            </div>


            <nav id="main-nav" class="hidden sm:flex space-x-8 items-center">
                <a href="/participantesici/public_html/home"
                    class="hover:text-[#00bfff] <?= basename($_SERVER['PHP_SELF']) === 'home.php' ? 'text-yellow-400' : '' ?>">Home</a>
                <a href="/participantesici/public_html/participantes"
                    class="hover:text-[#00bfff] <?= is_active('participantes') ?>">Participantes</a>
                <a href="/participantesici/public_html/rituais"
                    class="hover:text-[#00bfff] <?= is_active('rituais') ?>">Rituais</a>
                <a href="/participantesici/public_html/alterar_senha"
                    class="hover:text-[#00bfff] <?= basename($_SERVER['PHP_SELF']) === 'alterar_senha.php' ? 'text-yellow-400' : '' ?>">
                    Alterar Senha
                </a>
                <a href="/participantesici/public_html/logout" class="hover:text-[#00bfff]">Sair</a>
            </nav>
        </div>
    </header>

    <main class="flex-grow bg-gray-300 pt-[50px]">