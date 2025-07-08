<?php
require_once __DIR__ . '/config.php';

$host = env('DB_HOST', 'localhost');
$port = env('DB_PORT', 3306);
$dbname = env('DB_DATABASE');
$username = env('DB_USERNAME');
$password = env('DB_PASSWORD');

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro ao conectar ao banco de dados.");
}
