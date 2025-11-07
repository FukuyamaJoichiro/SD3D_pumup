<?php
session_start();
require_once __DIR__ . '/../../auth.php';
require_login('../../initial_screen_group/php/login.php');
require_once __DIR__ . '/../../db_connect.php'; // ← DB接続ファイル

// ✅ ログイン中のユーザーIDを取得
$user_id = $_SESSION['user_id'] ?? null;

// ✅ 念のためチェック（不正アクセス対策）
if (!$user_id) {
    header("Location: ../../initial_screen_group/php/login.php");
    exit;
}

// ✅ DBからユーザー情報を取得
try {
    $stmt = $pdo->prepare("
        SELECT user_id, user_name, gender, weight, height, birth
        FROM users
        WHERE user_id = :id
        LIMIT 1
    ");
    $stmt->bindValue(":id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // ✅ もしデータがなければデフォルト値
    if (!$user) {
        $user = [
            'user_name' => '',
            'gender'    => '',
            'weight'    => '',
            'height'    => '',
            'user_id'   => $user_id,
        ];
    }

} catch (PDOException $e) {
    die("DBエラー: " . $e->getMessage());
}
?>
