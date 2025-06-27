<?php
// app/auth/actions/enviar_recuperacao.php - VERSÃO COM DEBUG
error_reporting(E_ALL);
ini_set('display_errors', 0); // Não mostra erros na tela
ini_set('log_errors', 1);

session_start();

// Função para log de debug
function debugLog($message)
{
    error_log("[RECUPERACAO_DEBUG] " . $message);
}

debugLog("=== INÍCIO DO PROCESSO DE RECUPERAÇÃO ===");

try {
    // ===== VERIFICAÇÕES INICIAIS =====
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        debugLog("Método não é POST, redirecionando");
        header("Location: /participantesici/public_html/esqueci-senha");
        exit;
    }

    debugLog("Método POST confirmado");

    // ===== CAPTURA DADOS =====
    $usuario = trim($_POST['usuario'] ?? '');
    debugLog("Usuário informado: " . ($usuario ? "SIM" : "VAZIO"));

    if (empty($usuario)) {
        debugLog("Usuário vazio, definindo erro");
        $_SESSION['recovery_error'] = 'Nome de usuário é obrigatório.';
        header("Location: /participantesici/public_html/esqueci-senha");
        exit;
    }

    // ===== CARREGA DEPENDÊNCIAS =====
    debugLog("Carregando database.php");
    require_once __DIR__ . '/../../config/database.php';
    debugLog("Database carregado com sucesso");

    debugLog("Carregando EmailService.php");
    require_once __DIR__ . '/../../services/EmailService.php';
    debugLog("EmailService carregado com sucesso");

    // ===== BUSCA USUÁRIO =====
    debugLog("Buscando usuário no banco: $usuario");

    $stmt = $pdo->prepare("SELECT id, usuario, nome, email FROM usuarios WHERE usuario = ?");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        debugLog("Usuário não encontrado: $usuario");
        // Por segurança, não revela se usuário existe ou não
        $_SESSION['recovery_success'] = 'Se o usuário existir, um e-mail de recuperação será enviado.';
        header("Location: /participantesici/public_html/esqueci-senha");
        exit;
    }

    debugLog("Usuário encontrado: " . $user['usuario'] . " (ID: " . $user['id'] . ")");

    // ===== VERIFICA EMAIL =====
    if (empty($user['email'])) {
        debugLog("Usuário sem email: " . $user['usuario']);
        $_SESSION['recovery_error'] = 'Este usuário não possui e-mail cadastrado. Contate o administrador.';
        header("Location: /participantesici/public_html/esqueci-senha");
        exit;
    }

    debugLog("Email do usuário: " . $user['email']);

    // ===== GERA TOKEN =====
    debugLog("Gerando token de recuperação");
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
    debugLog("Token gerado, expira em: $expires_at");

    // ===== VERIFICA TABELA DE TOKENS =====
    try {
        debugLog("Verificando tabela password_recovery_tokens");
        $stmt = $pdo->query("SHOW TABLES LIKE 'password_recovery_tokens'");
        if ($stmt->rowCount() == 0) {
            debugLog("ERRO: Tabela password_recovery_tokens não existe!");
            $_SESSION['recovery_error'] = 'Sistema de recuperação não configurado. Contate o administrador.';
            header("Location: /participantesici/public_html/esqueci-senha");
            exit;
        }
        debugLog("Tabela password_recovery_tokens existe");
    } catch (Exception $e) {
        debugLog("Erro ao verificar tabela: " . $e->getMessage());
        $_SESSION['recovery_error'] = 'Erro interno do sistema.';
        header("Location: /participantesici/public_html/esqueci-senha");
        exit;
    }

    // ===== LIMPA TOKENS ANTIGOS =====
    debugLog("Removendo tokens antigos");
    $stmt = $pdo->prepare("DELETE FROM password_recovery_tokens WHERE user_id = ? OR expires_at < NOW()");
    $stmt->execute([$user['id']]);
    debugLog("Tokens antigos removidos");

    // ===== SALVA NOVO TOKEN =====
    debugLog("Salvando novo token");
    $stmt = $pdo->prepare("
        INSERT INTO password_recovery_tokens (user_id, token, expires_at)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$user['id'], $token, $expires_at]);
    debugLog("Token salvo no banco");

    // ===== MONTA LINK =====
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $link_recuperacao = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/participantesici/public_html/redefinir-senha?token=" . $token;
    debugLog("Link de recuperação: $link_recuperacao");

    // ===== ENVIA EMAIL =====
    debugLog("Tentando enviar email para: " . $user['email']);

    // Verifica configurações de email antes de tentar enviar
    $mailHost = env('MAIL_HOST');
    $mailUser = env('MAIL_USERNAME');
    $mailPass = env('MAIL_PASSWORD');

    if (!$mailHost || !$mailUser || !$mailPass) {
        debugLog("ERRO: Configurações de email incompletas");
        debugLog("MAIL_HOST: " . ($mailHost ? "OK" : "VAZIO"));
        debugLog("MAIL_USERNAME: " . ($mailUser ? "OK" : "VAZIO"));
        debugLog("MAIL_PASSWORD: " . ($mailPass ? "OK" : "VAZIO"));

        $_SESSION['recovery_error'] = 'Sistema de email não configurado. Contate o administrador.';
        header("Location: /participantesici/public_html/esqueci-senha");
        exit;
    }

    $success = EmailService::enviarRecuperacaoSenha($user['email'], $user['nome'], $link_recuperacao);

    if ($success) {
        debugLog("Email enviado com sucesso");
        $_SESSION['recovery_success'] = 'E-mail de recuperação enviado! Verifique sua caixa de entrada.';

        // Log de segurança
        debugLog("Recovery email sent for user: " . $user['usuario'] . " (" . $user['email'] . ")");
    } else {
        debugLog("Falha no envio do email");
        $_SESSION['recovery_error'] = 'Erro ao enviar e-mail. Verifique as configurações do servidor.';
    }

} catch (Error $fatal) {
    debugLog("ERRO FATAL: " . $fatal->getMessage() . " em " . $fatal->getFile() . ":" . $fatal->getLine());
    $_SESSION['recovery_error'] = 'Erro interno. Tente novamente mais tarde.';
} catch (Exception $e) {
    debugLog("EXCEPTION: " . $e->getMessage());
    $_SESSION['recovery_error'] = 'Erro interno. Tente novamente mais tarde.';
} catch (Throwable $t) {
    debugLog("THROWABLE: " . $t->getMessage());
    $_SESSION['recovery_error'] = 'Erro interno. Tente novamente mais tarde.';
}

debugLog("=== FIM DO PROCESSO - Redirecionando ===");
header("Location: /participantesici/public_html/esqueci-senha");
exit;