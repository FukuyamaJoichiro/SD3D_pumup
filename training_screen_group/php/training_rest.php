<?php
// PHPエラー表示設定 (開発時のみ有効にすることを推奨)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ==========================================================
// 1. 必要なファイルの読み込みとセッション処理
// ==========================================================
// auth.phpを読み込むことで、DB接続($pdo)とセッション開始(session_start())、
// そしてrequire_login()関数が利用可能になる。
// パスは「../../auth.php」で正しく動作していることを前提とする。
require_once '../../auth.php';       
require_once '../../db_connect.php'; 

// 🚨 ログインチェックを行い、未ログインならリダイレクト
// require_login()関数が auth.php で定義されているため、これを利用する
require_login(); 

// ログインチェックを通過した場合、ユーザーIDをセッションから取得
$user_id = $_SESSION['user_id']; 

// ----------------------------------------------------------
// 2. 選択日付の取得
// ----------------------------------------------------------
// URLパラメータから日付を取得。ない場合は今日の日付をデフォルトとする
$selected_date = filter_input(INPUT_GET, 'date', FILTER_DEFAULT);
if (!$selected_date) {
    $selected_date = date('Y-m-d');
}

// ----------------------------------------------------------
// 3. DBへの「おやすみ」登録処理 (UPSERT)
// ----------------------------------------------------------
try {
    // calendar_activity に 'REST' で登録/上書き
    $stmt = $pdo->prepare("
        INSERT INTO calendar_activity (user_id, activity_date, session_type, part_id)
        VALUES (:uid, :date, 'REST', NULL)
        ON DUPLICATE KEY UPDATE 
            session_type = VALUES(session_type),
            part_id = VALUES(part_id)
    ");
    
    $stmt->execute([
        ':uid' => $user_id,
        ':date' => $selected_date 
    ]);

} catch (PDOException $e) {
    // 開発完了後、本番環境では die() を削除し、ユーザーフレンドリーなエラー画面に遷移させます。
    die("DB登録中にエラーが発生しました: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>トレーニング記録</title>
    <link rel="stylesheet" href="training_rest.css">
</head>
<body>

    <div class="app-container">
        
        <header class="calendar-header">
            <div class="header-top">
                <a href="calendar.php" class="back-button">&lt;</a>
                <h1 class="month-title">9月</h1>
            </div>
            
            <div class="week-days">
                <div class="day-item day-sun">日<span class="date">9</span></div>
                <div class="day-item">月<span class="date">10</span></div>
                <div class="day-item day-fire active">火<span class="date">11</span></div>
                <div class="day-item">水<span class="date">12</span></div>
                <div class="day-item">木<span class="date">13</span></div>
                <div class="day-item">金<span class="date">14</span></div>
                <div class="day-item day-sat">土<span class="date">15</span></div>
            </div>
            
            <nav class="tab-menu">
                <a href="#" class="tab-item active">トレーニング記録</a>
                <a href="#" class="tab-item">ボディデータ</a>
                <div class="tab-indicator"></div>
            </nav>
        </header>

        <main class="content-area">
            <div class="rest-container">
                <div class="rest-illustration">
                    <img src="../tr_img/おやすみ画面.png" alt="トレーニングおやすみイラスト">
                </div>
                
                <p class="rest-message">トレーニングおやすみ U_U</p>
                <a href="training_record.php" class="cancel-link">キャンセル</a>
            </div>
        </main>
    </div>

    <script src="training_rest.js"></script>
</body>
</html>