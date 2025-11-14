<?php
// ファイル名: fetch_goal_data.php (phpフォルダ内を想定)
session_start();
require_once '../../db_connect.php'; // DB接続ファイルへのパスはプロジェクト構造に合わせて調整してください

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    // ユーザーIDがない場合はエラー処理または最初へ戻す
    exit('ユーザー情報が見つかりません。最初からやり直してください。');
}

// ユーザーの goal (目標レベル) をDBから取得
$sql = "SELECT goal FROM users WHERE user_id = :user_id";
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result && isset($result['goal'])) {
        // 取得した goal の値 (1, 2, 3, 4) をセッションに保存
        $_SESSION['display_goal'] = $result['goal'];
    } else {
        // 目標が見つからなかった場合、デフォルト値を設定
        $_SESSION['display_goal'] = '1'; 
    }

} catch (PDOException $e) {
    // DBエラー時
    $_SESSION['display_goal'] = 'error'; 
}

// データをセッションに保存したら、HTMLファイルへリダイレクト
header('Location: goal_detail.php'); // HTMLファイルへのパスを調整してください
exit();
?>