<?php
// app/auth/actions/enviar_recuperacao.php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../services/EmailService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /participantesici/public_html/esqueci-senha');
    exit;
}

$usuario = trim($_POST['usuario'] ?? '');

if (empty($usuario)) {
    $_SESSION['error'] = 'Por favor, informe seu usuário.';
    header('Location: /participantesici/public_html/esqueci-senha');
    exit;
}

try {
    // Verifica se o usuário existe
    $stmt = $pdo->prepare("SELECT id, nome, usuario, email FROM usuarios WHERE usuario = ?");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch();

    if (!$user) {
        error_log("[RECUPERACAO_DEBUG] Usuário não encontrado: $usuario");
        $_SESSION['error'] = 'Usuário não encontrado.';
        header('Location: /participantesici/public_html/esqueci-senha');
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
    $link_recuperacao = $base_url . "/participantesici/public_html/redefinir-senha?token=" . $token;

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
        error_log("[RECUPERACAO_DEBUG] Email enviado com sucesso");
        $_SESSION['success'] = 'Link de recuperação enviado! Verifique seu email.';
    } else {
        error_log("[RECUPERACAO_DEBUG] Falha no envio do email");
        $_SESSION['error'] = 'Erro ao enviar email. Tente novamente mais tarde.';
    }

} catch (Exception $e) {
    error_log("[RECUPERACAO_DEBUG] Erro geral: " . $e->getMessage());
    $_SESSION['error'] = 'Erro interno. Tente novamente mais tarde.';
}

error_log("[RECUPERACAO_DEBUG] === FIM DO PROCESSO - Redirecionando ===");
header('Location: /participantesici/public_html/esqueci-senha');
exit;

/**
 * Envia email de recuperação usando socket SMTP puro
 * @param array $user Dados do usuário
 * @param string $token Token de recuperação
 * @param array $config Configurações de email
 * @return bool
 */
function enviarEmailRecuperacao($user, $token, $config) {
    // Email fixo para teste (você pode mudar depois)
    $destinatario = 'admin@participantesici.com.br';

    // Array de configurações para tentar
    $tentativas = [
        // 🔧 Tentativa 1: SSL na porta 465 (sua configuração do .env)
        [
            'host' => $config['host'],
            'port' => $config['port'],
            'encryption' => $config['encryption'],
            'context' => [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                    'ciphers' => 'HIGH:!SSLv2:!SSLv3'
                ]
            ]
        ],
        // 🔧 Tentativa 2: TLS na porta 587 (fallback)
        [
            'host' => $config['host'],
            'port' => 587,
            'encryption' => 'tls',
            'context' => [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                    'ciphers' => 'HIGH:!SSLv2:!SSLv3'
                ]
            ]
        ]
    ];

    foreach ($tentativas as $i => $tentativa) {
        error_log("🔄 Tentativa " . ($i + 1) . ": {$tentativa['host']}:{$tentativa['port']} ({$tentativa['encryption']})");

        $resultado = tentarEnvioSMTP($destinatario, $user, $token, $config, $tentativa);

        if ($resultado) {
            error_log("✅ Sucesso na tentativa " . ($i + 1));
            return true;
        }

        error_log("❌ Falha na tentativa " . ($i + 1));
    }

    error_log("❌ Todas as tentativas de conexão falharam");
    return false;
}

/**
 * Tenta envio SMTP com configuração específica
 */
function tentarEnvioSMTP($destinatario, $user, $token, $config, $tentativa) {
    $socket = null;

    try {
        // 🔧 Criação do contexto SSL/TLS melhorada
        $context = stream_context_create($tentativa['context']);

        // 🔧 Determinação do protocolo baseado na porta e criptografia
        if ($tentativa['encryption'] === 'ssl' || $tentativa['port'] == 465) {
            $protocol = 'ssl://';
        } else {
            $protocol = '';
        }

        $host_connection = $protocol . $tentativa['host'];

        error_log("🔌 Tentando conexão: {$host_connection}:{$tentativa['port']} ({$tentativa['encryption']})");

        // Conecta ao servidor
        $socket = stream_socket_client(
            "{$host_connection}:{$tentativa['port']}",
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) {
            error_log("❌ Falha na conexão: $errstr ($errno)");
            return false;
        }

        // 🔧 Configura timeout
        stream_set_timeout($socket, 30);

        // Lê resposta inicial
        $response = fgets($socket, 512);
        error_log("📥 SMTP: " . trim($response));

        if (!str_starts_with($response, '220')) {
            error_log("❌ Resposta inicial inválida: " . trim($response));
            fclose($socket);
            return false;
        }

        // 🔧 EHLO com nome do servidor correto
        $ehlo_host = $tentativa['host'];
        $ehlo_sent = fputs($socket, "EHLO {$ehlo_host}\r\n");
        if (!$ehlo_sent) {
            error_log("❌ Falha ao enviar EHLO");
            fclose($socket);
            return false;
        }

        error_log("📤 SMTP: EHLO {$ehlo_host}");

        // Lê todas as linhas de resposta do EHLO
        do {
            $response = fgets($socket, 512);
            error_log("📥 SMTP: " . trim($response));

            if (str_starts_with($response, '250 ')) {
                break; // Última linha da resposta
            } elseif (!str_starts_with($response, '250-')) {
                error_log("❌ EHLO falhou: " . trim($response));
                fclose($socket);
                return false;
            }
        } while (!feof($socket));

        // 🔧 STARTTLS se necessário (apenas para porta 587 sem SSL)
        if ($tentativa['encryption'] === 'tls' && $tentativa['port'] == 587) {
            fputs($socket, "STARTTLS\r\n");
            error_log("📤 SMTP: STARTTLS");

            $response = fgets($socket, 512);
            error_log("📥 SMTP: " . trim($response));

            if (!str_starts_with($response, '220')) {
                error_log("❌ STARTTLS falhou: " . trim($response));
                fclose($socket);
                return false;
            }

            // Ativa criptografia TLS
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                error_log("❌ Falha ao ativar TLS");
                fclose($socket);
                return false;
            }

            // Novo EHLO após TLS
            fputs($socket, "EHLO {$ehlo_host}\r\n");
            error_log("📤 SMTP: EHLO {$ehlo_host} (pós-TLS)");

            do {
                $response = fgets($socket, 512);
                error_log("📥 SMTP: " . trim($response));
            } while (!str_starts_with($response, '250 '));
        }

        // Autenticação
        fputs($socket, "AUTH LOGIN\r\n");
        error_log("📤 SMTP: AUTH LOGIN");

        $response = fgets($socket, 512);
        error_log("📥 SMTP: " . trim($response));

        if (!str_starts_with($response, '334')) {
            error_log("❌ AUTH LOGIN falhou: " . trim($response));
            fclose($socket);
            return false;
        }

        // Username
        fputs($socket, base64_encode($config['username']) . "\r\n");
        $response = fgets($socket, 512);
        error_log("📥 SMTP: " . trim($response));

        if (!str_starts_with($response, '334')) {
            error_log("❌ Username rejeitado: " . trim($response));
            fclose($socket);
            return false;
        }

        // Password
        fputs($socket, base64_encode($config['password']) . "\r\n");
        $response = fgets($socket, 512);
        error_log("📥 SMTP: " . trim($response));

        if (!str_starts_with($response, '235')) {
            error_log("❌ Senha rejeitada: " . trim($response));
            fclose($socket);
            return false;
        }

        error_log("✅ Autenticação bem-sucedida");

        // Envelope
        fputs($socket, "MAIL FROM:<{$config['from_email']}>\r\n");
        $response = fgets($socket, 512);
        if (!str_starts_with($response, '250')) {
            error_log("❌ MAIL FROM rejeitado: " . trim($response));
            fclose($socket);
            return false;
        }

        fputs($socket, "RCPT TO:<$destinatario>\r\n");
        $response = fgets($socket, 512);
        if (!str_starts_with($response, '250')) {
            error_log("❌ RCPT TO rejeitado: " . trim($response));
            fclose($socket);
            return false;
        }

        fputs($socket, "DATA\r\n");
        $response = fgets($socket, 512);
        if (!str_starts_with($response, '354')) {
            error_log("❌ DATA rejeitado: " . trim($response));
            fclose($socket);
            return false;
        }

        // 🔧 Email content
        $link_recuperacao = "http://localhost/participantesici/public_html/redefinir-senha?token=" . $token;

        $subject = "=?UTF-8?B?" . base64_encode("Recuperação de Senha - Instituto Céu Interior") . "?=";
        $body = "Olá {$user['nome']},\n\n";
        $body .= "Você solicitou a recuperação da sua senha.\n\n";
        $body .= "Clique no link abaixo para redefinir sua senha:\n";
        $body .= "$link_recuperacao\n\n";
        $body .= "Este link expira em 1 hora.\n\n";
        $body .= "Se você não solicitou esta recuperação, ignore este email.\n\n";
        $body .= "Atenciosamente,\n";
        $body .= "Instituto Céu Interior";

        $message = "From: {$config['from_name']} <{$config['from_email']}>\r\n";
        $message .= "To: $destinatario\r\n";
        $message .= "Subject: $subject\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 8bit\r\n";
        $message .= "\r\n";
        $message .= $body;
        $message .= "\r\n.\r\n";

        fputs($socket, $message);
        $response = fgets($socket, 512);

        if (str_starts_with($response, '250')) {
            error_log("✅ Email enviado com sucesso");
            fputs($socket, "QUIT\r\n");
            fclose($socket);
            return true;
        }

        error_log("❌ Falha no envio: " . trim($response));
        fclose($socket);
        return false;

    } catch (Exception $e) {
        error_log("❌ Exceção no envio SMTP: " . $e->getMessage());
        if ($socket) {
            fclose($socket);
        }
        return false;
    }
}
?>