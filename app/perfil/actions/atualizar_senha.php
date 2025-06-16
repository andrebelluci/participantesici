<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../config/database.php';

$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  if (empty($_POST['senha_atual']) || empty($_POST['nova_senha']) || empty($_POST['confirmar_senha'])) {
    $_SESSION['mensagem'] = [
      'tipo' => 'error',
      'texto' => 'Todos os campos são obrigatórios.'
    ];
    header('Location: /participantesici/public_html/alterar_senha');
    exit;
  }

  $senha_atual = hash('sha256', $_POST['senha_atual']);
  $nova_senha = $_POST['nova_senha'];
  $confirmar_senha = $_POST['confirmar_senha'];

  $stmt = $pdo->prepare("SELECT senha FROM usuarios WHERE id = ?");
  $stmt->execute([$_SESSION['user_id']]);
  $usuario = $stmt->fetch();

  if ($usuario && $usuario['senha'] === $senha_atual) {
    if ($nova_senha === $confirmar_senha) {
      $nova_senha_hash = hash('sha256', $nova_senha);
      $stmt_update = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
      $stmt_update->execute([$nova_senha_hash, $_SESSION['user_id']]);
      $_SESSION['mensagem'] = ['tipo' => 'success', 'texto' => 'Senha alterada com sucesso!'];
    } else {
      $_SESSION['mensagem'] = ['tipo' => 'error', 'texto' => 'As senhas não coincidem.'];
    }
  } else {
    $_SESSION['mensagem'] = ['tipo' => 'error', 'texto' => 'Senha atual incorreta.'];
  }

  header("Location: /participantesici/public_html/alterar_senha?t=" . time());
  exit;
}
?>