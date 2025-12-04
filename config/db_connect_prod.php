<?php
// 本番環境（ロリポップ）用

$host = 'mysql323.phy.lolipop.lan';
$dbname = 'LAA1684541-gorifit';
$user = 'LAA1684541';
$password = 'pumpup';

$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    exit("【PROD】DB接続エラー: " . $e->getMessage());
}
