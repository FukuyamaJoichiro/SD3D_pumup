<?php
// ファイル名: goal_detail.php
session_start();
require_once '../../db_connect.php'; // ★★★ DB接続ファイルへのパスを確認してください ★★★

$user_id = $_SESSION['user_id'] ?? null;
// HTMLフォームの name="customGoal" から入力された具体的な目標を取得
$goal_detail = $_POST['customGoal'] ?? null; 

if (!$user_id) {
    // ユーザーIDがない場合はエラー
    exit('エラー: ユーザー情報が見つかりません。最初からやり直してください。');
}

// 20文字以内のバリデーション (サーバーサイドチェック)
if (mb_strlen($goal_detail, 'UTF-8') > 20) {
    exit('エラー: 具体的な目標は20文字以内で入力してください。');
}

// DBの goal_detail カラムを新しい値で上書き更新するSQL
$sql = "UPDATE users SET goal_detail = :goal_detail WHERE user_id = :user_id";

try {
    $stmt = $pdo->prepare($sql);
    
    // プレースホルダに値をバインドし実行
    $stmt->execute([
        ':goal_detail' => $goal_detail, // 入力された具体的な目標を格納
        ':user_id' => $user_id           // 現在のユーザーIDを特定
    ]);
    
    // DB更新後、次の画面 training_count.html へリダイレクト
    // ★★★ HTMLファイルへのパスを適切に調整してください ★★★
    header('Location: ../html/training_count.html'); 
    exit();

} catch (PDOException $e) {
    // DBエラー処理
    error_log("DB Error: " . $e->getMessage()); 
    exit('エラー: 目標の詳細を登録中に問題が発生しました。');
}
?>