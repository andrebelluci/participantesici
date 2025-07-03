<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../functions/check_auth.php';
require_once __DIR__ . '/../config/database.php';

// ✅ FUNÇÃO PARA VERIFICAR TOKEN DE LEMBRAR-ME
function verificarTokenLembrarMe($pdo)
{
    if (!isset($_COOKIE['remember_token'])) {
        return false;
    }

    $token = $_COOKIE['remember_token'];

    try {
        // Busca token válido no banco
        $stmt = $pdo->prepare("
            SELECT u.id, u.nome, u.usuario
            FROM remember_tokens rt
            JOIN usuarios u ON rt.user_id = u.id
            WHERE rt.token = ? AND rt.expires_at > NOW()
        ");
        $stmt->execute([$token]);
        $result = $stmt->fetch();

        if ($result) {
            // Token válido - restaura sessão
            $_SESSION['user_id'] = $result['id'];
            $_SESSION['nome'] = $result['nome'];
            $_SESSION['last_activity'] = time();

            error_log("[REMEMBER_ME] Sessão restaurada para usuário: {$result['usuario']} (ID: {$result['id']})");
            return true;
        } else {
            // Token inválido/expirado - remove cookie
            setcookie('remember_token', '', time() - 3600, '/');
            error_log("[REMEMBER_ME] Token inválido ou expirado removido");
            return false;
        }

    } catch (Exception $e) {
        error_log("[REMEMBER_ME] Erro ao verificar token: " . $e->getMessage());
        return false;
    }
}

// ✅ VERIFICAÇÃO DE AUTENTICAÇÃO MELHORADA
if (!isset($_SESSION['user_id'])) {
    // Tenta restaurar sessão via cookie de lembrar-me
    if (!verificarTokenLembrarMe($pdo)) {
        // Não está logado e não tem token válido
        header("Location: /login");
        exit;
    }
}

// ✅ VERIFICAÇÃO DE TIMEOUT DE SESSÃO MELHORADA
$timeout = 3600; // 1 hora

// Se tem cookie de lembrar-me, não aplica timeout
$tem_cookie_lembrar = isset($_COOKIE['remember_token']);

if (!$tem_cookie_lembrar && isset($_SESSION['last_activity'])) {
    if ((time() - $_SESSION['last_activity']) > $timeout) {
        // Timeout apenas se não tem cookie de lembrar-me
        session_unset();
        session_destroy();
        header("Location: /login?timeout=1");
        exit;
    }
}

// Atualiza última atividade (sempre)
$_SESSION['last_activity'] = time();

// ✅ FUNÇÃO PARA VERIFICAR PÁGINA ATIVA
function is_active($pagina_url)
{
    $current_url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $base_path = '/';
    $current_relative = str_replace($base_path, '', $current_url);
    return trim($current_relative, '/') === trim($pagina_url, '/') ? 'text-yellow-400' : '';
}


// Verifica se usuário é administrador
$is_admin = false;
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../config/database.php';
    $stmt = $pdo->prepare("
        SELECT p.nome as perfil_nome
        FROM usuarios u
        JOIN perfis p ON u.perfil_id = p.id
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user_perfil = $stmt->fetch();
    $is_admin = $user_perfil && $user_perfil['perfil_nome'] === 'Administrador';
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, viewport-fit=cover, interactive-widget=resizes-content">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#000000">
    <title>Participantes - ICI</title>
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/mobile-fixes.css?v=1.0">
    <script src="/assets/js/global-scripts.js?t=<?= time() ?>"></script>
    <script src="/assets/js/unsaved-changes-detector.js"></script>

</head>

<body class="bg-black min-h-screen flex flex-col mobile-viewport">
    <header class="fixed top-0 left-0 right-0 z-50 bg-black text-white shadow-lg">
        <div class="flex items-center justify-between px-4 py-1 max-w-6xl mx-auto">
            <a href="/home">
                <img src="/assets/images/logo.png" alt="Logo Instituto Céu Interior"
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
                    <a href="/home"
                        class="px-4 py-2 hover:bg-gray-800 <?= basename($_SERVER['PHP_SELF']) === 'home.php' ? 'text-yellow-400' : '' ?>">Home</a>
                    <a href="/participantes"
                        class="px-4 py-2 hover:bg-gray-800 <?= is_active('participantes') ?>">Participantes</a>
                    <a href="/rituais"
                        class="px-4 py-2 hover:bg-gray-800 <?= is_active('rituais') ?>">Rituais</a>
                    <?php if ($is_admin): ?>
                        <a href="/usuarios"
                            class="px-4 py-2 hover:bg-gray-800 <?= is_active('usuarios') ?>">Usuários</a>
                    <?php endif; ?>
                    <a href="/alterar_senha"
                        class="px-4 py-2 hover:bg-gray-800 <?= basename($_SERVER['PHP_SELF']) === 'alterar_senha.php' ? 'text-yellow-400' : '' ?>">Alterar
                        Senha</a>
                    <a href="/logout" class="px-4 py-2 hover:bg-gray-800">Sair</a>
                </nav>
            </div>

            <nav id="main-nav" class="hidden sm:flex space-x-8 items-center">
                <a href="/home"
                    class="hover:text-[#00bfff] <?= basename($_SERVER['PHP_SELF']) === 'home.php' ? 'text-yellow-400' : '' ?>">Home</a>
                <a href="/participantes"
                    class="hover:text-[#00bfff] <?= is_active('participantes') ?>">Participantes</a>
                <a href="/rituais"
                    class="hover:text-[#00bfff] <?= is_active('rituais') ?>">Rituais</a>
                <?php if ($is_admin): ?>
                        <a href="/usuarios" class="hover:text-[#00bfff] <?= is_active('usuarios') ?>">Usuários</a>
                <?php endif; ?>
                <a href="/alterar_senha"
                    class="hover:text-[#00bfff] <?= basename($_SERVER['PHP_SELF']) === 'alterar_senha.php' ? 'text-yellow-400' : '' ?>">
                    Alterar Senha
                </a>
                <a href="/logout" class="hover:text-[#00bfff]">Sair</a>
            </nav>
        </div>
    </header>

    <main class="flex-grow bg-gray-300 pt-[50px] pb-[40px]">