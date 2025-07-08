<?php
// app/auth/actions/logout.php - VERSÃO ATUALIZADA
session_start();
require_once __DIR__ . '/../../config/database.php';

// ✅ FUNÇÃO PARA LIMPAR TOKENS DE LEMBRAR-ME
function limparTokensLembrarMe($user_id, $pdo)
{
  try {
    // Remove todos os tokens do usuário
    $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
    $stmt->execute([$user_id]);

    // Remove cookie
    setcookie('remember_token', '', time() - 3600, '/');

    error_log("[LOGOUT] Tokens de lembrar-me removidos para usuário: $user_id");
    return true;

  } catch (Exception $e) {
    error_log("[LOGOUT] Erro ao remover tokens: " . $e->getMessage());
    return false;
  }
}

// Pega o ID do usuário antes de destruir a sessão
$user_id = $_SESSION['user_id'] ?? null;
$nome_usuario = $_SESSION['nome'] ?? 'Usuário';

// Limpa tokens de lembrar-me se o usuário estava logado
if ($user_id) {
  limparTokensLembrarMe($user_id, $pdo);
  error_log("[LOGOUT] Logout realizado para usuário: $nome_usuario (ID: $user_id)");
}

// Limpa todos os dados da sessão
$_SESSION = [];

// Destrói a sessão completamente
session_destroy();

// Mensagem de logout (opcional)
session_start();
$_SESSION['login_success'] = "Logout realizado com sucesso. Até logo, $nome_usuario!";

// Redireciona para a página de login
header("Location: /login");
exit;
