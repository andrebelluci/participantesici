<?php
// app/auth/templates/esqueci_senha.php
session_start();
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
    <title>Recuperar Senha - ICI</title>
    <link rel="icon" href="/participantesici/public_html/assets/images/favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="/participantesici/public_html/assets/js/global-scripts.js?t=<?= time() ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#00bfff',
                    }
                }
            }
        };
    </script>

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
            <h2 class="mt-4 text-xl font-semibold text-center text-white">Recuperar Senha</h2>
        </div>

        <div class="form-container mobile-compact">
            <form method="POST" action="/participantesici/public_html/enviar-recuperacao" class="space-y-4" novalidate>
                <div>
                    <input type="text" name="usuario" id="usuario" placeholder="Usuário" required autocapitalize="none"
                        class="w-full p-3 rounded border border-gray-300 text-black placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary transition" />
                    <p class="text-sm text-red-500 mt-1 hidden" id="erro-usuario">Campo obrigatório.</p>
                </div>

                <button type="submit"
                    class="w-full bg-[#00bfff] font-bold text-black py-3 rounded hover:bg-yellow-400 transition">
                    <i class="fa-solid fa-envelope mr-2"></i>
                    Enviar Link de Recuperação
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
</body>

</html>
