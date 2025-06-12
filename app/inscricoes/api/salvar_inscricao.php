<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../config/database.php';

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inscricao_id = $_POST['id'];
    $primeira_vez_instituto = $_POST['primeira_vez_instituto'];
    $primeira_vez_ayahuasca = $_POST['primeira_vez_ayahuasca'];
    $doenca_psiquiatrica = $_POST['doenca_psiquiatrica'];
    $nome_doenca = $_POST['nome_doenca'] ?? '';
    $uso_medicao = $_POST['uso_medicao'];
    $nome_medicao = $_POST['nome_medicao'] ?? '';
    $mensagem = $_POST['mensagem'] ?? '';

    try {
        // Atualiza os detalhes da inscrição e registra a data/hora de salvamento
        $stmt = $pdo->prepare("
            UPDATE inscricoes
            SET
                primeira_vez_instituto = ?,
                primeira_vez_ayahuasca = ?,
                doenca_psiquiatrica = ?,
                nome_doenca = ?,
                uso_medicao = ?,
                nome_medicao = ?,
                mensagem = ?,
                salvo_em = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $primeira_vez_instituto,
            $primeira_vez_ayahuasca,
            $doenca_psiquiatrica,
            $nome_doenca,
            $uso_medicao,
            $nome_medicao,
            $mensagem,
            $inscricao_id
        ]);

        echo json_encode(['success' => true]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Erro ao salvar detalhes da inscrição: ' . $e->getMessage()]);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método de requisição inválido.']);
    exit;
}
