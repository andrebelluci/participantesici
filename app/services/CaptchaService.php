<?php
// app/services/CaptchaService.php
class CaptchaService
{

  private const SITE_KEY = '6Lfi-HMrAAAAAEkx8_QU758ogmV7v_l0KAjKrFrS';
  private const SECRET_KEY = '6Lfi-HMrAAAAAOPjGrFWvnHvY3X6rejrgFTW3EsY';
  private const MAX_TENTATIVAS = 5;
  private const TEMPO_RESET = 1800; // 30 minutos em segundos

  /**
   * Verifica se deve mostrar o captcha baseado no número de tentativas
   * @param string $identificador - IP ou outro identificador único
   * @return bool
   */
  public static function deveMostrarCaptcha($identificador)
  {
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
    if (empty($token)) {
      return [
        'success' => false,
        'error' => 'Token do captcha não fornecido'
      ];
    }

    $ip = $ip ?: $_SERVER['REMOTE_ADDR'] ?? '';

    $dados = [
      'secret' => self::SECRET_KEY,
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
   * @return string
   */
  public static function getSiteKey()
  {
    return self::SITE_KEY;
  }

  /**
   * Gera o HTML do reCAPTCHA v2
   * @return string
   */
  public static function gerarHtmlCaptcha()
  {
    return '<div class="g-recaptcha" data-sitekey="' . self::SITE_KEY . '"></div>';
  }

  /**
   * Gera o script necessário para o reCAPTCHA
   * @return string
   */
  public static function gerarScriptCaptcha()
  {
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
}