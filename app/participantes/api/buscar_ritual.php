<?php
require_once __DIR__ . '/../../functions/check_auth_api.php';
require_once __DIR__ . '/../../config/database.php';

$pesquisa = $_GET['nome'] ?? null;

if (!$pesquisa) {
  echo json_encode(['error' => 'Pesquisa inválida']);
  exit;
}

try {
  // Pesquisar por nome - INCLUINDO data_ritual
  $stmt = $pdo->prepare("
        SELECT id, nome, foto, data_ritual
        FROM rituais
        WHERE nome LIKE ?
        ORDER BY data_ritual DESC
        LIMIT 20
    ");
  $stmt->execute(["%$pesquisa%"]);

  $ritual = $stmt->fetchAll(PDO::FETCH_ASSOC);

  if (empty($ritual)) {
    echo json_encode([]); // Retorna uma lista vazia se nenhum ritual for encontrado
    exit;
  }

  echo json_encode($ritual);
} catch (Exception $e) {
  echo json_encode(['error' => 'Erro ao buscar ritual: ' . $e->getMessage()]);
}
?>