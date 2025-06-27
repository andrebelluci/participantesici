<?php
// app/test/teste_recuperacao_senha.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/EmailService.php';

echo "<h1>🧪 Teste Completo do Sistema de Recuperação de Senha</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    .warning { color: orange; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
    .code { background: #f5f5f5; padding: 5px; border-radius: 4px; font-family: monospace; }
</style>";

// ===== TESTE 1: VERIFICAR TABELA =====
echo "<div class='section'>";
echo "<h2>1. 🗃️ Verificando Tabela de Tokens</h2>";

try {
  $stmt = $pdo->query("SHOW TABLES LIKE 'password_recovery_tokens'");
  if ($stmt->rowCount() > 0) {
    echo "<span class='success'>✅ Tabela 'password_recovery_tokens' existe</span><br>";

    // Verifica estrutura da tabela
    $stmt = $pdo->query("DESCRIBE password_recovery_tokens");
    $colunas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<span class='info'>Colunas: " . implode(', ', $colunas) . "</span><br>";
  } else {
    echo "<span class='error'>❌ Tabela 'password_recovery_tokens' não existe!</span><br>";
    echo "<span class='warning'>Execute o SQL fornecido para criar a tabela.</span><br>";
  }
} catch (Exception $e) {
  echo "<span class='error'>❌ Erro ao verificar tabela: " . $e->getMessage() . "</span><br>";
}
echo "</div>";

// ===== TESTE 2: CONFIGURAÇÕES DE EMAIL =====
echo "<div class='section'>";
echo "<h2>2. 📧 Verificando Configurações de Email</h2>";

$configs = [
  'MAIL_HOST' => env('MAIL_HOST'),
  'MAIL_PORT' => env('MAIL_PORT'),
  'MAIL_USERNAME' => env('MAIL_USERNAME'),
  'MAIL_FROM_EMAIL' => env('MAIL_FROM_EMAIL'),
  'MAIL_FROM_NAME' => env('MAIL_FROM_NAME')
];

$configsOk = true;
foreach ($configs as $chave => $valor) {
  if (empty($valor)) {
    echo "<span class='error'>❌ $chave não configurado</span><br>";
    $configsOk = false;
  } else {
    $valorExibir = ($chave === 'MAIL_PASSWORD') ? '***' : $valor;
    echo "<span class='success'>✅ $chave: $valorExibir</span><br>";
  }
}

if (!env('MAIL_PASSWORD')) {
  echo "<span class='error'>❌ MAIL_PASSWORD não configurado</span><br>";
  $configsOk = false;
}

echo $configsOk ? "<span class='success'>✅ Todas as configurações OK</span>" : "<span class='error'>❌ Configurações incompletas</span>";
echo "</div>";

// ===== TESTE 3: CONEXÃO SMTP =====
echo "<div class='section'>";
echo "<h2>3. 🔌 Testando Conexão SMTP</h2>";

if (EmailService::testarConfiguracao()) {
  echo "<span class='success'>✅ Conexão SMTP funcionando</span><br>";
} else {
  echo "<span class='error'>❌ Falha na conexão SMTP</span><br>";
  echo "<span class='warning'>Verifique as configurações ou logs do servidor</span><br>";
}
echo "</div>";

// ===== TESTE 4: BUSCAR USUÁRIO DE TESTE =====
echo "<div class='section'>";
echo "<h2>4. 👤 Verificando Usuários Disponíveis</h2>";

try {
  $stmt = $pdo->query("SELECT id, usuario, nome, email FROM usuarios LIMIT 5");
  $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

  if (empty($usuarios)) {
    echo "<span class='error'>❌ Nenhum usuário encontrado na tabela 'usuarios'</span><br>";
  } else {
    echo "<span class='success'>✅ Usuários encontrados:</span><br>";
    foreach ($usuarios as $user) {
      $emailStatus = $user['email'] ? '✅' : '❌';
      echo "• <strong>{$user['usuario']}</strong> ({$user['nome']}) $emailStatus {$user['email']}<br>";
    }
  }
} catch (Exception $e) {
  echo "<span class='error'>❌ Erro ao buscar usuários: " . $e->getMessage() . "</span><br>";
}
echo "</div>";

// ===== TESTE 5: ARQUIVOS NECESSÁRIOS =====
echo "<div class='section'>";
echo "<h2>5. 📁 Verificando Arquivos Necessários</h2>";

$arquivos = [
  '/participantesici/app/auth/templates/esqueci_senha.php' => 'Página "Esqueci minha senha"',
  '/participantesici/app/auth/templates/redefinir_senha.php' => 'Página "Redefinir senha"',
  '/participantesici/app/auth/actions/enviar_recuperacao.php' => 'Action enviar recuperação',
  '/participantesici/app/auth/actions/salvar_nova_senha.php' => 'Action salvar nova senha',
  '/participantesici/app/services/EmailService.php' => 'Serviço de email'
];

foreach ($arquivos as $caminho => $descricao) {
  $caminhoCompleto = __DIR__ . '/..' . $caminho;
  if (file_exists($caminhoCompleto)) {
    echo "<span class='success'>✅ $descricao</span><br>";
  } else {
    echo "<span class='error'>❌ $descricao ($caminho)</span><br>";
  }
}
echo "</div>";

// ===== TESTE 6: REGRAS DO .HTACCESS =====
echo "<div class='section'>";
echo "<h2>6. 🔧 Verificando Regras de URL</h2>";

$urls = [
  '/participantesici/public_html/esqueci-senha' => 'Página esqueci senha',
  '/participantesici/public_html/enviar-recuperacao' => 'Action enviar recuperação',
  '/participantesici/public_html/redefinir-senha' => 'Página redefinir senha',
  '/participantesici/public_html/salvar-nova-senha' => 'Action salvar nova senha'
];

echo "<span class='info'>URLs que devem funcionar:</span><br>";
foreach ($urls as $url => $descricao) {
  echo "• <span class='code'>$url</span> → $descricao<br>";
}
echo "</div>";

// ===== INSTRUÇÕES FINAIS =====
echo "<div class='section'>";
echo "<h2>📋 Próximos Passos</h2>";

echo "<h3>Se tudo estiver ✅:</h3>";
echo "<ol>";
echo "<li>Acesse <span class='code'>/participantesici/public_html/login</span></li>";
echo "<li>Clique em 'Esqueci minha senha'</li>";
echo "<li>Digite um nome de usuário válido</li>";
echo "<li>Verifique o email recebido</li>";
echo "<li>Clique no link do email</li>";
echo "<li>Defina uma nova senha</li>";
echo "</ol>";

echo "<h3>Se houver ❌:</h3>";
echo "<ol>";
echo "<li>Crie a tabela com o SQL fornecido</li>";
echo "<li>Configure o arquivo .env com suas credenciais</li>";
echo "<li>Verifique se todos os arquivos estão no lugar correto</li>";
echo "<li>Atualize o .htaccess com as novas regras</li>";
echo "</ol>";

echo "<h3>🚨 Para Debug:</h3>";
echo "<ul>";
echo "<li>Verifique os logs de erro do PHP</li>";
echo "<li>Ative o debug SMTP temporariamente</li>";
echo "<li>Teste com um email real primeiro</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><strong>⚠️ Importante:</strong> Remova este arquivo após os testes por segurança!</p>";
echo "<p><em>Arquivo: app/test/teste_recuperacao_senha.php</em></p>";
?>