<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}
require_once 'includes/db.php';

$nomePesquisa = $_GET['nome'] ?? null;

if (!$nomePesquisa) {
    echo json_encode(['error' => 'Nome inválido']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, nome_completo, foto 
        FROM participantes 
        WHERE nome_completo LIKE ?
        LIMIT 20
    ");
    $stmt->execute(["%$nomePesquisa%"]);
    $participantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($participantes);
} catch (Exception $e) {
    echo json_encode(['error' => 'Erro ao buscar participantes: ' . $e->getMessage()]);
}
