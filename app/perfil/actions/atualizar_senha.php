<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../config/database.php';

// Função para validar senha
function validarSenha($senha)
{
  $erros = [];

  if (strlen($senha) < 8) {
    $erros[] = 'A senha deve ter pelo menos 8 caracteres';
  }

  if (!preg_match('/[A-Z]/', $senha)) {
    $erros[] = 'A senha deve conter pelo menos 1 letra maiúscula';
  }

  if (!preg_match('/[0-9]/', $senha)) {
    $erros[] = 'A senha deve conter pelo menos 1 número';
  }

  if (!preg_match('/[^a-zA-Z0-9]/', $senha)) {
    $erros[] = 'A senha deve conter pelo menos 1 caractere especial';
  }

  return $erros;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  // Debug para verificar se está chegando aqui
  error_log("[DEBUG_ALTERAR_SENHA] POST recebido para usuário: " . $_SESSION['user_id']);
  // Validação de campos vazios
  if (empty($_POST['senha_atual']) || empty($_POST['nova_senha']) || empty($_POST['confirmar_senha'])) {
    error_log("[DEBUG_ALTERAR_SENHA] Campos vazios detectados");
    $_SESSION['mensagem'] = [
      'tipo' => 'error',
      'texto' => 'Todos os campos são obrigatórios.'
    ];
    header('Location: /alterar_senha');
    exit;
  }

  $senha_atual = hash('sha256', $_POST['senha_atual']);
  $nova_senha = $_POST['nova_senha'];
  $confirmar_senha = $_POST['confirmar_senha'];

  // Validação de correspondência de senhas
  if ($nova_senha !== $confirmar_senha) {
    $_SESSION['mensagem'] = [
      'tipo' => 'error',
      'texto' => 'As senhas não coincidem.'
    ];
    header('Location: /alterar_senha');
    exit;
  }

  // Validação robusta da nova senha
  $errosSenha = validarSenha($nova_senha);
  if (!empty($errosSenha)) {
    $_SESSION['mensagem'] = [
      'tipo' => 'error',
      'texto' => 'Nova senha inválida: ' . implode(', ', $errosSenha)
    ];
    header('Location: /alterar_senha');
    exit;
  }

  // Verifica se a nova senha é igual à atual
  $nova_senha_hash = hash('sha256', $nova_senha);
  if ($senha_atual === $nova_senha_hash) {
    $_SESSION['mensagem'] = [
      'tipo' => 'error',
      'texto' => 'A nova senha deve ser diferente da senha atual.'
    ];
    header('Location: /alterar_senha');
    exit;
  }

  // Busca a senha atual do usuário
  $stmt = $pdo->prepare("SELECT senha FROM usuarios WHERE id = ?");
  $stmt->execute([$_SESSION['user_id']]);
  $usuario = $stmt->fetch();

  if ($usuario && $usuario['senha'] === $senha_atual) {
    try {
      // Atualiza a senha
      $stmt_update = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
      $stmt_update->execute([$nova_senha_hash, $_SESSION['user_id']]);

      $_SESSION['mensagem'] = [
        'tipo' => 'success',
        'texto' => 'Senha alterada com sucesso!'
      ];

      // Log da alteração de senha
      error_log("[SENHA_ALTERADA] Usuário ID: " . $_SESSION['user_id'] . " alterou a senha em " . date('Y-m-d H:i:s'));

      // Debug para confirmar que chegou até aqui
      error_log("[DEBUG_ALTERAR_SENHA] Senha alterada com sucesso, redirecionando...");

    } catch (Exception $e) {
      error_log("[ERRO_ALTERAR_SENHA] Usuário ID: " . $_SESSION['user_id'] . " - Erro: " . $e->getMessage());
      $_SESSION['mensagem'] = [
        'tipo' => 'error',
        'texto' => 'Erro interno. Tente novamente mais tarde.'
      ];
    }
  } else {
    $_SESSION['mensagem'] = [
      'tipo' => 'error',
      'texto' => 'Senha atual incorreta.'
    ];
  }

  header("Location: /alterar_senha?t=" . time());
  exit;
}
