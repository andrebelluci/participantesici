<?php
// app/auth/actions/login.php - VERSÃO ATUALIZADA COM CAPTCHA E 30 DIAS

// ✅ Configura sessão para 30 dias APENAS se não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
  // Configura parâmetros ANTES de iniciar a sessão
  ini_set('session.gc_maxlifetime', 30 * 24 * 60 * 60); // 30 dias
  ini_set('session.cookie_lifetime', 30 * 24 * 60 * 60); // 30 dias

  session_set_cookie_params([
    'lifetime' => 30 * 24 * 60 * 60, // 30 dias
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Lax'
  ]);
  session_start();
} else {
  // ✅ Se sessão já está ativa, só faz log
  error_log("[LOGIN] Sessão já ativa - usando configurações existentes");
}
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../services/CaptchaService.php';

// Função para gerar token seguro
function gerarTokenSeguro($length = 64)
{
  return bin2hex(random_bytes($length / 2));
}

// ✅ Função corrigida para definir cookie de lembrar-me
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

    // ✅ Define cookie seguro com configuração estendida
    $cookie_expiry = time() + (30 * 24 * 60 * 60); // 30 dias

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

    error_log("[REMEMBER_ME] Token criado para usuário: $user_id, expira em: $expira_em, cookie expira: " . date('Y-m-d H:i:s', $cookie_expiry));
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

      // ✅ Se marcou "lembrar-me", cria token persistente
      if ($lembrar_me) {
        $cookie_success = definirCookieLembrarMe($user['id'], $pdo);
        if ($cookie_success) {
          error_log("[LOGIN] Usuário {$user['usuario']} logou com lembrar-me ativado - sessão válida por 30 dias");
        } else {
          error_log("[LOGIN] Falha ao criar cookie para usuário {$user['usuario']}");
        }

        // ✅ Limpeza preventiva de tokens expirados
        limparTokensExpirados($pdo);
      } else {
        error_log("[LOGIN] Usuário {$user['usuario']} logou sem lembrar-me - sessão padrão");
      }

      // Log do login
      error_log("[LOGIN] Login bem-sucedido: {$user['usuario']} (ID: {$user['id']}) - Método: " . ($lembrar_me ? 'REMEMBER_ME' : 'NORMAL'));

      // ✅ Debug das configurações de sessão
      error_log("[LOGIN] Configurações de sessão:");
      error_log("  - session.gc_maxlifetime: " . ini_get('session.gc_maxlifetime') . " segundos (" . (ini_get('session.gc_maxlifetime') / 86400) . " dias)");
      error_log("  - session.cookie_lifetime: " . ini_get('session.cookie_lifetime') . " segundos (" . (ini_get('session.cookie_lifetime') / 86400) . " dias)");

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
