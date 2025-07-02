<?php
// app/auth/templates/login.php - VERSÃO ATUALIZADA + CAPTCHA
session_start();
require_once __DIR__ . '/../../services/CaptchaService.php';

$error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);

// ✅ ADICIONAR MENSAGEM DE SUCESSO DA RECUPERAÇÃO
$loginSuccess = $_SESSION['login_success'] ?? null;
unset($_SESSION['login_success']);

// Verifica se deve mostrar captcha
$identificador = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
CaptchaService::verificarTempoReset($identificador);
$mostrarCaptcha = CaptchaService::deveMostrarCaptcha($identificador);
$tentativas = CaptchaService::obterTentativas($identificador);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
  <title>Participantes - ICI</title>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, viewport-fit=cover">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="apple-mobile-web-app-title" content="Participantes ICI">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="application-name" content="Participantes ICI">
  <meta name="theme-color" content="#000000">
  <meta name="theme-color" content="#1D4ED8">
  <link rel="manifest" href="/participantesici/public_html/manifest.json" />
  <link rel="apple-touch-icon" href="/participantesici/public_html/assets/images/icon-192.png">
  <link rel="apple-touch-icon" sizes="152x152" href="/participantesici/public_html/assets/images/icon-152.png">
  <link rel="apple-touch-icon" sizes="180x180" href="/participantesici/public_html/assets/images/icon-192.png">
  <link rel="icon" href="/participantesici/public_html/assets/images/favicon.ico" type="image/x-icon" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
  <link rel="stylesheet" href="/participantesici/public_html/assets/css/mobile-fixes.css?v=1.0">
  <script src="/participantesici/public_html/assets/js/global-scripts.js?t=<?= time() ?>"></script>
  <script src="/participantesici/public_html/assets/js/pwa.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
  <script src="https://cdn.tailwindcss.com"></script>

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

<body class="relative h-screen flex items-center justify-center bg-black/70 text-white">
  <div class="video-bg">
    <video autoplay muted loop>
      <source src="/participantesici/public_html/assets/videos/fogueira.mp4" type="video/mp4">
      Seu navegador não suporta vídeos.
    </video>
  </div>

  <div class="w-full max-w-md bg-black/50 rounded-lg p-6 shadow-lg mx-4 backdrop-blur-sm">
    <div class="flex flex-col items-center mb-6">
      <img src="/participantesici/public_html/assets/images/logo.png" alt="Logo" class="w-40 h-auto object-contain" />
      <h2 class="mt-4 text-xl font-semibold text-center text-white">Gestão de participantes</h2>
    </div>

    <!-- Alerta de Segurança (só aparece quando necessário) -->
    <?php if ($mostrarCaptcha && $tentativas >= 5): ?>
      <div class="security-alert">
        <div class="flex items-center text-red-300">
          <i class="fa-solid fa-shield-exclamation mr-2 text-red-400"></i>
          <div class="text-sm">
            <strong>Verificação de segurança necessária</strong><br>
            Muitas tentativas de login. Complete a verificação abaixo.
          </div>
        </div>
      </div>
    <?php endif; ?>

    <div class="form-container mobile-compact">
      <form method="POST" action="/participantesici/public_html/entrar" class="space-y-4" novalidate>
        <div>
          <input type="text" name="usuario" id="usuario" placeholder="Usuário" required autocapitalize="none"
            class="w-full p-3 rounded border border-gray-300 text-black placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#00bfff] transition" />
          <p class="text-sm text-red-500 mt-1 hidden" id="erro-usuario">Campo obrigatório.</p>
        </div>

        <div class="flex flex-col">
          <div class="relative">
            <input type="password" name="senha" id="senha" placeholder="Senha" required autocapitalize="none"
              class="w-full p-3 pr-12 rounded border border-gray-300 text-black placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#00bfff] transition" />
            <button type="button" onclick="toggleSenha()"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-black hover:text-blue-300" title="Mostrar senha"
              id="toggleSenhaBtn">
              <i class="fa-solid fa-eye" id="iconOlho"></i>
            </button>
          </div>
          <p class="text-sm text-red-500 mt-1 hidden" id="erro-senha">Campo obrigatório.</p>
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

        <!-- ✅ NOVA SEÇÃO: CHECKBOX LEMBRAR-ME -->
        <div class="flex items-center justify-between">
          <label class="flex items-center cursor-pointer group">
            <input type="checkbox" name="lembrar_me" id="lembrar_me" value="1"
              class="w-4 h-4 text-[#00bfff] bg-gray-100 border-gray-300 rounded focus:ring-[#00bfff] focus:ring-2">
            <span class="ml-2 text-sm text-yellow-400 group-hover:text-[#00bfff] transition">
              <i class="fa-solid fa-clock mr-1"></i>Lembrar-me por 30 dias
            </span>
          </label>
        </div>

        <button type="submit"
          class="w-full bg-[#00bfff] font-bold text-black py-3 rounded hover:bg-yellow-400 transition">
          <i class="fa-solid fa-sign-in-alt mr-2"></i>Entrar
        </button>

        <!-- ✅ SEÇÃO: LINK ESQUECI MINHA SENHA -->
        <div class="text-center space-y-3 mt-6">
          <hr class="border-yellow-400">
          <a href="/participantesici/public_html/esqueci-senha"
            class="inline-flex items-center text-sm text-yellow-400 hover:text-[#00bfff] transition group font-semibold">
            <i class="fa-solid fa-key mr-2 group-hover:scale-110 transition-transform"></i>
            Esqueci minha senha
          </a>
          <p class="text-xs text-yellow-400">
            Você receberá um link de recuperação por email
          </p>
        </div>
      </form>
    </div>
  </div>

  <!-- Mensagens de Erro -->
  <?php if ($error): ?>
    <script>
      document.addEventListener("DOMContentLoaded", () => {
        showToast(<?= json_encode($error) ?>, 'error');
      });
    </script>

    <script>
      // Função toggle senha (do código original)
      function toggleSenha() {
        const senhaInput = document.getElementById('senha');
        const iconOlho = document.getElementById('iconOlho');

        if (senhaInput.type === 'password') {
          senhaInput.type = 'text';
          iconOlho.classList.remove('fa-eye');
          iconOlho.classList.add('fa-eye-slash');
        } else {
          senhaInput.type = 'password';
          iconOlho.classList.remove('fa-eye-slash');
          iconOlho.classList.add('fa-eye');
        }
      }

      // Validação do formulário com captcha
      document.querySelector('form').addEventListener('submit', function (e) {
        const usuario = document.getElementById('usuario').value.trim();
        const senha = document.getElementById('senha').value.trim();

        if (!usuario || !senha) {
          e.preventDefault();
          showToast('Usuário e senha são obrigatórios', 'error');
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
      });
    </script>
  <?php endif; ?>

  <!-- ✅ MENSAGEM DE SUCESSO DA RECUPERAÇÃO -->
  <?php if ($loginSuccess): ?>
    <script>
      document.addEventListener("DOMContentLoaded", () => {
        showToast(<?= json_encode($loginSuccess) ?>, 'success');
      });
    </script>
  <?php endif; ?>

  <!-- ✅ TOAST DE SUCESSO NO LOGIN -->
  <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
    <script>
      document.addEventListener("DOMContentLoaded", () => {
        // Exibe toast de sucesso e redireciona
        showLoginSuccessToast('/participantesici/public_html/home');
      });
    </script>
  <?php endif; ?>

  <!-- ✅ TOAST PARA TIMEOUT DE SESSÃO -->
  <?php if (isset($_GET['timeout']) && $_GET['timeout'] == '1'): ?>
    <script>
      document.addEventListener("DOMContentLoaded", () => {
        showToast('Sua sessão expirou. Faça login novamente.', 'warning');
      });
    </script>
  <?php endif; ?>

</body>

</html>