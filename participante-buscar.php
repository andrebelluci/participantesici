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
    // Verifica se a pesquisa é um CPF (11 dígitos numéricos)
    $pesquisaLimpa = preg_replace('/[^0-9]/', '', $pesquisa); // Remove caracteres não numéricos
    if (strlen($pesquisaLimpa) === 11) {
        // Pesquisar por CPF
        $stmt = $pdo->prepare("
            SELECT id, nome_completo, foto 
            FROM participantes 
            WHERE cpf = ?
            LIMIT 20
        ");
        $stmt->execute([$pesquisaLimpa]);
    } else {
        // Pesquisar por nome
        $stmt = $pdo->prepare("
            SELECT id, nome_completo, foto 
            FROM participantes 
            WHERE nome_completo LIKE ?
            LIMIT 20
        ");
        $stmt->execute(["%$pesquisa%"]);
    }

    $participantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($participantes)) {
        echo json_encode([]); // Retorna uma lista vazia se nenhum participante for encontrado
        exit;
    }

    echo json_encode($participantes);
} catch (Exception $e) {
    echo json_encode(['error' => 'Erro ao buscar participantes: ' . $e->getMessage()]);
}
