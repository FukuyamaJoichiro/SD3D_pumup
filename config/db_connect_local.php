<?php
// ローカル（XAMPP）用

$host = 'localhost';
$dbname = 'gorifit';
$user = 'root';
$password = ''; // XAMPP デフォルト

$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    exit("【LOCAL】DB接続エラー: " . $e->getMessage());
}
