<?php
// app/auth/templates/login.php - VERSÃO ATUALIZADA + CAPTCHA
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Se já estiver logado, redireciona para home
if (isset($_SESSION['user_id'])) {
  header("Location: /home");
  exit;
}
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
  <title>ICI Participantes</title>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, viewport-fit=cover">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="apple-mobile-web-app-title" content="Participantes ICI">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="application-name" content="Participantes ICI">
  <meta name="theme-color" content="#000000">
  <meta name="theme-color" content="#1D4ED8">
  <link rel="manifest" href="/manifest.json" />
  <link rel="apple-touch-icon" href="/assets/images/icon-192.png">
  <link rel="apple-touch-icon" sizes="152x152" href="/assets/images/icon-152.png">
  <link rel="apple-touch-icon" sizes="180x180" href="/assets/images/icon-192.png">
  <link rel="icon" href="/favicon.ico" type="image/x-icon" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
  <link rel="stylesheet" href="/assets/css/mobile-fixes.css?v=1.0">
  <link rel="stylesheet" href="/assets/css/tailwind.css?v=1.0">
  <script src="/assets/js/global-scripts.js?t=<?= time() ?>"></script>
  <script src="/assets/js/pwa.js"></script>
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

<body class="relative h-screen flex items-center justify-center bg-black/50 text-white">
  <div class="video-bg">
    <video autoplay muted loop>
      <source src="/assets/videos/fogueira.mp4" type="video/mp4">
      Seu navegador não suporta vídeos.
    </video>
  </div>

  <div class="w-full max-w-md bg-black/50 rounded-lg p-6 shadow-lg mx-4 backdrop-blur-sm">
    <div class="flex flex-col items-center mb-6">
      <img src="/assets/images/logo.png" alt="Logo" class="w-40 h-auto object-contain" />
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
      <form method="POST" action="/entrar" class="space-y-4" novalidate>
        <!-- Campo Usuário -->
        <div class="mb-4">
          <label for="usuario" class="block text-sm font-medium text-white mb-2">
            <i class="fa-solid fa-user mr-1"></i> Usuário
          </label>
          <input type="text" id="usuario" name="usuario" autocomplete="username" spellcheck="false" required
            class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#00bfff] focus:border-transparent text-black text-base">
        </div>

        <!-- Campo Senha -->
        <div class="mb-4">
          <label for="senha" class="block text-sm font-medium text-white mb-2">
            <i class="fa-solid fa-lock mr-1"></i> Senha
          </label>
          <div class="relative">
            <input type="password" id="senha" name="senha" autocomplete="current-password" spellcheck="false" required
              class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#00bfff] focus:border-transparent text-black text-base pr-12">
            <button type="button" id="toggleSenhaBtn" onclick="toggleSenha()" title="Mostrar senha"
              class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none">
              <i id="iconOlho" class="fa-solid fa-eye"></i>
            </button>
          </div>
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

        <div class="mb-6">
          <div class="bg-yellow-400/20 border border-yellow-400 rounded-lg p-4">
            <label for="lembrar_me" class="flex items-start cursor-pointer">
              <input type="checkbox" id="lembrar_me" name="lembrar_me" value="1"
                class="mt-1 h-5 w-5 text-[#00bfff] border-2 border-yellow-400 rounded focus:ring-[#00bfff] focus:ring-2">
              <div class="ml-3">
                <span class="text-white font-semibold text-base">
                  <i class="fa-solid fa-mobile-alt mr-1 text-yellow-400"></i>
                  Manter conectado
                </span>
              </div>
            </label>
          </div>
        </div>

        <button type="submit"
          class="w-full bg-[#00bfff] text-black py-3 px-4 rounded-lg hover:bg-yellow-400 transition-all duration-300 font-bold flex items-center justify-center focus:ring-4 focus:ring-[#00bfff]/50">
          <i class="fa-solid fa-sign-in-alt mr-2"></i>
          <span>Entrar</span>
        </button>

        <!-- ✅ SEÇÃO: LINK ESQUECI MINHA SENHA -->
        <div class="text-center space-y-3 mt-6">
          <hr class="border-yellow-400">
          <a href="/esqueci-senha"
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
        showLoginSuccessToast('/home');
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
  <script>
    // ===== ADICIONE NO FINAL DO login.php, dentro da tag <script> =====

    // ✅ CORREÇÃO: Previne submit automático no mobile
    document.addEventListener('DOMContentLoaded', function () {
      const form = document.querySelector('form');
      const senhaInput = document.getElementById('senha');
      const usuarioInput = document.getElementById('usuario');
      const submitBtn = form.querySelector('button[type="submit"]');

      let submitIntencional = false; // Flag para controlar submit intencional

      // ✅ Previne submit automático por autocomplete
      form.addEventListener('submit', function (e) {
        // Se não foi um clique intencional no botão, previne
        if (!submitIntencional) {
          e.preventDefault();
          console.log('🚫 Submit automático bloqueado');
          return false;
        }

        // Reset da flag
        submitIntencional = false;

        // Validações normais
        const usuario = usuarioInput.value.trim();
        const senha = senhaInput.value.trim();

        if (!usuario || !senha) {
          e.preventDefault();
          showToast('Usuário e senha são obrigatórios', 'error');
          return;
        }

        <?php if ($mostrarCaptcha): ?>
          const captchaResponse = grecaptcha.getResponse();
          if (!captchaResponse) {
            e.preventDefault();
            showToast('Por favor, complete a verificação de segurança', 'error');
            return;
          }
        <?php endif; ?>

        console.log('✅ Submit permitido');
      });

      // ✅ Marca como intencional quando clica no botão
      submitBtn.addEventListener('click', function (e) {
        console.log('👆 Clique intencional no botão de login');
        submitIntencional = true;
      });

      // ✅ Previne submit por Enter nos campos de input
      [usuarioInput, senhaInput].forEach(input => {
        if (input) {
          input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
              e.preventDefault();
              console.log('⏸️ Enter bloqueado no campo:', input.id);

              // Move foco para o próximo campo ou para o botão
              if (input === usuarioInput && senhaInput) {
                senhaInput.focus();
              } else if (input === senhaInput && submitBtn) {
                submitBtn.focus();
              }
            }
          });
        }
      });

      // ✅ Adiciona indicador visual quando campos estão preenchidos
      function verificarCamposPreenchidos() {
        const usuarioPreenchido = usuarioInput.value.trim().length > 0;
        const senhaPreenchida = senhaInput.value.trim().length > 0;

        if (usuarioPreenchido && senhaPreenchida) {
          submitBtn.classList.add('ring-2', 'ring-yellow-400');
          submitBtn.innerHTML = '<i class="fa-solid fa-sign-in-alt mr-2"></i>Pronto para Entrar';
        } else {
          submitBtn.classList.remove('ring-2', 'ring-yellow-400');
          submitBtn.innerHTML = '<i class="fa-solid fa-sign-in-alt mr-2"></i>Entrar';
        }
      }

      // ✅ Monitora mudanças nos campos
      [usuarioInput, senhaInput].forEach(input => {
        if (input) {
          input.addEventListener('input', verificarCamposPreenchidos);
          input.addEventListener('change', verificarCamposPreenchidos);
        }
      });
    });

    // ✅ ADICIONE TAMBÉM: Previne autocomplete agressivo
    document.querySelector('form').setAttribute('autocomplete', 'off');
    document.getElementById('usuario').setAttribute('autocomplete', 'username');
    document.getElementById('senha').setAttribute('autocomplete', 'current-password');
  </script>

</body>

</html>