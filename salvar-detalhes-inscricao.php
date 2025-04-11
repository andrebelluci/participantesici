<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'includes/db.php';

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inscricao_id = $_POST['inscricao_id'];
    $primeira_vez_instituto = $_POST['primeira_vez_instituto'];
    $primeira_vez_ayahuasca = $_POST['primeira_vez_ayahuasca'];
    $doenca_psiquiatrica = $_POST['doenca_psiquiatrica'];
    $nome_doenca = $_POST['nome_doenca'] ?? '';
    $uso_medicao = $_POST['uso_medicao'];
    $nome_medicao = $_POST['nome_medicao'] ?? '';
    $mensagem = $_POST['mensagem'] ?? '';

    try {
        // Atualiza os detalhes da inscrição
        $stmt = $pdo->prepare("
            UPDATE inscricoes 
            SET 
                primeira_vez_instituto = ?,
                primeira_vez_ayahuasca = ?,
                doenca_psiquiatrica = ?,
                nome_doenca = ?,
                uso_medicao = ?,
                nome_medicao = ?,
                mensagem = ?
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

        $_SESSION['success'] = "Detalhes da inscrição salvos com sucesso!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Erro ao salvar detalhes da inscrição: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "Método de requisição inválido.";
}

// Redireciona de volta para a página do ritual
$stmt = $pdo->prepare("SELECT ritual_id FROM inscricoes WHERE id = ?");
$stmt->execute([$inscricao_id]);
$ritual = $stmt->fetch();
$ritual_id = $ritual['ritual_id'];

header("Location: ritual-visualizar.php?id=$ritual_id");
exit;
?>