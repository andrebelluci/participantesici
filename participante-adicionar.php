<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'includes/db.php';

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ritual_id = $_POST['ritual_id'];
    $nome_participante = trim($_POST['nome']);

    if (empty($nome_participante)) {
        $_SESSION['error'] = "O campo de pesquisa não pode estar vazio.";
        header("Location: ritual-visualizar.php?id=$ritual_id");
        exit;
    }

    try {
        // Verifica se o participante existe
        $stmt = $pdo->prepare("SELECT id FROM participantes WHERE nome_completo LIKE ?");
        $stmt->execute(["%$nome_participante%"]);
        $participante = $stmt->fetch();

        if (!$participante) {
            $_SESSION['error'] = "Participante não encontrado.";
            header("Location: ritual-visualizar.php?id=$ritual_id");
            exit;
        }

        $participante_id = $participante['id'];

        // Verifica se o participante já está inscrito no ritual
        $stmt = $pdo->prepare("SELECT id FROM inscricoes WHERE ritual_id = ? AND participante_id = ?");
        $stmt->execute([$ritual_id, $participante_id]);
        $inscricao_existente = $stmt->fetch();

        if ($inscricao_existente) {
            $_SESSION['error'] = "Este participante já está inscrito no ritual.";
            header("Location: ritual-visualizar.php?id=$ritual_id");
            exit;
        }

        // Insere o participante no ritual
        $stmt = $pdo->prepare("INSERT INTO inscricoes (ritual_id, participante_id, presente) VALUES (?, ?, ?)");
        $stmt->execute([$ritual_id, $participante_id, 'Não']);

        $_SESSION['success'] = "Participante adicionado com sucesso!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Erro ao adicionar participante: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "Método de requisição inválido.";
}

header("Location: ritual-visualizar.php?id=$ritual_id");
exit;
?>