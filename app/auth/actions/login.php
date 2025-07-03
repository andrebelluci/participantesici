<?php
// app/auth/actions/login.php - VERSÃO ATUALIZADA COM CAPTCHA
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../services/CaptchaService.php';

// Função para gerar token seguro
function gerarTokenSeguro($length = 64)
{
  return bin2hex(random_bytes($length / 2));
}

// Função para definir cookie de lembrar-me
function definirCookieLembrarMe($user_id, $pdo)
{
  $token = gerarTokenSeguro();
  $expira_em = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60)); // 30 dias

  try {
    // Remove tokens antigos do usuário
    $stmt_cleanup = $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
    $stmt_cleanup->execute([$user_id]);

    // Insere novo token
    $stmt = $pdo->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $token, $expira_em]);

    // Define cookie seguro
    setcookie(
      'remember_token',
      $token,
      [
        'expires' => time() + (30 * 24 * 60 * 60), // 30 dias
        'path' => '/',
        'domain' => '', // Deixe vazio para usar o domínio atual
        'secure' => isset($_SERVER['HTTPS']), // Apenas HTTPS se disponível
        'httponly' => true, // Previne acesso via JavaScript
        'samesite' => 'Lax' // Proteção CSRF
      ]
    );

    error_log("[REMEMBER_ME] Token criado para usuário: $user_id, expira em: $expira_em");
    return true;

  } catch (Exception $e) {
    error_log("[REMEMBER_ME] Erro ao criar token: " . $e->getMessage());
    return false;
  }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $usuario = $_POST['usuario'] ?? '';
  $senha = $_POST['senha'] ?? '';
  $lembrar_me = isset($_POST['lembrar_me']) && $_POST['lembrar_me'] == '1';
  $captcha_token = $_POST['g-recaptcha-response'] ?? '';

  // Identifica o usuário pelo IP
  $identificador = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

  // Verifica se o tempo de reset expirou
  CaptchaService::verificarTempoReset($identificador);

  // Validação básica
  if (empty($usuario) || empty($senha)) {
    CaptchaService::incrementarTentativas($identificador);
    $_SESSION['login_error'] = 'Usuário e senha são obrigatórios!';
    header("Location: /login?t=" . time());
    exit;
  }

  // Verifica se deve mostrar captcha e se foi preenchido
  $deveMostrarCaptcha = CaptchaService::deveMostrarCaptcha($identificador);

  if ($deveMostrarCaptcha) {
    if (empty($captcha_token)) {
      $_SESSION['login_error'] = 'Por favor, complete a verificação de segurança (captcha).';
      header("Location: /login?t=" . time());
      exit;
    }

    // Verifica o captcha
    $resultadoCaptcha = CaptchaService::verificarCaptcha($captcha_token, $identificador);

    if (!$resultadoCaptcha['success']) {
      CaptchaService::incrementarTentativas($identificador);
      $_SESSION['login_error'] = 'Verificação de segurança inválida. Tente novamente.';
      header("Location: /login?t=" . time());
      exit;
    }

    error_log("[LOGIN] Captcha verificado com sucesso para $identificador");
  }

  $senha_hash = hash('sha256', $senha);

  try {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ? AND senha = ?");
    $stmt->execute([$usuario, $senha_hash]);
    $user = $stmt->fetch();

    if ($user) {
      // Login bem-sucedido - reseta tentativas de captcha
      CaptchaService::resetarTentativas($identificador);

      $_SESSION['user_id'] = $user['id'];
      $_SESSION['nome'] = $user['nome'];
      $_SESSION['last_activity'] = time();

      // Se marcou "lembrar-me", cria token persistente
      if ($lembrar_me) {
        $cookie_success = definirCookieLembrarMe($user['id'], $pdo);
        if ($cookie_success) {
          error_log("[LOGIN] Usuário {$user['usuario']} logou com lembrar-me ativado");
        } else {
          error_log("[LOGIN] Falha ao criar cookie para usuário {$user['usuario']}");
        }
      }

      // Log do login
      error_log("[LOGIN] Login bem-sucedido: {$user['usuario']} (ID: {$user['id']})");

      // $_SESSION['login_success'] = 'Login efetuado com sucesso!';
      header("Location: /login?success=1");
      exit;

    } else {
      // Login falhou - incrementa tentativas
      CaptchaService::incrementarTentativas($identificador);

      error_log("[LOGIN] Tentativa de login inválida para usuário: $usuario (Tentativas: " . CaptchaService::obterTentativas($identificador) . ")");
      $_SESSION['login_error'] = 'Usuário ou senha inválidos!';
      header("Location: /login?t=" . time());
      exit;
    }

  } catch (Exception $e) {
    CaptchaService::incrementarTentativas($identificador);
    error_log("[LOGIN] Erro no banco de dados: " . $e->getMessage());
    $_SESSION['login_error'] = 'Erro interno. Tente novamente mais tarde.';
    header("Location: /login?t=" . time());
    exit;
  }
}

header("Location: /login");
exit;
