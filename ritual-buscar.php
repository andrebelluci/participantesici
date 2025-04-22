<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}
require_once 'includes/db.php';

$pesquisa = $_GET['nome'] ?? null;

if (!$pesquisa) {
    echo json_encode(['error' => 'Pesquisa inválida']);
    exit;
}

try {    
    // Pesquisar por nome
    $stmt = $pdo->prepare("
        SELECT id, nome, foto
        FROM rituais 
        WHERE nome LIKE ?
        LIMIT 20
    ");
    $stmt->execute(["%$pesquisa%"]);

    $ritual = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($ritual)) {
        echo json_encode([]); // Retorna uma lista vazia se nenhum participante for encontrado
        exit;
    }

    echo json_encode($ritual);
} catch (Exception $e) {
    echo json_encode(['error' => 'Erro ao buscar ritual: ' . $e->getMessage()]);
}
