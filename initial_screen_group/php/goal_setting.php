<?php
session_start();
require_once '../../db_connect.php';

$user_id = $_SESSION['user_id'] ?? null;
$goal_level = $_POST['selected_goal'] ?? null;
if (!$user_id) {
    exit('ユーザー情報が見つかりません。最初からやり直してください。');
}

if (empty($goal_level)) {
    exit('目標が選択されていません。');
}
$sql = "UPDATE users SET goal = :goal_level WHERE user_id = :user_id";
try {
    $stmt = $pdo->prepare($sql);
    
    $stmt->execute([
        ':goal_level' => $goal_level, // goalカラムにレベルを格納
        ':user_id' => $user_id         // どのユーザーを更新するか指定
    ]);
    header('Location: fetch_goal_data.php');
    exit();

} catch (PDOException $e) {
    exit('目標の登録中にエラーが発生しました: ' . $e->getMessage());
}
?>