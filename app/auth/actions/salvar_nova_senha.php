<?php
// app/auth/actions/salvar_nova_senha.php
session_start();
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: /participantesici/public_html/login");
  exit;
}

$token = trim($_POST['token'] ?? '');
$novaSenha = trim($_POST['nova_senha'] ?? '');
$confirmarSenha = trim($_POST['confirmar_senha'] ?? '');

// Validações básicas
if (empty($token)) {
  $_SESSION['reset_error'] = 'Token de recuperação não encontrado.';
  header("Location: /participantesici/public_html/login");
  exit;
}

if (empty($novaSenha) || strlen($novaSenha) < 6) {
  $_SESSION['reset_error'] = 'A nova senha deve ter pelo menos 6 caracteres.';
  header("Location: /participantesici/public_html/redefinir-senha?token=" . urlencode($token));
  exit;
}

if ($novaSenha !== $confirmarSenha) {
  $_SESSION['reset_error'] = 'As senhas não coincidem.';
  header("Location: /participantesici/public_html/redefinir-senha?token=" . urlencode($token));
  exit;
}

try {
  $pdo->beginTransaction();

  // Verifica se o token é válido e busca o usuário
  $stmt = $pdo->prepare("
        SELECT prt.user_id, u.usuario, u.nome
        FROM password_recovery_tokens prt
        JOIN usuarios u ON prt.user_id = u.id
        WHERE prt.token = ? AND prt.expires_at > NOW()
    ");
  $stmt->execute([$token]);
  $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$resultado) {
    $_SESSION['reset_error'] = 'Link de recuperação inválido ou expirado.';
    header("Location: /participantesici/public_html/esqueci-senha");
    exit;
  }

  // Atualiza a senha do usuário
  $senhaHash = hash('sha256', $novaSenha);
  $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
  $stmt->execute([$senhaHash, $resultado['user_id']]);

  // Remove todos os tokens de recuperação deste usuário
  $stmt = $pdo->prepare("DELETE FROM password_recovery_tokens WHERE user_id = ?");
  $stmt->execute([$resultado['user_id']]);

  // Remove tokens expirados de outros usuários (limpeza)
  $stmt = $pdo->prepare("DELETE FROM password_recovery_tokens WHERE expires_at < NOW()");
  $stmt->execute();

  $pdo->commit();

  // Log de segurança
  error_log("✅ Senha redefinida com sucesso para usuário: " . $resultado['usuario']);

  // Mensagem de sucesso e redirecionamento
  $_SESSION['login_success'] = 'Senha alterada com sucesso! Faça login com sua nova senha.';
  header("Location: /participantesici/public_html/login");
  exit;

} catch (Exception $e) {
  $pdo->rollBack();
  error_log("❌ Erro ao redefinir senha: " . $e->getMessage());

  $_SESSION['reset_error'] = 'Erro interno. Tente novamente mais tarde.';
  header("Location: /participantesici/public_html/redefinir-senha?token=" . urlencode($token));
  exit;
}