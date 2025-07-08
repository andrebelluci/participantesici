<?php
/**
 * ARQUIVO: app/functions/check_auth_api.php
 *
 * Verificação de autenticação para APIs (retorna JSON)
 */

// Inicia sessão apenas se não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
  // Para APIs, retorna JSON em vez de redirect
  http_response_code(401);
  header('Content-Type: application/json');
  echo json_encode([
    'success' => false,
    'error' => 'Usuário não autenticado',
    'redirect' => '/login'
  ]);
  exit;
}

// Opcional: Verificar se a sessão não expirou
// if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
//     session_unset();
//     session_destroy();
//     http_response_code(401);
//     header('Content-Type: application/json');
//     echo json_encode([
//         'success' => false,
//         'error' => 'Sessão expirada',
//         'redirect' => '/login?expired=1'
//     ]);
//     exit;
// }

// Atualiza último acesso
$_SESSION['last_activity'] = time();
