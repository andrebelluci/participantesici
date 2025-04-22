<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'includes/db.php';

// Verifica se o ID do ritual foi passado via GET
$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID do ritual não especificado.");
}

try {
    // Inicia uma transação para garantir que todas as operações sejam concluídas com sucesso
    $pdo->beginTransaction();

    // Exclui as inscrições associadas ao ritual
    $stmt_delete_inscricoes = $pdo->prepare("DELETE FROM inscricoes WHERE ritual_id = ?");
    $stmt_delete_inscricoes->execute([$id]);

    // Exclui o ritual
    $stmt_delete_ritual = $pdo->prepare("DELETE FROM rituais WHERE id = ?");
    $stmt_delete_ritual->execute([$id]);

    // Confirma a transação
    $pdo->commit();

    // Redireciona de volta para a página de rituais com uma mensagem de sucesso
    echo "<script>alert('Ritual e participantes associados excluídos com sucesso!');</script>";
    echo "<script>window.location.href = 'rituais.php';</script>";
} catch (Exception $e) {
    // Reverte a transação em caso de erro
    $pdo->rollBack();
    die("Erro ao excluir o ritual: " . $e->getMessage());
}
