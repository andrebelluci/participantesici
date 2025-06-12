<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../config/database.php';

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inscricao_id = $_POST['inscricao_id'];
    $observacao = trim($_POST['observacao']);

    if (empty($observacao)) {
        echo json_encode(['success' => false, 'error' => 'A observação não pode estar vazia.']);
        exit;
    }

    try {
        // Atualiza o campo observacao na tabela inscricoes
        $stmt = $pdo->prepare("
            UPDATE inscricoes
            SET observacao = ? ,
            obs_salvo_em = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$observacao, $inscricao_id]);

        echo json_encode(['success' => true]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Erro ao salvar observação: ' . $e->getMessage()]);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método de requisição inválido.']);
    exit;
}
