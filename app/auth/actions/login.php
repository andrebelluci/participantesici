<?php
// app/auth/actions/login.php - VERSÃO ATUALIZADA COM CAPTCHA E 30 DIAS

// ✅ Inicia sessão se não estiver ativa (configurações serão aplicadas no header.php)
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../services/CaptchaService.php';

// Função para gerar token seguro
function gerarTokenSeguro($length = 64)
{
  return bin2hex(random_bytes($length / 2));
}

// ✅ Função corrigida para definir cookie de lembrar-me (persistente até limpar cache)
function definirCookieLembrarMe($user_id, $pdo)
{
  $token = gerarTokenSeguro();
  // Expiração muito longa (10 anos) - efetivamente até limpar cache do navegador
  $expira_em = date('Y-m-d H:i:s', time() + (10 * 365 * 24 * 60 * 60)); // 10 anos

  try {
    // Remove tokens antigos do usuário
    $stmt_cleanup = $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
    $stmt_cleanup->execute([$user_id]);

    // Insere novo token
    $stmt = $pdo->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $token, $expira_em]);

    // ✅ Define cookie seguro com expiração muito longa (10 anos)
    $cookie_expiry = time() + (10 * 365 * 24 * 60 * 60); // 10 anos

    // Para ambiente local/desenvolvimento
    if (
      $_SERVER['HTTP_HOST'] === 'localhost' ||
      strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false ||
      strpos($_SERVER['HTTP_HOST'], '.local') !== false
    ) {

      $cookie_set = setcookie(
        'remember_token',
        $token,
        $cookie_expiry,
        '/',
        '', // domínio vazio para localhost
        false, // secure false para HTTP local
        true  // httponly
      );
    } else {
      // Para produção
      $cookie_set = setcookie(
        'remember_token',
        $token,
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

    if (!$cookie_set) {
      error_log("[REMEMBER_ME] ERRO: Falha ao definir cookie");
      return false;
    }

    error_log("[REMEMBER_ME] Token criado para usuário: $user_id - Cookie persistente até limpar cache do navegador");
    return true;

  } catch (Exception $e) {
    error_log("[REMEMBER_ME] Erro ao criar token: " . $e->getMessage());
    return false;
  }
}

// ✅ Função para limpar tokens expirados
function limparTokensExpirados($pdo)
{
  try {
    $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE expires_at < NOW()");
    $stmt->execute();
    $count = $stmt->rowCount();

    if ($count > 0) {
      error_log("[REMEMBER_ME] Limpeza: $count tokens expirados removidos");
    }

    return true;
  } catch (Exception $e) {
    error_log("[REMEMBER_ME] Erro na limpeza de tokens: " . $e->getMessage());
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

      // ✅ Define sessão com configurações estendidas
      $_SESSION['user_id'] = $user['id'];
      $_SESSION['nome'] = $user['nome'];
      $_SESSION['last_activity'] = time();
      $_SESSION['login_method'] = $lembrar_me ? 'remember_me' : 'normal'; // Para distinguir

      // ✅ Se marcou "manter conectado", cria token persistente
      if ($lembrar_me) {
        $cookie_success = definirCookieLembrarMe($user['id'], $pdo);
        if ($cookie_success) {
          error_log("[LOGIN] Usuário {$user['usuario']} logou com 'manter conectado' ativado - sessão persistente até limpar cache");
        } else {
          error_log("[LOGIN] Falha ao criar cookie para usuário {$user['usuario']}");
        }

        // ✅ Limpeza preventiva de tokens expirados (apenas os realmente antigos)
        limparTokensExpirados($pdo);
      } else {
        error_log("[LOGIN] Usuário {$user['usuario']} logou sem 'manter conectado' - sessão padrão");
      }

      // Log do login
      error_log("[LOGIN] Login bem-sucedido: {$user['usuario']} (ID: {$user['id']}) - Método: " . ($lembrar_me ? 'MANTER_CONECTADO' : 'NORMAL'));

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
