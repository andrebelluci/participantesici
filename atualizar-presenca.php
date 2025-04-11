<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit;
}
require_once 'includes/db.php';

// Recebe os dados via POST
$data = json_decode(file_get_contents('php://input'), true);

// Log dos dados recebidos (para depuração)
error_log("Dados recebidos: " . print_r($data, true));

$inscricao_id = $data['inscricao_id'] ?? null;
$novo_status = $data['novo_status'] ?? null;

if (!$inscricao_id || !$novo_status || !in_array($novo_status, ['Sim', 'Não'])) {
    error_log("Parâmetros inválidos: inscricao_id=$inscricao_id, novo_status=$novo_status");
    echo json_encode(['success' => false, 'error' => 'Parâmetros inválidos']);
    exit;
}

try {
    // Prepara a query de atualização
    $stmt = $pdo->prepare("
        UPDATE inscricoes 
        SET presente = ? 
        WHERE id = ?
    ");

    // Log da query preparada (para depuração)
    error_log("Executando query: UPDATE inscricoes SET presente = '$novo_status' WHERE id = $inscricao_id");

    // Executa a query
    $stmt->execute([$novo_status, $inscricao_id]);

    // Log da execução (para depuração)
    error_log("Query executada com sucesso. Linhas afetadas: " . $stmt->rowCount());

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log("Erro ao executar query: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro ao atualizar presença: ' . $e->getMessage()]);
}
