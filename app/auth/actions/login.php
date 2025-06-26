<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $usuario = $_POST['usuario'];
  $senha = hash('sha256', $_POST['senha']);

  $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ? AND senha = ?");
  $stmt->execute([$usuario, $senha]);
  $user = $stmt->fetch();

  if ($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['nome'] = $user['nome'];

    // ✅ ADICIONA MENSAGEM DE SUCESSO
    $_SESSION['login_success'] = 'Login efetuado com sucesso!';

    header("Location: /participantesici/public_html/login?success=1");
    exit;
  } else {
    $_SESSION['login_error'] = 'Usuário ou senha inválidos!';
    // Evita cache com parâmetro fake de tempo
    header("Location: /participantesici/public_html/login?t=" . time());
    exit;
  }
}

header("Location: /participantesici/public_html/login");
exit;
