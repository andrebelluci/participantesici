<?php
// app/auth/templates/login.php - VERSÃO ATUALIZADA
session_start();
$error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);

// ✅ ADICIONAR MENSAGEM DE SUCESSO DA RECUPERAÇÃO
$loginSuccess = $_SESSION['login_success'] ?? null;
unset($_SESSION['login_success']);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, viewport-fit=cover">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="theme-color" content="#000000">
  <meta name="theme-color" content="#1D4ED8">
  <title>Participantes - ICI</title>
  <link rel="icon" href="/participantesici/public_html/assets/images/favicon.ico" type="image/x-icon" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="manifest" href="/participantesici/public_html/manifest.json" />
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

    .eye {
      text-color: #00bfff;
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

  <div class="w-full max-w-md bg-white/5 rounded-lg p-6 shadow-lg mx-4">
    <div class="flex flex-col items-center mb-6">
      <img src="/participantesici/public_html/assets/images/logo.png" alt="Logo" class="w-40 h-auto object-contain" />
      <h2 class="mt-4 text-xl font-semibold text-center text-white">Gestão de participantes</h2>
    </div>

    <div class="form-container mobile-compact">
      <form method="POST" action="/participantesici/public_html/entrar" class="space-y-4" novalidate>
        <div>
          <input type="text" name="usuario" id="usuario" placeholder="Usuário" required autocapitalize="none"
            class="w-full p-3 rounded border border-gray-300 text-black placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary transition" />
          <p class="text-sm text-red-500 mt-1 hidden" id="erro-usuario">Campo obrigatório.</p>
        </div>

        <div class="flex flex-col">
          <div class="relative">
            <input type="password" name="senha" id="senha" placeholder="Senha" required autocapitalize="none"
              class="w-full p-3 pr-12 rounded border border-gray-300 text-black placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary transition" />
            <button type="button" onclick="toggleSenha()"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-black hover:text-blue-300" title="Mostrar senha"
              id="toggleSenhaBtn">
              <i class="fa-solid fa-eye" id="iconOlho"></i>
            </button>
          </div>
          <p class="text-sm text-red-500 mt-1 hidden" id="erro-senha">Campo obrigatório.</p>
        </div>

        <button type="submit" class="w-full bg-primary font-bold text-white py-3 rounded hover:bg-blue-900 transition">
          <i class="fa-solid fa-sign-in-alt mr-2"></i>Entrar
        </button>

        <!-- ✅ NOVA SEÇÃO: LINK ESQUECI MINHA SENHA -->
        <div class="text-center space-y-3 mt-6">
          <hr class="border-gray-400">
          <a href="/participantesici/public_html/esqueci-senha"
            class="inline-flex items-center text-sm text-gray-300 hover:text-primary transition group">
            <i class="fa-solid fa-key mr-2 group-hover:scale-110 transition-transform"></i>
            Esqueci minha senha
          </a>
          <p class="text-xs text-gray-400">
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
  <?php endif; ?>

  <!-- ✅ MENSAGEM DE SUCESSO DA RECUPERAÇÃO -->
  <?php if ($loginSuccess): ?>
    <script>
      document.addEventListener("DOMContentLoaded", () => {
        showToast(<?= json_encode($loginSuccess) ?>, 'success');
      });
    </script>
  <?php endif; ?>

  <!-- ✅ ADICIONAR TOAST DE SUCESSO NO LOGIN -->
  <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
    <script>
      document.addEventListener("DOMContentLoaded", () => {
        // Exibe toast de sucesso e redireciona
        showLoginSuccessToast('/participantesici/public_html/home');
      });
    </script>
  <?php endif; ?>

  <!-- Service Worker: forçar atualização imediata -->
  <script>
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.getRegistrations().then(registrations => {
        for (let reg of registrations) {
          reg.unregister(); // Remove antigos
        }
        // Registra novamente
        navigator.serviceWorker.register('/participantesici/public_html/service-worker.js?ts=' + Date.now())
          .then(() => console.log("Service Worker atualizado!"))
          .catch(err => console.error("Erro no Service Worker:", err));
      });
    }
  </script>

</body>

</html>