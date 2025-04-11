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
    $observacao = trim($_POST['observacao']);

    if (empty($observacao)) {
        $_SESSION['error'] = "A observação não pode estar vazia.";
        header("Location: ritual-visualizar.php");
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

        $_SESSION['success'] = "Observação salva com sucesso!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Erro ao salvar observação: " . $e->getMessage();
    }

    // Encontra o ID do ritual associado à inscrição
    $stmt = $pdo->prepare("SELECT ritual_id FROM inscricoes WHERE id = ?");
    $stmt->execute([$inscricao_id]);
    $ritual = $stmt->fetch();
    $ritual_id = $ritual['ritual_id'];

    // Redireciona de volta para a página do ritual
    header("Location: ritual-visualizar.php?id=$ritual_id");
    exit;
} else {
    $_SESSION['error'] = "Método de requisição inválido.";
    header("Location: rituais.php");
    exit;
}
