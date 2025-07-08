<?php
// app/auth/templates/esqueci_senha.php - LAYOUT ORIGINAL + CAPTCHA
session_start();
require_once __DIR__ . '/../../services/CaptchaService.php';

$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);

$success = $_SESSION['success'] ?? null;
unset($_SESSION['success']);

// Verifica se deve mostrar captcha
$identificador = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
CaptchaService::verificarTempoReset($identificador);
$mostrarCaptcha = CaptchaService::deveMostrarCaptcha($identificador);
$tentativas = CaptchaService::obterTentativas($identificador);
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
    <link rel="icon" href="/assets/images/favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="/assets/js/global-scripts.js?t=<?= time() ?>"></script>
    <link rel="stylesheet" href="/assets/css/tailwind.css?v=1.0">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <?php if ($mostrarCaptcha): ?>
    <?= CaptchaService::gerarScriptCaptcha() ?>
    <?php endif; ?>

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

        /* Estilização do captcha para se adequar ao design */
        .g-recaptcha {
            transform: scale(0.9);
            transform-origin: 0 0;
            margin: 10px 0;
        }

        @media (max-width: 640px) {
            .g-recaptcha {
                transform: scale(0.77);
            }
        }

        /* Alerta de segurança estilizado */
        .security-alert {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 16px;
        }
    </style>
</head>

<body class="relative h-screen flex items-center justify-center bg-black/50 text-white overflow-hidden">
    <div class="video-bg">
        <video autoplay muted loop>
            <source src="/assets/videos/fogueira.mp4" type="video/mp4">
            Seu navegador não suporta vídeos.
        </video>
    </div>

    <div class="w-full max-w-md bg-black/50 rounded-lg p-6 shadow-lg mx-4 backdrop-blur-sm">
        <div class="flex flex-col items-center mb-6">
            <img src="/assets/images/logo.png" alt="Logo"
                class="w-40 h-auto object-contain" />
            <h2 class="mt-4 text-xl font-semibold text-center text-white">Recuperar Senha</h2>
        </div>

        <!-- Alerta de Segurança (só aparece quando necessário) -->
        <?php if ($mostrarCaptcha && $tentativas >= 5): ?>
        <div class="security-alert">
            <div class="flex items-center text-red-300">
                <i class="fa-solid fa-shield-exclamation mr-2 text-red-400"></i>
                <div class="text-sm">
                    <strong>Verificação de segurança necessária</strong><br>
                    Muitas tentativas de recuperação. Complete a verificação abaixo.
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="form-container mobile-compact">
            <form method="POST" action="/enviar-recuperacao" class="space-y-4" novalidate id="recuperacaoForm">
                <div>
                    <input type="text" name="usuario" id="usuario" placeholder="Usuário" required autocapitalize="none"
                        class="bg-white w-full p-3 rounded border border-gray-300 text-black placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#00bfff] transition" />
                    <p class="text-sm text-red-500 mt-1 hidden" id="erro-usuario">Campo obrigatório.</p>
                </div>

                <!-- Captcha (só aparece após 5 tentativas) -->
                <?php if ($mostrarCaptcha): ?>
                <div class="captcha-container">
                    <div class="bg-white/10 p-3 rounded border border-white/20">
                        <label class="block text-sm text-yellow-400 mb-2">
                            <i class="fa-solid fa-shield-check mr-1"></i>Verificação de Segurança
                        </label>
                        <?= CaptchaService::gerarHtmlCaptcha() ?>
                    </div>
                </div>
                <?php endif; ?>

                <button type="submit"
                    class="w-full bg-[#00bfff] font-bold text-black py-3 rounded hover:bg-yellow-400 transition">
                    <i class="fa-solid fa-envelope mr-2"></i>
                    Enviar Link de Recuperação
                </button>

                <div class="text-center space-y-3 mt-6">
                    <hr class="border-yellow-400">
                    <a href="/login"
                        class="inline-flex items-center text-yellow-400 hover:text-[#00bfff] text-sm transition font-semibold">
                        <i class="fa-solid fa-arrow-left mr-1"></i>
                        Voltar ao Login
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Mostrar mensagens de erro/sucesso (mantendo comportamento original)
        <?php if ($error): ?>
        showToast('<?= addslashes($error) ?>', 'error');
        <?php endif; ?>

        <?php if ($success): ?>
        showToast('<?= addslashes($success) ?>', 'success');
        <?php endif; ?>

        // Validação do formulário
        document.getElementById('recuperacaoForm').addEventListener('submit', function(e) {
            const usuario = document.getElementById('usuario').value.trim();

            if (!usuario) {
                e.preventDefault();
                showToast('Por favor, digite seu nome de usuário', 'error');
                return;
            }

            <?php if ($mostrarCaptcha): ?>
            // Verifica se o captcha foi preenchido
            const captchaResponse = grecaptcha.getResponse();
            if (!captchaResponse) {
                e.preventDefault();
                showToast('Por favor, complete a verificação de segurança', 'error');
                return;
            }
            <?php endif; ?>

            // Mostra loading (mantendo comportamento original)
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Enviando...';
            submitBtn.disabled = true;

            // Reabilita o botão após 5 segundos (caso de erro)
            setTimeout(function() {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 5000);
        });

        // Foco automático no campo usuário
        document.getElementById('usuario').focus();
    </script>
</body>

</html>