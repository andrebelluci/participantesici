<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'includes/db.php';

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $participante_id = $_POST['participante_id'];
    $observacao = trim($_POST['observacao']);

    if (empty($observacao)) {
        $_SESSION['error'] = "A observação não pode estar vazia.";
        header("Location: ritual-visualizar.php");
        exit;
    }

    try {
        // Atualiza a observação do participante
        $stmt = $pdo->prepare("UPDATE participantes SET observacao = ? WHERE id = ?");
        $stmt->execute([$observacao, $participante_id]);

        $_SESSION['success'] = "Observação salva com sucesso!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Erro ao salvar observação: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "Método de requisição inválido.";
}

// Redireciona de volta para a página do ritual
$stmt = $pdo->prepare("SELECT i.ritual_id FROM inscricoes i WHERE i.participante_id = ?");
$stmt->execute([$participante_id]);
$ritual = $stmt->fetch();
$ritual_id = $ritual['ritual_id'];

header("Location: ritual-visualizar.php?id=$ritual_id");
exit;
?>