<?php
if ($_SERVER['HTTP_HOST'] === 'localhost') {
    $host     = '127.0.0.1';
    $dbname   = 'ici-sistema';
    $username = 'root';
    $password = '';
} else {
    $host     = getenv('DB_HOST');
    $dbname   = getenv('DB_NAME');
    $username = getenv('DB_USER');
    $password = getenv('DB_PASS');
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro ao conectar ao banco de dados.");
}
