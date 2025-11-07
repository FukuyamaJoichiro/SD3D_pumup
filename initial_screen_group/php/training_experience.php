<?php
// セッションを開始
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 変更点: $_POST['level'] ではなく $_POST['experience_level'] をチェックする
    if (isset($_POST['experience_level'])) {
        $selected_level = $_POST['experience_level'];
        
        // セッション変数に保持（キー名はお好みで。ここでは training_level を維持）
        $_SESSION['training_level'] = $selected_level; 
        
        // 次の画面（goal_setting.html）にリダイレクト
        header('Location: ../html/goal_setting.html'); 
        exit;
        
    } else {
        // エラーメッセージの変更（デバッグ用）
        echo "エラー: トレーニングレベルが送信されていません。";
    }
} else {
    // POST以外のリクエスト時の処理
    // ...
}
?>