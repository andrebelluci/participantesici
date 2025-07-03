<?php
/**
 * ARQUIVO: app/functions/check_auth.php
 *
 * Verificação de autenticação corrigida
 */

// Inicia sessão apenas se não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
  // Redireciona para login
  header("Location: /login");
  exit;
}

// Opcional: Verificar se a sessão não expirou
// if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
//     session_unset();
//     session_destroy();
//     header("Location: /login?expired=1");
//     exit;
// }

// Atualiza último acesso
$_SESSION['last_activity'] = time();