<?php
// データベース接続ファイルを読み込み
require_once ('../../db_connect.php');

try {
    // セッションからユーザーIDを取得
    session_start();
    $user_id = $_SESSION['user_id'] ?? 1;
    
    // セッションに保存されたトレーニングリストを初期化
    if (!isset($_SESSION['workout_trainings'])) {
        $_SESSION['workout_trainings'] = [];
    }
    
    // POSTで新しいトレーニングが送信された場合
    if (isset($_POST['training']) && !empty($_POST['training'])) {
        foreach ($_POST['training'] as $training_id) {
            // 重複チェック：まだリストにない場合のみ追加
            if (!in_array($training_id, $_SESSION['workout_trainings'])) {
                $_SESSION['workout_trainings'][] = $training_id;
            }
        }
    }
    
    $selected_training_ids = $_SESSION['workout_trainings'];
    
    // 選択されたトレーニング情報を取得
    $trainings = [];
    if (!empty($selected_training_ids)) {
        $placeholders = implode(',', array_fill(0, count($selected_training_ids), '?'));
        $stmt = $pdo->prepare("
            SELECT 
                t.training_id, 
                t.training_name,
                GROUP_CONCAT(DISTINCT tt.type_id) as type_ids
            FROM trainings t
            LEFT JOIN training_types tt ON t.training_id = tt.training_id
            WHERE t.training_id IN ($placeholders)
            GROUP BY t.training_id
            ORDER BY FIELD(t.training_id, $placeholders)
        ");
        $stmt->execute(array_merge($selected_training_ids, $selected_training_ids));
        $trainings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch(PDOException $e) {
    echo "データ取得エラー: " . $e->getMessage();
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>トレーニング記録</title>
    <link rel="stylesheet" href="training_select.css">
</head>
<body>
    <div class="container">
        <!-- ヘッダー -->
        <div class="header">
            <button class="back-btn" onclick="history.back()">＜</button>
            <div class="date-selector">
                <span class="month">9月</span>
            </div>
        </div>
        
        <!-- 週間カレンダー -->
        <div class="week-calendar">
            <div class="day-item">
                <span class="day-label">日</span>
                <span class="day-num">7</span>
            </div>
            <div class="day-item">
                <span class="day-label">月</span>
                <span class="day-num">8</span>
            </div>
            <div class="day-item active">
                <span class="day-label">火</span>
                <span class="day-num">9</span>
            </div>
            <div class="day-item">
                <span class="day-label">水</span>
                <span class="day-num">10</span>
            </div>
            <div class="day-item">
                <span class="day-label">木</span>
                <span class="day-num">11</span>
            </div>
            <div class="day-item">
                <span class="day-label">金</span>
                <span class="day-num">12</span>
            </div>
            <div class="day-item">
                <span class="day-label">土</span>
                <span class="day-num">13</span>
            </div>
        </div>
        
        <!-- タブ -->
        <div class="tabs">
            <button class="tab active">トレーニング記録</button>
            <button class="tab">ボディデータ</button>
        </div>
        
        <!-- インターバルタイマー -->
        <div class="interval-timer">
            <span>⏱ Interval Timer ⏱</span>
        </div>
        
        <!-- トレーニングリスト -->
        <div class="training-list">
            <?php if (empty($trainings)): ?>
                <div class="empty-message">
                    <p>トレーニングが選択されていません</p>
                    <button onclick="location.href='training_list.php'" class="select-btn">トレーニングを選択</button>
                </div>
            <?php else: ?>
                <?php foreach ($trainings as $index => $training): ?>
                    <div class="training-card" data-training-id="<?php echo $training['training_id']; ?>" data-type-ids="<?php echo $training['type_ids']; ?>">
                        <div class="training-header">
                            <span class="training-number"><?php echo $index + 1; ?>種</span>
                            <span class="training-name"><?php echo htmlspecialchars($training['training_name']); ?></span>
                            <button class="info-btn">ⓘ</button>
                            <button class="menu-btn">⋮</button>
                        </div>
                        
                        <!-- セットリスト -->
                        <div class="sets-container">
                            <!-- セットはJavaScriptで動的に追加 -->
                        </div>
                        
                        <!-- セットボタン -->
                        <div class="set-actions">
                            <button class="delete-set-btn">－セット削除</button>
                            <button class="add-set-btn">＋セット追加</button>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- トレーニング追加ボタン -->
                <button class="add-training-btn" onclick="location.href='training_list.php'">
                    トレーニング追加
                </button>
                
                <!-- セッションクリアボタン（デバッグ用） -->
                <form method="post" action="clear_session.php" style="margin-top: 8px;">
                    <button type="submit" class="clear-session-btn">全てリセット</button>
                </form>
                
                <!-- メモ -->
                <div class="memo-section">
                    <label>メモ</label>
                    <textarea class="memo-input" placeholder="メモを入力..."></textarea>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- フッターボタン -->
        <?php if (!empty($trainings)): ?>
        <div class="footer-actions">
            <div class="timer-section">
                <div class="timer-item">
                    <span class="timer-label">休憩タイマー</span>
                    <span class="timer-value" id="rest-timer">0:00</span>
                </div>
                <div class="timer-item">
                    <span class="timer-label">通算時間</span>
                    <span class="timer-value" id="total-timer">00:00</span>
                </div>
            </div>
            <button class="start-btn">トレーニングスタート</button>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="training_select.js"></script>
</body>
</html>