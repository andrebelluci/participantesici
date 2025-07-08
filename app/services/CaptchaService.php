<?php
// app/services/CaptchaService.php

if (!function_exists('env')) {
  require_once __DIR__ . '/../config/config.php';
}

class CaptchaService
{
  private const MAX_TENTATIVAS = 5;
  private const TEMPO_RESET = 1800; // 30 minutos em segundos

  /**
   * Obtém a chave do site do .env
   * @return string
   */
  private static function getSiteKeyFromEnv()
  {
    return env('RECAPTCHA_SITE_KEY');
  }

  /**
   * Obtém a chave secreta do .env
   * @return string
   */
  private static function getSecretKeyFromEnv()
  {
    return env('RECAPTCHA_SECRET_KEY');
  }

  /**
   * Verifica se o reCAPTCHA está configurado
   * @return bool
   */
  public static function isCaptchaConfigurado()
  {
    $siteKey = self::getSiteKeyFromEnv();
    $secretKey = self::getSecretKeyFromEnv();

    return !empty($siteKey) && !empty($secretKey);
  }

  /**
   * Verifica se deve mostrar o captcha baseado no número de tentativas
   * @param string $identificador - IP ou outro identificador único
   * @return bool
   */
  public static function deveMostrarCaptcha($identificador)
  {
    // Se não está configurado, não mostra captcha
    if (!self::isCaptchaConfigurado()) {
      return false;
    }

    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }

    $chave = 'tentativas_' . md5($identificador);
    $tentativas = $_SESSION[$chave] ?? 0;

    return $tentativas >= self::MAX_TENTATIVAS;
  }

  /**
   * Incrementa o contador de tentativas falhadas
   * @param string $identificador - IP ou outro identificador único
   */
  public static function incrementarTentativas($identificador)
  {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }

    $chave = 'tentativas_' . md5($identificador);
    $chaveTime = 'primeiro_erro_' . md5($identificador);

    // Se é a primeira tentativa falha, marca o tempo
    if (!isset($_SESSION[$chave])) {
      $_SESSION[$chaveTime] = time();
      $_SESSION[$chave] = 1;
    } else {
      $_SESSION[$chave]++;
    }

    error_log("[CAPTCHA] Tentativas para $identificador: " . $_SESSION[$chave]);
  }

  /**
   * Reseta o contador de tentativas (chamado em login bem-sucedido)
   * @param string $identificador - IP ou outro identificador único
   */
  public static function resetarTentativas($identificador)
  {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }

    $chave = 'tentativas_' . md5($identificador);
    $chaveTime = 'primeiro_erro_' . md5($identificador);

    unset($_SESSION[$chave]);
    unset($_SESSION[$chaveTime]);

    error_log("[CAPTCHA] Tentativas resetadas para $identificador");
  }

  /**
   * Verifica se o tempo de reset expirou (30 minutos)
   * @param string $identificador - IP ou outro identificador único
   */
  public static function verificarTempoReset($identificador)
  {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }

    $chave = 'tentativas_' . md5($identificador);
    $chaveTime = 'primeiro_erro_' . md5($identificador);

    if (isset($_SESSION[$chaveTime])) {
      $tempoDecorrido = time() - $_SESSION[$chaveTime];

      // Se passou mais de 30 minutos, reseta
      if ($tempoDecorrido > self::TEMPO_RESET) {
        unset($_SESSION[$chave]);
        unset($_SESSION[$chaveTime]);
        error_log("[CAPTCHA] Tempo expirado, resetando tentativas para $identificador");
        return true;
      }
    }

    return false;
  }

  /**
   * Verifica o token do reCAPTCHA com o Google
   * @param string $token - Token retornado pelo reCAPTCHA
   * @param string $ip - IP do usuário (opcional)
   * @return array
   */
  public static function verificarCaptcha($token, $ip = null)
  {
    // Se não está configurado, considera válido (modo de desenvolvimento)
    if (!self::isCaptchaConfigurado()) {
      error_log("[CAPTCHA] reCAPTCHA não configurado - permitindo acesso");
      return [
        'success' => true,
        'message' => 'Captcha não configurado'
      ];
    }

    if (empty($token)) {
      return [
        'success' => false,
        'error' => 'Token do captcha não fornecido'
      ];
    }

    $ip = $ip ?: $_SERVER['REMOTE_ADDR'] ?? '';
    $secretKey = self::getSecretKeyFromEnv();

    $dados = [
      'secret' => $secretKey,
      'response' => $token,
      'remoteip' => $ip
    ];

    $opcoes = [
      'http' => [
        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
        'method' => 'POST',
        'content' => http_build_query($dados),
        'timeout' => 10
      ]
    ];

    $contexto = stream_context_create($opcoes);
    $resultado = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $contexto);

    if ($resultado === false) {
      error_log("[CAPTCHA] Erro ao conectar com Google reCAPTCHA");
      return [
        'success' => false,
        'error' => 'Erro de conexão com o serviço de captcha'
      ];
    }

    $resposta = json_decode($resultado, true);

    if (!$resposta) {
      error_log("[CAPTCHA] Resposta inválida do Google: " . $resultado);
      return [
        'success' => false,
        'error' => 'Resposta inválida do serviço de captcha'
      ];
    }

    if ($resposta['success']) {
      error_log("[CAPTCHA] Verificação bem-sucedida para IP: $ip");
      return [
        'success' => true,
        'score' => $resposta['score'] ?? null,
        'action' => $resposta['action'] ?? null
      ];
    } else {
      $erros = implode(', ', $resposta['error-codes'] ?? ['erro desconhecido']);
      error_log("[CAPTCHA] Falha na verificação: $erros");
      return [
        'success' => false,
        'error' => 'Captcha inválido: ' . $erros
      ];
    }
  }

  /**
   * Retorna a chave pública do site para uso no frontend
   * @return string|null
   */
  public static function getSiteKey()
  {
    return self::getSiteKeyFromEnv();
  }

  /**
   * Gera o HTML do reCAPTCHA v2
   * @return string
   */
  public static function gerarHtmlCaptcha()
  {
    $siteKey = self::getSiteKeyFromEnv();

    if (empty($siteKey)) {
      return '<!-- reCAPTCHA não configurado no .env -->';
    }

    return '<div class="g-recaptcha" data-sitekey="' . htmlspecialchars($siteKey) . '"></div>';
  }

  /**
   * Gera o script necessário para o reCAPTCHA
   * @return string
   */
  public static function gerarScriptCaptcha()
  {
    if (!self::isCaptchaConfigurado()) {
      return '<!-- reCAPTCHA não configurado no .env -->';
    }

    return '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
  }

  /**
   * Obtém o número atual de tentativas para debug
   * @param string $identificador
   * @return int
   */
  public static function obterTentativas($identificador)
  {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }
    $chave = 'tentativas_' . md5($identificador);
    return $_SESSION[$chave] ?? 0;
  }

  /**
   * Método para debug - mostra status da configuração
   * @return array
   */
  public static function getConfigStatus()
  {
    $siteKey = self::getSiteKeyFromEnv();
    $secretKey = self::getSecretKeyFromEnv();

    return [
      'configured' => self::isCaptchaConfigurado(),
      'site_key_set' => !empty($siteKey),
      'secret_key_set' => !empty($secretKey),
      'site_key_preview' => $siteKey ? substr($siteKey, 0, 10) . '...' : 'não definida',
    ];
  }
}
