<?php
// パスを修正: 認証とDB接続ファイルの読み込み
require_once '../../auth.php';
require_login(); 

global $pdo; 

// 現在ログイン中のユーザーIDを取得
$logged_in_user_id = $_SESSION['user_id']; 

// POSTデータが送信されたかを確認
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 送信された体重と身長を取得
    $new_weight = isset($_POST['weight']) ? (float)$_POST['weight'] : null;
    $new_height = isset($_POST['height']) ? (float)$_POST['height'] : null;

    // バリデーション (値が存在し、有効な範囲であることを確認)
    if ($new_weight === null || $new_height === null || $new_weight <= 0 || $new_height <= 0) {
        header("Location: mybodydata_edit.php?error=invalid_data");
        exit;
    }

    try {
        // データベースを更新するSQL
        // ★ 修正: usersテーブルに存在しない updated_at カラムを削除しました。
        $stmt = $pdo->prepare("UPDATE users SET weight = :weight, height = :height WHERE user_id = :id");
        
        // パラメータのバインド
        // decimal(4,1)型に格納するため、float値を文字列としてバインドするのが安全です。
        $stmt->bindParam(':weight', $new_weight); 
        $stmt->bindParam(':height', $new_height);
        $stmt->bindParam(':id', $logged_in_user_id, PDO::PARAM_INT);
        
        // 実行
        $stmt->execute();

        // 成功した場合、次の画面にリダイレクト
        header("Location: bodydata.php");
        exit;

    } catch (Exception $e) {
        error_log("DB更新エラー: " . $e->getMessage());
        // エラー内容を外部ファイルに出力後、リダイレクト
        header("Location: mybodydata_edit.php?error=db_update_failed");
        exit;
    }
} else {
    // POSTリクエスト以外でアクセスされた場合、メイン画面にリダイレクト
    header("Location: mybodydata_edit.php");
    exit;
}