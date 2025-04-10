<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ritual_id = $_POST['ritual_id'];
    $nome = $_POST['nome'];

    // Pesquisar participantes pelo nome
    $stmt = $pdo->prepare("SELECT * FROM participantes WHERE nome_completo LIKE ?");
    $stmt->execute(["%$nome%"]);
    $participantes = $stmt->fetchAll();

    if (empty($participantes)) {
        echo "<script>alert('Nenhum participante encontrado com esse nome.');</script>";
        echo "<script>window.location.href = 'ritual-visualizar.php?id=$ritual_id';</script>";
        exit;
    }

    // Exibir lista de participantes encontrados
    echo "<div style='padding: 20px;'>";
    echo "<h2>Participantes Encontrados</h2>";
    foreach ($participantes as $pessoa) {
        echo "<div>";
        echo "<strong>" . htmlspecialchars($pessoa['nome_completo']) . "</strong>";
        echo " - CPF: " . htmlspecialchars($pessoa['cpf']);
        echo " <a href='participante-vincular.php?ritual_id=$ritual_id&participante_id={$pessoa['id']}' class='btn'>Vincular</a>";
        echo "</div>";
    }
    echo "</div>";
}
?>