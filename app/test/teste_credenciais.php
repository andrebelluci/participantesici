<?php
// app/test/teste_credenciais.php
require_once __DIR__ . '/../config/config.php';

echo "<h1>🔐 Teste de Credenciais SMTP</h1>";
echo "<style>body{font-family:Arial;margin:20px;}.success{color:green;}.error{color:red;}.info{color:blue;}.warning{color:orange;}</style>";

echo "<h2>1. 📋 Configurações Atuais</h2>";

$configs = [
  'MAIL_HOST' => env('MAIL_HOST'),
  'MAIL_PORT' => env('MAIL_PORT'),
  'MAIL_USERNAME' => env('MAIL_USERNAME'),
  'MAIL_PASSWORD' => env('MAIL_PASSWORD') ? '***' : 'NÃO DEFINIDO',
  'MAIL_FROM_EMAIL' => env('MAIL_FROM_EMAIL'),
  'MAIL_FROM_NAME' => env('MAIL_FROM_NAME')
];

foreach ($configs as $chave => $valor) {
  $cor = empty($valor) ? 'error' : 'success';
  echo "<span class='$cor'>$chave: $valor</span><br>";
}

echo "<h2>2. 🌐 Teste de Conectividade</h2>";

$host = env('MAIL_HOST', 'mail.participantesici.com.br');
$portas = [
  465 => 'SSL/TLS',
  587 => 'STARTTLS',
  25 => 'Não criptografado',
  993 => 'IMAP SSL',
  995 => 'POP3 SSL'
];

foreach ($portas as $porta => $descricao) {
  echo "<strong>Testando $host:$porta ($descricao):</strong> ";

  $connection = @fsockopen($host, $porta, $errno, $errstr, 5);
  if ($connection) {
    fclose($connection);
    echo "<span class='success'>✅ Conecta</span><br>";
  } else {
    echo "<span class='error'>❌ $errstr ($errno)</span><br>";
  }
}

echo "<h2>3. 🔐 Teste de Autenticação SMTP Manual</h2>";

function testeAutenticacaoManual($host, $porta, $username, $password, $ssl = false)
{
  try {
    $prefix = $ssl ? 'ssl://' : '';
    $connection = @fsockopen($prefix . $host, $porta, $errno, $errstr, 10);

    if (!$connection) {
      return "❌ Conexão falhou: $errstr";
    }

    // Lê resposta inicial
    $response = fgets($connection, 1024);
    if (substr($response, 0, 3) !== '220') {
      fclose($connection);
      return "❌ Resposta inicial inválida: $response";
    }

    // EHLO
    fputs($connection, "EHLO localhost\r\n");
    $response = fgets($connection, 1024);

    // AUTH LOGIN
    fputs($connection, "AUTH LOGIN\r\n");
    $response = fgets($connection, 1024);

    if (substr($response, 0, 3) !== '334') {
      fclose($connection);
      return "❌ Servidor não suporta AUTH LOGIN: $response";
    }

    // Username
    fputs($connection, base64_encode($username) . "\r\n");
    $response = fgets($connection, 1024);

    if (substr($response, 0, 3) !== '334') {
      fclose($connection);
      return "❌ Username rejeitado: $response";
    }

    // Password
    fputs($connection, base64_encode($password) . "\r\n");
    $response = fgets($connection, 1024);

    fclose($connection);

    if (substr($response, 0, 3) === '235') {
      return "✅ Autenticação bem-sucedida!";
    } else {
      return "❌ Senha incorreta: $response";
    }

  } catch (Exception $e) {
    return "❌ Erro: " . $e->getMessage();
  }
}

$username = env('MAIL_USERNAME');
$password = env('MAIL_PASSWORD');

if ($username && $password) {
  echo "<strong>Testando porta 465 (SSL):</strong><br>";
  echo testeAutenticacaoManual($host, 465, $username, $password, true) . "<br><br>";

  echo "<strong>Testando porta 587:</strong><br>";
  echo testeAutenticacaoManual($host, 587, $username, $password, false) . "<br><br>";
} else {
  echo "<span class='error'>❌ Username ou senha não configurados</span><br>";
}

echo "<h2>4. 📨 Configurações Recomendadas</h2>";

echo "<div style='background:#f0f8ff;padding:15px;border-radius:8px;'>";
echo "<h3>Para seu provedor (mail.participantesici.com.br):</h3>";
echo "<strong>Opção 1 - SSL (Recomendado):</strong><br>";
echo "<code style='background:#fff;padding:5px;'>
MAIL_HOST=mail.participantesici.com.br<br>
MAIL_PORT=465<br>
MAIL_USERNAME=nao-responda@participantesici.com.br<br>
MAIL_PASSWORD=SUA_SENHA_REAL<br>
MAIL_FROM_EMAIL=nao-responda@participantesici.com.br<br>
MAIL_FROM_NAME=\"Instituto Céu Interior\"
</code><br><br>";

echo "<strong>Opção 2 - TLS:</strong><br>";
echo "<code style='background:#fff;padding:5px;'>
MAIL_HOST=mail.participantesici.com.br<br>
MAIL_PORT=587<br>
MAIL_USERNAME=nao-responda@participantesici.com.br<br>
MAIL_PASSWORD=SUA_SENHA_REAL<br>
MAIL_FROM_EMAIL=nao-responda@participantesici.com.br<br>
MAIL_FROM_NAME=\"Instituto Céu Interior\"
</code>";
echo "</div>";

echo "<h2>5. 🚨 Possíveis Problemas</h2>";
echo "<ul>";
echo "<li><strong>Senha incorreta:</strong> Verifique se a senha está exata no .env</li>";
echo "<li><strong>Firewall:</strong> Seu servidor pode estar bloqueando as portas SMTP</li>";
echo "<li><strong>Limite de rate:</strong> Provedor pode estar limitando conexões</li>";
echo "<li><strong>IP bloqueado:</strong> Seu IP pode estar em blacklist</li>";
echo "</ul>";

echo "<p><strong>⚠️ Remova este arquivo após os testes!</strong></p>";
