<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'includes/db.php';

$ritual_id = $_GET['ritual_id'];
$participante_id = $_GET['participante_id'];

// Verificar se o participante já está vinculado ao ritual
$stmt_check = $pdo->prepare("SELECT * FROM inscricoes WHERE ritual_id = ? AND participante_id = ?");
$stmt_check->execute([$ritual_id, $participante_id]);
if ($stmt_check->rowCount() > 0) {
    echo "<script>alert('Este participante já está vinculado ao ritual.');</script>";
    echo "<script>window.location.href = 'ritual-visualizar.php?id=$ritual_id';</script>";
    exit;
}

// Vincular o participante ao ritual
$stmt_insert = $pdo->prepare("INSERT INTO inscricoes (ritual_id, participante_id, presente) VALUES (?, ?, 'Não')");
$stmt_insert->execute([$ritual_id, $participante_id]);

echo "<script>alert('Participante vinculado com sucesso!');</script>";
echo "<script>window.location.href = 'ritual-visualizar.php?id=$ritual_id';</script>";
?>