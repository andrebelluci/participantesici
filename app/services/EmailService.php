<?php
// app/services/EmailService.php - VERSÃO COMPATÍVEL COM PHP 7.x
require_once __DIR__ . '/../config/config.php';

class EmailService {

    /**
     * Função de compatibilidade para str_starts_with (PHP < 8.0)
     */
    private static function str_starts_with($haystack, $needle) {
        return strncmp($haystack, $needle, strlen($needle)) === 0;
    }

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

        // ✅ CONFIGURAÇÕES CORRIGIDAS PARA O SEU SERVIDOR
        $configs = [
            // Tenta SSL na porta 465 primeiro (mais seguro)
            [
                'host' => $host,
                'port' => 465,
                'crypto' => 'ssl',
                'timeout' => 30
            ],
            // Depois TLS na porta 587 com configurações mais flexíveis
            [
                'host' => $host,
                'port' => 587,
                'crypto' => 'tls',
                'timeout' => 30
            ],
            // Última tentativa: porta 587 sem criptografia
            [
                'host' => $host,
                'port' => 587,
                'crypto' => 'none',
                'timeout' => 15
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

            // 🔧 CORREÇÃO: Lê resposta inicial COMPLETA (multilinhas)
            $response = self::lerRespostaCompleta($connection);
            if (!$response || !self::str_starts_with($response, '220')) {
                error_log("❌ Resposta inicial inválida: $response");
                fclose($connection);
                return false;
            }

            // 🔧 CORREÇÃO: EHLO/HELO com melhor tratamento
            $domain = self::obterDominio();

            // Tenta EHLO primeiro
            self::enviarComando($connection, "EHLO $domain");
            $response = self::lerRespostaCompleta($connection);

            // Se EHLO falhou, tenta HELO
            if (!self::str_starts_with($response, '250')) {
                error_log("📝 EHLO falhou, tentando HELO...");
                self::enviarComando($connection, "HELO $domain");
                $response = self::lerRespostaCompleta($connection);

                if (!self::str_starts_with($response, '250')) {
                    error_log("❌ HELO/EHLO falhou: $response");
                    fclose($connection);
                    return false;
                }
            }

            error_log("✅ Handshake SMTP bem-sucedido");

            // ✅ STARTTLS (só se não for SSL e crypto = tls)
            if ($crypto === 'tls') {
                self::enviarComando($connection, "STARTTLS");
                $response = self::lerRespostaCompleta($connection);

                if (self::str_starts_with($response, '220')) {
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
                    $response = self::lerRespostaCompleta($connection);

                    if (!self::str_starts_with($response, '250')) {
                        error_log("❌ EHLO pós-TLS falhou: $response");
                        fclose($connection);
                        return false;
                    }
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
            $response = self::lerRespostaCompleta($connection);
            if (!self::str_starts_with($response, '250')) {
                error_log("❌ MAIL FROM rejeitado: $response");
                fclose($connection);
                return false;
            }

            // RCPT TO
            self::enviarComando($connection, "RCPT TO: <$para>");
            $response = self::lerRespostaCompleta($connection);
            if (!self::str_starts_with($response, '250')) {
                error_log("❌ RCPT TO rejeitado: $response");
                fclose($connection);
                return false;
            }

            // DATA
            self::enviarComando($connection, "DATA");
            $response = self::lerRespostaCompleta($connection);
            if (!self::str_starts_with($response, '354')) {
                error_log("❌ DATA rejeitado: $response");
                fclose($connection);
                return false;
            }

            // Envia email
            $boundary = md5(time());
            $headers = self::construirCabecalhos($fromEmail, $fromName, $para, $assunto, $boundary);
            $corpo = self::construirCorpo($html, $texto, $boundary);

            self::enviarComando($connection, $headers . $corpo . "\r\n.");
            $response = self::lerRespostaCompleta($connection);
            if (!self::str_starts_with($response, '250')) {
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
     * 🔧 NOVA FUNÇÃO: Lê resposta SMTP completa (multilinhas)
     */
    private static function lerRespostaCompleta($connection) {
        $response = '';
        $finalResponse = '';

        do {
            $line = fgets($connection, 515);
            if ($line === false) {
                break;
            }

            $line = rtrim($line);
            $response .= $line . "\n";

            error_log("📥 SMTP: $line");

            // Se a linha tem 4+ caracteres e o 4º caractere é espaço, é a linha final
            if (strlen($line) >= 4 && $line[3] === ' ') {
                $finalResponse = $line;
                break;
            }

            // Se a linha tem 4+ caracteres e o 4º caractere é hífen, continua lendo
            if (strlen($line) >= 4 && $line[3] === '-') {
                continue;
            }

            // Se não tem formato padrão, assume que é a resposta final
            $finalResponse = $line;
            break;

        } while (!feof($connection));

        return $finalResponse ? $finalResponse : trim($response);
    }

    /**
     * 🔧 NOVA FUNÇÃO: Obtém domínio adequado para EHLO/HELO
     */
    private static function obterDominio() {
        // Tenta obter domínio real do servidor
        if (isset($_SERVER['HTTP_HOST'])) {
            return $_SERVER['HTTP_HOST'];
        }

        if (isset($_SERVER['SERVER_NAME'])) {
            return $_SERVER['SERVER_NAME'];
        }

        // 🔧 CORREÇÃO: Usar IP local real em vez de localhost
        $ip = gethostbyname(gethostname());
        if ($ip && $ip !== gethostname()) {
            return "[$ip]"; // Formato [IP] para EHLO
        }

        return 'localhost.localdomain';
    }

    /**
     * ✅ AUTENTICAÇÃO MELHORADA
     */
    private static function autenticar($connection, $username, $password) {
        // Tenta AUTH LOGIN primeiro
        self::enviarComando($connection, "AUTH LOGIN");
        $response = self::lerRespostaCompleta($connection);

        if (self::str_starts_with($response, '334')) {
            // Username
            self::enviarComando($connection, base64_encode($username));
            $response = self::lerRespostaCompleta($connection);

            if (self::str_starts_with($response, '334')) {
                // Password
                self::enviarComando($connection, base64_encode($password));
                $response = self::lerRespostaCompleta($connection);

                if (self::str_starts_with($response, '235')) {
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
        $response = self::lerRespostaCompleta($connection);

        if (self::str_starts_with($response, '235')) {
            error_log("✅ Autenticação PLAIN bem-sucedida");
            return true;
        } else {
            error_log("❌ AUTH PLAIN falhou: $response");
            return false;
        }
    }

    /**
     * ✅ FUNÇÃO: Enviar comando SMTP
     */
    private static function enviarComando($connection, $comando) {
        fputs($connection, $comando . "\r\n");
        error_log("📤 SMTP: $comando");
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
     * Template HTML para recuperação de senha
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
                    <p>Recebemos uma solicitação para recuperar sua senha. Se você não fez esta solicitação, pode ignorar este email.</p>

                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='" . htmlspecialchars($link) . "' style='display: inline-block; background: linear-gradient(135deg, #00bfff, #1D4ED8); color: white; padding: 15px 30px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 16px;'>
                            🔐 Redefinir Senha
                        </a>
                    </div>

                    <p style='color: #666; font-size: 14px;'>
                        <strong>⚠️ Importante:</strong> Este link expira em 1 hora por motivos de segurança.
                    </p>

                    <p style='color: #666; font-size: 14px;'>
                        Se o botão não funcionar, copie e cole este link no seu navegador:<br>
                        <a href='" . htmlspecialchars($link) . "' style='color: #1D4ED8; word-break: break-all;'>" . htmlspecialchars($link) . "</a>
                    </p>

                    <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>

                    <div style='text-align: center; color: #666; font-size: 12px;'>
                        <p>Esta mensagem foi enviada automaticamente. Não responda a este email.</p>
                        <p>© " . date('Y') . " Instituto Céu Interior - Todos os direitos reservados</p>
                        <p><a href='" . htmlspecialchars($sistemaUrl) . "' style='color: #1D4ED8;'>Acessar Sistema</a></p>
                    </div>
                </div>
            </div>
        </body>
        </html>";
    }
}
?>