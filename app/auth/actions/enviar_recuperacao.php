<?php
// app/auth/actions/enviar_recuperacao.php - VERSÃO ATUALIZADA COM CAPTCHA
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../services/EmailService.php';
require_once __DIR__ . '/../../services/CaptchaService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /esqueci-senha');
    exit;
}

$usuario = trim($_POST['usuario'] ?? '');
$captcha_token = $_POST['g-recaptcha-response'] ?? '';

// Identifica o usuário pelo IP
$identificador = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// Verifica se o tempo de reset expirou
CaptchaService::verificarTempoReset($identificador);

if (empty($usuario)) {
    CaptchaService::incrementarTentativas($identificador);
    $_SESSION['error'] = 'Por favor, informe seu usuário.';
    header('Location: /esqueci-senha');
    exit;
}

// Verifica se deve mostrar captcha e se foi preenchido
$deveMostrarCaptcha = CaptchaService::deveMostrarCaptcha($identificador);

if ($deveMostrarCaptcha) {
    if (empty($captcha_token)) {
        $_SESSION['error'] = 'Por favor, complete a verificação de segurança (captcha).';
        header('Location: /esqueci-senha');
        exit;
    }

    // Verifica o captcha
    $resultadoCaptcha = CaptchaService::verificarCaptcha($captcha_token, $identificador);

    if (!$resultadoCaptcha['success']) {
        CaptchaService::incrementarTentativas($identificador);
        $_SESSION['error'] = 'Verificação de segurança inválida. Tente novamente.';
        header('Location: /esqueci-senha');
        exit;
    }

    error_log("[RECUPERACAO] Captcha verificado com sucesso para $identificador");
}

try {
    // Verifica se o usuário existe
    $stmt = $pdo->prepare("SELECT id, nome, usuario, email FROM usuarios WHERE usuario = ?");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch();

    if (!$user) {
        // Incrementa tentativas mesmo se usuário não existir (segurança)
        CaptchaService::incrementarTentativas($identificador);
        error_log("[RECUPERACAO_DEBUG] Usuário não encontrado: $usuario");
        $_SESSION['error'] = 'Usuário não encontrado.';
        header('Location: /esqueci-senha');
        exit;
    }

    // Remove tokens antigos do usuário
    $stmt_cleanup = $pdo->prepare("DELETE FROM password_recovery_tokens WHERE user_id = ? OR expires_at < NOW()");
    $stmt_cleanup->execute([$user['id']]);

    // Gera novo token de recuperação
    $token = bin2hex(random_bytes(32));
    $expira_em = date('Y-m-d H:i:s', strtotime('+1 hour'));

    error_log("[RECUPERACAO_DEBUG] Token gerado para usuário: $usuario");

    // Salva o token na tabela password_recovery_tokens
    $stmt_token = $pdo->prepare("INSERT INTO password_recovery_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
    $stmt_token->execute([$user['id'], $token, $expira_em]);

    error_log("[RECUPERACAO_DEBUG] Token salvo no banco de dados");

    // Monta link de recuperação
    $base_url = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $link_recuperacao = $base_url . "/redefinir-senha?token=" . $token;

    // Define email de destino
    $email_destino = $user['email'] ?? 'admin@participantesici.com.br';

    error_log("[RECUPERACAO_DEBUG] Enviando email para: $email_destino");

    // Envia o email usando o EmailService
    $enviado = EmailService::enviarRecuperacaoSenha(
        $email_destino,
        $user['nome'],
        $link_recuperacao
    );

    if ($enviado) {
        // Reset tentativas apenas em caso de sucesso completo
        CaptchaService::resetarTentativas($identificador);
        error_log("[RECUPERACAO_DEBUG] Email enviado com sucesso");
        $_SESSION['success'] = 'Link de recuperação enviado! Verifique seu email.';
    } else {
        CaptchaService::incrementarTentativas($identificador);
        error_log("[RECUPERACAO_DEBUG] Falha no envio do email");
        $_SESSION['error'] = 'Erro ao enviar email. Tente novamente mais tarde.';
    }

} catch (Exception $e) {
    CaptchaService::incrementarTentativas($identificador);
    error_log("[RECUPERACAO_DEBUG] Erro geral: " . $e->getMessage());
    $_SESSION['error'] = 'Erro interno. Tente novamente mais tarde.';
}

header('Location: /esqueci-senha');
exit;
