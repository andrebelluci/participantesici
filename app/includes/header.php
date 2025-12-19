<?php
// ✅ Configura sessão com cookie persistente se não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    // Configura parâmetros ANTES de iniciar a sessão
    // Cookie de sessão com expiração muito longa quando há remember_token
    $session_lifetime = isset($_COOKIE['remember_token']) ? (10 * 365 * 24 * 60 * 60) : (8 * 60 * 60); // 10 anos ou 8 horas
    ini_set('session.gc_maxlifetime', $session_lifetime);
    ini_set('session.cookie_lifetime', $session_lifetime);

    session_set_cookie_params([
        'lifetime' => $session_lifetime,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
} else {
    // ✅ Se sessão já está ativa, só faz log
    error_log("[HEADER] Sessão já ativa - configurações aplicadas anteriormente");
}

require_once __DIR__ . '/../functions/check_auth.php';
require_once __DIR__ . '/../config/database.php';

// ✅ FUNÇÃO MELHORADA PARA VERIFICAR TOKEN DE LEMBRAR-ME
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
            // Token válido - restaura/renova sessão
            $_SESSION['user_id'] = $result['id'];
            $_SESSION['nome'] = $result['nome'];
            $_SESSION['last_activity'] = time();
            $_SESSION['login_method'] = 'remember_me'; // Marca como login via token

            // ✅ RENOVAR TOKEN para mais 10 anos (automático - persistente)
            $novo_token = bin2hex(random_bytes(32));
            $nova_expiracao = date('Y-m-d H:i:s', time() + (10 * 365 * 24 * 60 * 60));

            // Atualiza token no banco
            $stmt_update = $pdo->prepare("
                UPDATE remember_tokens
                SET token = ?, expires_at = ?
                WHERE user_id = ?
            ");
            $stmt_update->execute([$novo_token, $nova_expiracao, $result['id']]);

            // ✅ Atualiza cookie com expiração muito longa (10 anos)
            $cookie_expiry = time() + (10 * 365 * 24 * 60 * 60);

            if (
                $_SERVER['HTTP_HOST'] === 'localhost' ||
                strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false ||
                strpos($_SERVER['HTTP_HOST'], '.local') !== false
            ) {

                setcookie(
                    'remember_token',
                    $novo_token,
                    $cookie_expiry,
                    '/',
                    '',
                    false, // HTTP local
                    true
                );
            } else {
                setcookie(
                    'remember_token',
                    $novo_token,
                    [
                        'expires' => $cookie_expiry,
                        'path' => '/',
                        'domain' => '',
                        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]
                );
            }

            error_log("[REMEMBER_ME] Sessão restaurada e token renovado para usuário: {$result['usuario']} (ID: {$result['id']}) - persistente até limpar cache");
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
    // Tenta restaurar sessão via cookie de manter conectado
    if (!verificarTokenLembrarMe($pdo)) {
        // Não está logado e não tem token válido
        header("Location: /login");
        exit;
    }
} else if (isset($_COOKIE['remember_token']) && (!isset($_SESSION['login_method']) || $_SESSION['login_method'] !== 'remember_me')) {
    // Se tem cookie mas sessão não está marcada como remember_me, marca agora
    $_SESSION['login_method'] = 'remember_me';
}

// ✅ VERIFICAÇÃO DE TIMEOUT SIMPLIFICADA
// Se tem cookie de "manter conectado", não aplica timeout - sessão persiste até limpar cache
$tem_cookie_lembrar = isset($_COOKIE['remember_token']);
$login_via_remember = isset($_SESSION['login_method']) && $_SESSION['login_method'] === 'remember_me';

// Timeout apenas para login normal (sem "manter conectado")
$timeout_normal = 8 * 60 * 60; // 8 horas para login normal

if (isset($_SESSION['last_activity']) && !$tem_cookie_lembrar && !$login_via_remember) {
    // Aplica timeout apenas se NÃO tiver cookie de manter conectado
    if ((time() - $_SESSION['last_activity']) > $timeout_normal) {
        error_log("[SESSION] Timeout de sessão normal - fazendo logout");
<<<<<<< HEAD
<<<<<<< HEAD
=======
>>>>>>> 17c2916 (feat: Melhorias no sistema de inscrições e visualização de documentos)
                session_unset();
                session_destroy();
                header("Location: /login?timeout=1");
                exit;
            }
<<<<<<< HEAD
=======
        session_unset();
        session_destroy();
        header("Location: /login?timeout=1");
        exit;
    }
>>>>>>> 94fb5aa (feat: Melhorias no sistema de inscrições e visualização de documentos)
=======
>>>>>>> 17c2916 (feat: Melhorias no sistema de inscrições e visualização de documentos)
} else if ($tem_cookie_lembrar && isset($_SESSION['last_activity'])) {
    // Se tem cookie mas sessão expirou, tenta renovar automaticamente
    // Não aplica timeout rigoroso - cookie mantém conectado
    if ((time() - $_SESSION['last_activity']) > (24 * 60 * 60)) { // Renova se inativo por mais de 24h
        error_log("[SESSION] Renovando sessão via cookie de manter conectado...");
        if (verificarTokenLembrarMe($pdo)) {
            error_log("[SESSION] Sessão renovada com sucesso");
            $_SESSION['last_activity'] = time();
        }
    }
}

// Atualiza última atividade (sempre)
$_SESSION['last_activity'] = time();

// ✅ Debug para PWA (remover em produção se desejar)
if (isset($_GET['debug_pwa'])) {
    error_log("=== DEBUG PWA SESSION ===");
    error_log("session.gc_maxlifetime: " . ini_get('session.gc_maxlifetime') . " (" . (ini_get('session.gc_maxlifetime') / 86400) . " dias)");
    error_log("session.cookie_lifetime: " . ini_get('session.cookie_lifetime') . " (" . (ini_get('session.cookie_lifetime') / 86400) . " dias)");
    error_log("Cookie remember_token: " . (isset($_COOKIE['remember_token']) ? 'EXISTE' : 'NÃO EXISTE'));
    error_log("Sessão user_id: " . ($_SESSION['user_id'] ?? 'NÃO DEFINIDO'));
    error_log("Login method: " . ($_SESSION['login_method'] ?? 'NÃO DEFINIDO'));
    error_log("Última atividade: " . ($_SESSION['last_activity'] ?? 'NÃO DEFINIDO'));

    if (isset($_SESSION['last_activity'])) {
        $tempo_inativo = time() - $_SESSION['last_activity'];
        error_log("Tempo inativo: " . $tempo_inativo . " segundos (" . round($tempo_inativo / 3600, 2) . " horas)");
        error_log("Timeout configurado: " . (($tem_cookie_lembrar || $login_via_remember) ? $timeout_remember : $timeout_normal) . " segundos");
    }
    error_log("=== FIM DEBUG PWA ===");
}

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
    <link rel="stylesheet" href="/assets/css/tailwind.css?v=1.0">
    <link rel="stylesheet" href="/assets/css/mobile-fixes.css?v=1.0">
    <script src="/assets/js/global-scripts.js?t=<?= time() ?>"></script>
    <script src="/assets/js/unsaved-changes-detector.js"></script>
</head>

<body class="bg-black min-h-screen flex flex-col mobile-viewport">
    <header class="fixed top-0 left-0 right-0 z-50 bg-black text-white shadow-lg">
        <div class="flex items-center justify-between px-4 py-1 max-w-6xl mx-auto">
            <a href="/home">
                <img src="/assets/images/logo.png" alt="Logo Instituto Céu Interior" class="h-10">
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
                    <a href="/rituais" class="px-4 py-2 hover:bg-gray-800 <?= is_active('rituais') ?>">Rituais</a>
                    <?php if ($is_admin): ?>
                        <a href="/usuarios" class="px-4 py-2 hover:bg-gray-800 <?= is_active('usuarios') ?>">Usuários</a>
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
                <a href="/rituais" class="hover:text-[#00bfff] <?= is_active('rituais') ?>">Rituais</a>
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