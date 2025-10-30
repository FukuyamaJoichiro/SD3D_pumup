<?php
session_start(); // セッション開始
require_once '../../db_connect.php'; 

$user_id = $_SESSION['user_id'] ?? null; 
$experience_level = $_POST['experience_level'] ?? null; // HTML側で name="experience_level" の隠しフィールドが必要です

if (!$user_id) {
    exit('ユーザー情報が見つかりません。');
}

if (empty($experience_level)) {
    // 選択されていない場合のエラー処理
    exit('経験レベルが選択されていません。');
}

// usersテーブルの goal カラムを更新
// ここでは level を goal に格納すると仮定します
$sql = "UPDATE users SET goal = :level WHERE user_id = :user_id";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':level' => $experience_level, 
        ':user_id' => $user_id
    ]);
    
    // 成功したら次の画面へリダイレクト
    header('Location: ../html/goal_setting.html');
    exit();

} catch (PDOException $e) {
    exit('経験レベル登録中にエラーが発生しました: ' . $e->getMessage());
}
?>