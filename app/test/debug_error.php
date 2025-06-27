<?php
// app/test/debug_error.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h1>🔍 Debug de Erros</h1>";
echo "<style>body{font-family:Arial;margin:20px;}.error{color:red;}.success{color:green;}.info{color:blue;}</style>";

echo "<h2>1. Verificando arquivo enviar_recuperacao.php</h2>";

$arquivo = __DIR__ . '/../auth/actions/enviar_recuperacao.php';
if (file_exists($arquivo)) {
  echo "<span class='success'>✅ Arquivo existe</span><br>";
  echo "<span class='info'>Tamanho: " . filesize($arquivo) . " bytes</span><br>";
  echo "<span class='info'>Permissões: " . substr(sprintf('%o', fileperms($arquivo)), -4) . "</span><br>";
} else {
  echo "<span class='error'>❌ Arquivo não encontrado: $arquivo</span><br>";
}

echo "<h2>2. Testando inclusões de arquivos</h2>";

// Testa config/database.php
try {
  require_once __DIR__ . '/../config/database.php';
  echo "<span class='success'>✅ database.php carregado</span><br>";
} catch (Exception $e) {
  echo "<span class='error'>❌ Erro em database.php: " . $e->getMessage() . "</span><br>";
}

// Testa services/EmailService.php
try {
  require_once __DIR__ . '/../services/EmailService.php';
  echo "<span class='success'>✅ EmailService.php carregado</span><br>";
} catch (Exception $e) {
  echo "<span class='error'>❌ Erro em EmailService.php: " . $e->getMessage() . "</span><br>";
}

echo "<h2>3. Testando função env()</h2>";
try {
  $host = env('MAIL_HOST');
  echo "<span class='success'>✅ Função env() funciona: $host</span><br>";
} catch (Exception $e) {
  echo "<span class='error'>❌ Erro na função env(): " . $e->getMessage() . "</span><br>";
}

echo "<h2>4. Testando conexão com banco</h2>";
try {
  $stmt = $pdo->query("SELECT 1");
  echo "<span class='success'>✅ Conexão com banco OK</span><br>";
} catch (Exception $e) {
  echo "<span class='error'>❌ Erro no banco: " . $e->getMessage() . "</span><br>";
}

echo "<h2>5. Simulando POST para enviar_recuperacao.php</h2>";

// Simula os dados do POST
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['usuario'] = 'admin'; // Use um usuário que existe
session_start();

echo "<span class='info'>Executando o código...</span><br>";

try {
  // Captura saída
  ob_start();
  include $arquivo;
  $output = ob_get_clean();

  echo "<span class='success'>✅ Código executado sem erro fatal</span><br>";
  if ($output) {
    echo "<span class='info'>Saída: $output</span><br>";
  }

  // Verifica mensagens de sessão
  if (isset($_SESSION['recovery_error'])) {
    echo "<span class='error'>Erro: " . $_SESSION['recovery_error'] . "</span><br>";
  }
  if (isset($_SESSION['recovery_success'])) {
    echo "<span class='success'>Sucesso: " . $_SESSION['recovery_success'] . "</span><br>";
  }

} catch (Error $e) {
  echo "<span class='error'>❌ Erro Fatal: " . $e->getMessage() . "</span><br>";
  echo "<span class='error'>Linha: " . $e->getLine() . "</span><br>";
  echo "<span class='error'>Arquivo: " . $e->getFile() . "</span><br>";
} catch (Exception $e) {
  echo "<span class='error'>❌ Exception: " . $e->getMessage() . "</span><br>";
}

echo "<h2>6. Logs de erro do servidor</h2>";
$logFiles = [
  '/var/log/apache2/error.log',
  '/var/log/php_errors.log',
  ini_get('error_log')
];

foreach ($logFiles as $logFile) {
  if ($logFile && file_exists($logFile)) {
    echo "<span class='info'>Log encontrado: $logFile</span><br>";
    $lines = file($logFile);
    $recent = array_slice($lines, -10); // Últimas 10 linhas
    echo "<pre style='background:#f5f5f5;padding:10px;'>" . htmlspecialchars(implode('', $recent)) . "</pre>";
    break;
  }
}

echo "<p><strong>⚠️ Remova este arquivo após o debug!</strong></p>";
