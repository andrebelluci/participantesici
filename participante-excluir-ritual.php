<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'includes/db.php';

// Verifica se o ID do participante foi fornecido
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $participante_id = $_GET['id'];

    try {
        // Encontra o ritual associado ao participante
        $stmt = $pdo->prepare("SELECT ritual_id FROM inscricoes WHERE participante_id = ?");
        $stmt->execute([$participante_id]);
        $inscricao = $stmt->fetch();

        if (!$inscricao) {
            $_SESSION['error'] = "Participante não encontrado ou já foi removido.";
            header("Location: rituais");
            exit;
        }

        $ritual_id = $inscricao['ritual_id'];

        // Remove o participante da tabela de inscrições
        $stmt = $pdo->prepare("DELETE FROM inscricoes WHERE participante_id = ?");
        $stmt->execute([$participante_id]);

        $_SESSION['success'] = "Participante removido com sucesso!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Erro ao remover participante: " . $e->getMessage();
    }

    // Redireciona de volta para a página do ritual
    header("Location: ritual-visualizar?id=$ritual_id");
    exit;
} else {
    $_SESSION['error'] = "ID do participante inválido.";
    header("Location: rituais");
    exit;
}
?>