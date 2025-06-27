<?php
// app/auth/actions/salvar_nova_senha.php
session_start();
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /participantesici/public_html/login');
  exit;
}

$token = $_POST['token'] ?? '';
$nova_senha = $_POST['nova_senha'] ?? '';
$confirmar_senha = $_POST['confirmar_senha'] ?? '';

// Validações
if (empty($token)) {
  $_SESSION['error'] = 'Token inválido.';
  header('Location: /participantesici/public_html/esqueci-senha');
  exit;
}

if (empty($nova_senha) || empty($confirmar_senha)) {
  $_SESSION['error'] = 'Todos os campos são obrigatórios.';
  header("Location: /participantesici/public_html/redefinir-senha?token=" . urlencode($token));
  exit;
}

if ($nova_senha !== $confirmar_senha) {
  $_SESSION['error'] = 'As senhas não coincidem.';
  header("Location: /participantesici/public_html/redefinir-senha?token=" . urlencode($token));
  exit;
}

if (strlen($nova_senha) < 6) {
  $_SESSION['error'] = 'A senha deve ter pelo menos 6 caracteres.';
  header("Location: /participantesici/public_html/redefinir-senha?token=" . urlencode($token));
  exit;
}

try {
  // Verifica se o token é válido e não expirou usando a tabela password_recovery_tokens
  $stmt = $pdo->prepare("
        SELECT u.id, u.nome, u.usuario, prt.id as token_id
        FROM password_recovery_tokens prt
        JOIN usuarios u ON prt.user_id = u.id
        WHERE prt.token = ? AND prt.expires_at > NOW()
    ");
  $stmt->execute([$token]);
  $result = $stmt->fetch();

  if (!$result) {
    $_SESSION['error'] = 'Token de recuperação inválido ou expirado. Envie novamente.';
    header('Location: /participantesici/public_html/esqueci-senha');
    exit;
  }

  // Inicia transação
  $pdo->beginTransaction();

  // Atualiza a senha
  $senha_hash = hash('sha256', $nova_senha);
  $stmt_update = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
  $stmt_update->execute([$senha_hash, $result['id']]);

  // Remove o token usado (e limpa tokens expirados)
  $stmt_cleanup = $pdo->prepare("DELETE FROM password_recovery_tokens WHERE id = ? OR expires_at < NOW()");
  $stmt_cleanup->execute([$result['token_id']]);

  // Confirma transação
  $pdo->commit();

  error_log("[RECUPERACAO_DEBUG] Senha atualizada com sucesso para usuário: " . $result['usuario']);

  // ✅ PÁGINA DE SUCESSO COM TOAST E REDIRECIONAMENTO
  ?>
  <!DOCTYPE html>
  <html lang="pt-br">

  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Senha Alterada - ICI</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="/participantesici/public_html/assets/js/global-scripts.js?t=<?= time() ?>"></script>
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
    </style>
  </head>

  <body class="relative h-screen flex items-center justify-center bg-black/70 text-white overflow-hidden">
    <div class="video-bg">
      <video autoplay muted loop>
        <source src="/participantesici/public_html/assets/videos/fogueira.mp4" type="video/mp4">
      </video>
    </div>

    <div class="w-full max-w-md bg-black/50 rounded-lg p-6 shadow-lg mx-4 text-center">
      <div class="flex flex-col items-center mb-6">
        <img src="/participantesici/public_html/assets/images/logo.png" alt="Logo" class="w-40 h-auto object-contain" />
        <h2 class="mt-4 text-xl font-semibold text-white">Senha Alterada!</h2>
      </div>

      <div class="space-y-4">
        <div class="text-green-400 text-6xl mb-4">
          <i class="fa-solid fa-check-circle"></i>
        </div>

        <h3 class="text-lg font-semibold text-white">
          ✅ Senha alterada com sucesso!
        </h3>

        <p class="text-gray-300 text-sm">
          Redirecionando para a página de login...
        </p>

        <div class="w-full bg-gray-600 rounded-full h-2">
          <div id="progress-bar" class="bg-[#00bfff] h-2 rounded-full transition-all duration-100" style="width: 0%"></div>
        </div>

        <p class="text-xs text-gray-400">
          <span id="countdown">3</span> segundos
        </p>

        <button onclick="irParaLogin()"
          class="mt-4 bg-[#00bfff] text-black px-6 py-2 rounded hover:bg-yellow-400 transition font-semibold">
          <i class="fa-solid fa-arrow-right mr-2"></i>
          Ir para Login
        </button>
      </div>
    </div>

    <script>
      let countdown = 3;
      let progress = 0;
      let intervalId;

      function irParaLogin() {
        if (intervalId) clearInterval(intervalId);
        window.location.href = '/participantesici/public_html/login';
      }

      // Mostra toast imediatamente
      document.addEventListener('DOMContentLoaded', function () {
        showToast('🎉 Senha alterada com sucesso! Redirecionando...', 'success', 4000);

        // Atualiza countdown e barra de progresso
        intervalId = setInterval(function () {
          countdown--;
          progress += 33.33;

          document.getElementById('countdown').textContent = countdown;
          document.getElementById('progress-bar').style.width = progress + '%';

          if (countdown <= 0) {
            clearInterval(intervalId);
            irParaLogin();
          }
        }, 1000);
      });
    </script>
  </body>

  </html>
  <?php
  exit;

} catch (Exception $e) {
  // Desfaz transação em caso de erro
  $pdo->rollBack();

  error_log("[RECUPERACAO_DEBUG] Erro ao salvar nova senha: " . $e->getMessage());
  $_SESSION['error'] = 'Erro interno. Tente novamente mais tarde.';
  header("Location: /participantesici/public_html/redefinir-senha?token=" . urlencode($token));
  exit;
}
