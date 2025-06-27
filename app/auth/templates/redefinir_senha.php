<?php
// app/auth/templates/redefinir_senha.php
session_start();
require_once __DIR__ . '/../../config/database.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    $_SESSION['error'] = 'Token de recuperação inválido.';
    header('Location: /participantesici/public_html/login');
    exit;
}

// Verifica se o token é válido e não expirou usando a tabela password_recovery_tokens
$stmt = $pdo->prepare("
    SELECT u.id, u.nome, u.usuario
    FROM password_recovery_tokens prt
    JOIN usuarios u ON prt.user_id = u.id
    WHERE prt.token = ? AND prt.expires_at > NOW()
");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = 'Token de recuperação inválido ou expirado. Envie novamente.';
    header('Location: /participantesici/public_html/esqueci-senha');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#000000">
    <title>Redefinir Senha - ICI</title>
    <link rel="icon" href="/participantesici/public_html/assets/images/favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="/participantesici/public_html/assets/js/global-scripts.js?t=<?= time() ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        .video-bg video {
            position: fixed;
            top: 0;
            left: 0;
            min-width: 100%;
            min-height: 100%;
            object-fit: cover;
            z-index: -1;
        }

        ::placeholder {
            color: #d1d5db;
            opacity: 1;
        }
    </style>
</head>

<body class="relative h-screen flex items-center justify-center bg-black/70 text-white overflow-hidden">
    <div class="video-bg">
        <video autoplay muted loop>
            <source src="/participantesici/public_html/assets/videos/fogueira.mp4" type="video/mp4">
            Seu navegador não suporta vídeos.
        </video>
    </div>

    <div class="w-full max-w-md bg-black/50 rounded-lg p-6 shadow-lg mx-4">
        <div class="flex flex-col items-center mb-6">
            <img src="/participantesici/public_html/assets/images/logo.png" alt="Logo"
                class="w-40 h-auto object-contain" />
            <h2 class="mt-4 text-xl font-semibold text-center text-white">Redefinir Senha</h2>
            <p class="mt-2 text-sm text-gray-300 text-center">Olá, <?= htmlspecialchars($user['nome']) ?></p>
        </div>

        <div class="form-container mobile-compact">
            <form method="POST" action="/participantesici/public_html/salvar-nova-senha" class="space-y-4" novalidate>
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <div class="relative group">
                    <input type="password" name="nova_senha" id="nova_senha" placeholder="Nova Senha" required
                        class="w-full p-3 rounded border border-gray-300 text-black placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#00bfff] transition" />
                    <p class="text-sm text-red-500 mt-1 hidden" id="erro-nova-senha">Campo obrigatório.</p>
                </div>

                <div class="relative group">
                    <input type="password" name="confirmar_senha" id="confirmar_senha"
                        placeholder="Confirmar Nova Senha" required
                        class="w-full p-3 rounded border border-gray-300 text-black placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#00bfff] transition" />
                    <p class="text-sm text-red-500 mt-1 hidden" id="erro-confirmar-senha">Campo obrigatório.</p>
                </div>

                <button type="button" onclick="toggleTodasSenhas(this, this.querySelector('i'))"
                    class="text-yellow-400 text-sm hover:text-[#00bfff] flex items-center gap-2 transition">
                    <i class="fa-solid fa-eye"></i> Mostrar Senhas
                </button>

                <button type="submit"
                    class="w-full bg-[#00bfff] font-bold text-black py-3 rounded hover:bg-yellow-400 transition">
                    <i class="fa-solid fa-save mr-2"></i>
                    Salvar Nova Senha
                </button>

                <div class="text-center space-y-3 mt-6">
                    <hr class="border-yellow-400">
                    <a href="/participantesici/public_html/login"
                        class="inline-flex items-center text-yellow-400 hover:text-[#00bfff] text-sm transition font-semibold">
                        <i class="fa-solid fa-arrow-left mr-1"></i>
                        Voltar ao Login
                    </a>
                </div>
            </form>
        </div>
    </div>

    <?php
    // Exibe mensagens de sessão
    if (isset($_SESSION['success'])) {
        echo "<script>document.addEventListener('DOMContentLoaded', () => { showToast('" . addslashes($_SESSION['success']) . "', 'success'); });</script>";
        unset($_SESSION['success']);
    }
    if (isset($_SESSION['error'])) {
        echo "<script>document.addEventListener('DOMContentLoaded', () => { showToast('" . addslashes($_SESSION['error']) . "', 'error'); });</script>";
        unset($_SESSION['error']);
    }
    ?>

    <script>
        // Adiciona classe senha-input para funcionar com toggleTodasSenhas
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('nova_senha').classList.add('senha-input');
            document.getElementById('confirmar_senha').classList.add('senha-input');
        });
    </script>
</body>

</html>
