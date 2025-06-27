<?php
// app/services/EmailService.php - VERSÃO MELHORADA
require_once __DIR__ . '/../config/config.php';

class EmailService {

    /**
     * Envia e-mail com configurações otimizadas para seu provedor
     */
    public static function enviarEmail($para, $assunto, $mensagemHtml, $mensagemTexto = '') {

        // Configurações específicas para mail.participantesici.com.br
        $host = env('MAIL_HOST', 'mail.participantesici.com.br');
        $username = env('MAIL_USERNAME');
        $password = env('MAIL_PASSWORD');
        $fromEmail = env('MAIL_FROM_EMAIL', $username);
        $fromName = env('MAIL_FROM_NAME', 'Instituto Céu Interior');

        // Validações
        if (!$host || !$username || !$password) {
            error_log("❌ Configurações de email incompletas no .env");
            return false;
        }

        if (!filter_var($para, FILTER_VALIDATE_EMAIL)) {
            error_log("❌ Email inválido: $para");
            return false;
        }

        // ✅ CONFIGURAÇÕES ESPECÍFICAS BASEADAS NAS SUAS INFORMAÇÕES
        $configs = [
            // Tenta SSL na porta 465 primeiro (mais seguro)
            [
                'host' => $host,
                'port' => 465,
                'crypto' => 'ssl',
                'timeout' => 15
            ],
            // Depois TLS na porta 587 com configurações mais flexíveis
            [
                'host' => $host,
                'port' => 587,
                'crypto' => 'tls',
                'timeout' => 15
            ],
            // Se necessário, tenta porta não criptografada (não recomendado)
            [
                'host' => $host,
                'port' => 587,
                'crypto' => 'none',
                'timeout' => 10
            ]
        ];

        foreach ($configs as $config) {
            error_log("🔄 Tentando conexão: {$config['host']}:{$config['port']} ({$config['crypto']})");

            if (self::enviarViaSMTP($para, $assunto, $mensagemHtml, $mensagemTexto, $config)) {
                return true;
            }
        }

        error_log("❌ Todas as tentativas de conexão falharam");
        return false;
    }

    /**
     * Envia via SMTP com configurações otimizadas
     */
    private static function enviarViaSMTP($para, $assunto, $html, $texto, $config) {

        $host = $config['host'];
        $port = $config['port'];
        $crypto = $config['crypto'];
        $timeout = $config['timeout'];

        $username = env('MAIL_USERNAME');
        $password = env('MAIL_PASSWORD');
        $fromEmail = env('MAIL_FROM_EMAIL', $username);
        $fromName = env('MAIL_FROM_NAME', 'Instituto Céu Interior');

        try {
            // ✅ CONEXÃO MELHORADA
            if ($crypto === 'ssl') {
                // SSL direto
                $connection = @fsockopen("ssl://$host", $port, $errno, $errstr, $timeout);
            } else {
                // Conexão normal (depois pode fazer STARTTLS)
                $connection = @fsockopen($host, $port, $errno, $errstr, $timeout);
            }

            if (!$connection) {
                error_log("❌ Conexão falhou: $errstr ($errno) - {$host}:{$port}");
                return false;
            }

            // ✅ MELHOR TRATAMENTO DE TIMEOUT
            stream_set_timeout($connection, $timeout);

            // Lê resposta inicial
            $response = self::lerResposta($connection);
            if (!$response || substr($response, 0, 3) !== '220') {
                error_log("❌ Resposta inicial inválida: $response");
                fclose($connection);
                return false;
            }

            // EHLO/HELO
            $domain = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
            self::enviarComando($connection, "EHLO $domain");
            $response = self::lerResposta($connection);

            // Se EHLO falhou, tenta HELO
            if (substr($response, 0, 3) !== '250') {
                self::enviarComando($connection, "HELO $domain");
                $response = self::lerResposta($connection);
                if (substr($response, 0, 3) !== '250') {
                    error_log("❌ HELO/EHLO falhou: $response");
                    fclose($connection);
                    return false;
                }
            }

            // ✅ STARTTLS (só se não for SSL e crypto = tls)
            if ($crypto === 'tls') {
                self::enviarComando($connection, "STARTTLS");
                $response = self::lerResposta($connection);

                if (substr($response, 0, 3) === '220') {
                    // ✅ MELHORIA: Configurações TLS mais flexíveis
                    $cryptoMethod = STREAM_CRYPTO_METHOD_TLS_CLIENT;

                    // Para compatibilidade com servidores mais antigos
                    if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                        $cryptoMethod = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT | STREAM_CRYPTO_METHOD_TLS_CLIENT;
                    }

                    if (!@stream_socket_enable_crypto($connection, true, $cryptoMethod)) {
                        error_log("❌ STARTTLS falhou");
                        fclose($connection);
                        return false;
                    }

                    // EHLO novamente após TLS
                    self::enviarComando($connection, "EHLO $domain");
                    $response = self::lerResposta($connection);
                } else {
                    error_log("❌ Servidor não suporta STARTTLS: $response");

                    // Se TLS obrigatório falhou, aborta
                    if ($crypto === 'tls') {
                        fclose($connection);
                        return false;
                    }
                }
            }

            // ✅ AUTENTICAÇÃO MELHORADA
            if (!self::autenticar($connection, $username, $password)) {
                fclose($connection);
                return false;
            }

            // MAIL FROM
            self::enviarComando($connection, "MAIL FROM: <$fromEmail>");
            $response = self::lerResposta($connection);
            if (substr($response, 0, 3) !== '250') {
                error_log("❌ MAIL FROM rejeitado: $response");
                fclose($connection);
                return false;
            }

            // RCPT TO
            self::enviarComando($connection, "RCPT TO: <$para>");
            $response = self::lerResposta($connection);
            if (substr($response, 0, 3) !== '250') {
                error_log("❌ RCPT TO rejeitado: $response");
                fclose($connection);
                return false;
            }

            // DATA
            self::enviarComando($connection, "DATA");
            $response = self::lerResposta($connection);
            if (substr($response, 0, 3) !== '354') {
                error_log("❌ DATA rejeitado: $response");
                fclose($connection);
                return false;
            }

            // Envia email
            $boundary = md5(time());
            $headers = self::construirCabecalhos($fromEmail, $fromName, $para, $assunto, $boundary);
            $corpo = self::construirCorpo($html, $texto, $boundary);

            self::enviarComando($connection, $headers . $corpo . "\r\n.");
            $response = self::lerResposta($connection);
            if (substr($response, 0, 3) !== '250') {
                error_log("❌ Envio rejeitado: $response");
                fclose($connection);
                return false;
            }

            // QUIT
            self::enviarComando($connection, "QUIT");
            fclose($connection);

            error_log("✅ Email enviado com sucesso via {$host}:{$port} ($crypto)");
            return true;

        } catch (Exception $e) {
            error_log("❌ Erro SMTP: " . $e->getMessage());
            if (isset($connection) && is_resource($connection)) {
                fclose($connection);
            }
            return false;
        }
    }

    /**
     * ✅ NOVA FUNÇÃO: Autenticação melhorada
     */
    private static function autenticar($connection, $username, $password) {
        // Tenta AUTH LOGIN primeiro
        self::enviarComando($connection, "AUTH LOGIN");
        $response = self::lerResposta($connection);

        if (substr($response, 0, 3) === '334') {
            // Username
            self::enviarComando($connection, base64_encode($username));
            $response = self::lerResposta($connection);

            if (substr($response, 0, 3) === '334') {
                // Password
                self::enviarComando($connection, base64_encode($password));
                $response = self::lerResposta($connection);

                if (substr($response, 0, 3) === '235') {
                    error_log("✅ Autenticação LOGIN bem-sucedida");
                    return true;
                } else {
                    error_log("❌ Senha rejeitada: $response");
                    return false;
                }
            } else {
                error_log("❌ Username rejeitado: $response");
                return false;
            }
        }

        // Se AUTH LOGIN falhou, tenta AUTH PLAIN
        $authString = base64_encode("\0$username\0$password");
        self::enviarComando($connection, "AUTH PLAIN $authString");
        $response = self::lerResposta($connection);

        if (substr($response, 0, 3) === '235') {
            error_log("✅ Autenticação PLAIN bem-sucedida");
            return true;
        } else {
            error_log("❌ AUTH PLAIN falhou: $response");
            return false;
        }
    }

    /**
     * ✅ NOVA FUNÇÃO: Enviar comando SMTP
     */
    private static function enviarComando($connection, $comando) {
        fputs($connection, $comando . "\r\n");
        error_log("📤 SMTP: $comando");
    }

    /**
     * ✅ NOVA FUNÇÃO: Ler resposta SMTP
     */
    private static function lerResposta($connection) {
        $response = fgets($connection, 515);
        $response = rtrim($response);
        error_log("📥 SMTP: $response");
        return $response;
    }

    /**
     * Constrói cabeçalhos do email
     */
    private static function construirCabecalhos($fromEmail, $fromName, $para, $assunto, $boundary) {
        $headers = "From: \"$fromName\" <$fromEmail>\r\n";
        $headers .= "Reply-To: $fromEmail\r\n";
        $headers .= "To: $para\r\n";
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($assunto) . "?=\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        $headers .= "\r\n";

        return $headers;
    }

    /**
     * Constrói corpo do email
     */
    private static function construirCorpo($html, $texto, $boundary) {
        if (empty($texto)) {
            $texto = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>'], "\n", $html));
        }

        $corpo = "--$boundary\r\n";
        $corpo .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $corpo .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $corpo .= quoted_printable_encode($texto) . "\r\n\r\n";

        $corpo .= "--$boundary\r\n";
        $corpo .= "Content-Type: text/html; charset=UTF-8\r\n";
        $corpo .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $corpo .= quoted_printable_encode($html) . "\r\n\r\n";

        $corpo .= "--$boundary--\r\n";

        return $corpo;
    }

    /**
     * Envia email de recuperação
     */
    public static function enviarRecuperacaoSenha($email, $nome, $linkRecuperacao) {
        $assunto = "🔐 Recuperação de Senha - " . env('MAIL_FROM_NAME', 'Sistema');
        $mensagemHtml = self::templateRecuperacaoSenha($nome, $linkRecuperacao);
        return self::enviarEmail($email, $assunto, $mensagemHtml);
    }

    /**
     * Template HTML
     */
    private static function templateRecuperacaoSenha($nome, $link) {
        $sistemaUrl = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

        return "
        <!DOCTYPE html>
        <html>
        <head><meta charset='UTF-8'><title>Recuperação de Senha</title></head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px;'>
            <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                <div style='background: linear-gradient(135deg, #00bfff, #1D4ED8); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0;'>
                    <h1 style='margin: 0; font-size: 24px;'>🔐 Recuperação de Senha</h1>
                    <p style='margin: 10px 0 0 0; opacity: 0.9;'>Instituto Céu Interior</p>
                </div>
                <div style='padding: 30px;'>
                    <p>Olá, <strong>" . htmlspecialchars($nome) . "</strong>!</p>
                    <p>Você solicitou a recuperação de senha para sua conta no sistema de gestão de participantes.</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='$link' style='display: inline-block; background: #00bfff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: 600;'>🔑 Redefinir Senha</a>
                    </div>
                    <div style='background: #fff8e1; border-left: 4px solid #ffb74d; padding: 15px; margin: 20px 0;'>
                        <strong>⚠️ Importante:</strong><br>
                        • Este link expira em 1 hora<br>
                        • Se não foi você, ignore este email
                    </div>
                    <p><strong>Se o botão não funcionar:</strong></p>
                    <div style='background: #f5f5f5; padding: 10px; border-radius: 4px; word-break: break-all; font-family: monospace; font-size: 12px;'>$link</div>
                </div>
                <div style='background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 8px 8px;'>
                    <p>Email automático do sistema de gestão de participantes</p>
                    <p>Instituto Céu Interior © " . date('Y') . "</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Testa configuração
     */
    public static function testarConfiguracao() {
        $host = env('MAIL_HOST');

        // Testa porta 465 (SSL)
        $connection = @fsockopen("ssl://$host", 465, $errno, $errstr, 10);
        if ($connection) {
            fclose($connection);
            error_log("✅ Porta 465 (SSL) acessível");
            return true;
        }

        // Testa porta 587 (TLS)
        $connection = @fsockopen($host, 587, $errno, $errstr, 10);
        if ($connection) {
            fclose($connection);
            error_log("✅ Porta 587 (TLS) acessível");
            return true;
        }

        error_log("❌ Nenhuma porta SMTP acessível");
        return false;
    }
}