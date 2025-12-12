<?php
// データベース接続ファイルを読み込み
require_once('../../db_connect.php');

// セッション開始
session_start();

// ユーザーIDを取得
$user_id = $_SESSION['user_id'] ?? 1;

// セッションに保存されたトレーニングリストを初期化
if (!isset($_SESSION['workout_trainings'])) {
    $_SESSION['workout_trainings'] = [];
}

// POSTで新しいトレーニングが送信された場合
if (isset($_POST['training']) && is_array($_POST['training'])) {
    foreach ($_POST['training'] as $training_id) {
        $training_id = (int)$training_id; // 整数に変換
        // 重複チェック：まだリストにない場合のみ追加
        if (!in_array($training_id, $_SESSION['workout_trainings'])) {
            $_SESSION['workout_trainings'][] = $training_id;
        }
    }
}

$selected_training_ids = $_SESSION['workout_trainings'];

// 現在の日付情報を取得
$current_date = new DateTime();
$current_month = $current_date->format('n'); // 月（1-12）
$current_day = $current_date->format('j'); // 日（1-31）
$current_year = $current_date->format('Y'); // 年

// 週の日付を計算（日曜日から土曜日まで）
$week_dates = [];
$day_of_week = $current_date->format('w'); // 0(日曜)〜6(土曜)

for ($i = 0; $i < 7; $i++) {
    $date = clone $current_date;
    $date->modify('-' . $day_of_week . ' days');
    $date->modify('+' . $i . ' days');
    $week_dates[] = [
        'day_label' => ['日', '月', '火', '水', '木', '金', '土'][$i],
        'day_num' => $date->format('j'),
        'is_today' => ($i == $day_of_week)
    ];
}

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
                <span class="month"><?php echo $current_month; ?>月</span>
            </div>
        </div>
        
        <!-- 週間カレンダー -->
        <div class="week-calendar">
            <?php foreach ($week_dates as $date): ?>
                <div class="day-item<?php echo $date['is_today'] ? ' active' : ''; ?>">
                    <span class="day-label"><?php echo $date['day_label']; ?></span>
                    <span class="day-num"><?php echo $date['day_num']; ?></span>
                </div>
            <?php endforeach; ?>
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
                            <button class="info-btn" data-training-id="<?php echo $training['training_id']; ?>">ⓘ</button>
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
                
                <!-- セッションクリアボタン -->
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
    
    <!-- トレーニングメニューモーダル -->
    <div class="training-menu-overlay" id="training-menu-overlay">
        <div class="training-menu-content">
            <div class="menu-header">
                <span class="menu-training-name" id="menu-training-name">種</span>
                <button class="menu-close-btn" id="menu-close-btn">✕</button>
            </div>
            <div class="menu-items">
                <button class="menu-item" id="menu-exchange">
                    <span class="menu-item-icon">⇄</span>
                    <span class="menu-item-text">トレーニング交換</span>
                    <span class="menu-item-arrow">▷</span>
                </button>
                <button class="menu-item" id="menu-superset">
                    <span class="menu-item-icon">🔗</span>
                    <span class="menu-item-text">スーパーセット</span>
                    <span class="menu-item-arrow">▷</span>
                </button>
                <button class="menu-item delete" id="menu-delete">
                    <span class="menu-item-text">削除</span>
                </button>
            </div>
        </div>
    </div>
    
    <script src="training_select.js"></script>

    <!-- ▼▼▼ ここから追加：トレーニング詳細モーダル（ⓘ 用） ▼▼▼ -->
<div id="detail-modal-overlay" class="modal-overlay" style="z-index: 2000; display: none;">
    <div id="detail-modal-content" class="modal-content detail-modal-box">
        <!-- training_detail_modal.php の内容が JS によってここへ挿入される -->
    </div>
</div>
<!-- ▲▲▲ 追加はここまで ▲▲▲ -->

</html>
 <script src="training_detail_modal.js"></script>

</body>
</html>