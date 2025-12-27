<?php
// データベース接続ファイルを読み込み
require_once('../../db_connect.php');

// セッション開始
session_start();

// ユーザーIDを取得
$user_id = $_SESSION['user_id'] ?? 1;

try {
    // 1) 今日の日付
    $today_date = date("Y-m-d");
    $current_session_id = null;

    // 2) 今日のセッションID取得/作成
    $stmt_session = $pdo->prepare("SELECT session_id FROM workout_sessions WHERE user_id = ? AND date = ?");
    $stmt_session->execute([$user_id, $today_date]);
    $session_data = $stmt_session->fetch(PDO::FETCH_ASSOC);

    if ($session_data) {
        $current_session_id = (int)$session_data['session_id'];
    } else {
        $stmt_insert_session = $pdo->prepare("INSERT INTO workout_sessions (user_id, date) VALUES (?, ?)");
        $stmt_insert_session->execute([$user_id, $today_date]);
        $current_session_id = (int)$pdo->lastInsertId();
    }

    // 3) POSTでトレーニング追加が来た場合、workout_setsへ登録
    if ($current_session_id && isset($_POST['training']) && is_array($_POST['training'])) {
        $new_training_ids = $_POST['training'];

        $stmt_max_order = $pdo->prepare("SELECT IFNULL(MAX(order_on), 0) FROM workout_sets WHERE session_id = ?");
        $stmt_max_order->execute([$current_session_id]);
        $max_order = (int)$stmt_max_order->fetchColumn();
        $current_order = $max_order;

        $pdo->beginTransaction();
        try {
            // superset_id は NULL で追加（後で組み合わせる）
            $stmt_insert = $pdo->prepare("
                INSERT INTO workout_sets
                (session_id, training_id, user_id, order_on, weight, reps, duration, set_memo, superset_id)
                VALUES (?, ?, ?, ?, 0, 0, 0, NULL, NULL)
            ");

            foreach ($new_training_ids as $training_id) {
                $training_id = (int)$training_id;

                // 既に同じ training_id があれば追加しない
                $stmt_check = $pdo->prepare("SELECT 1 FROM workout_sets WHERE session_id = ? AND training_id = ? LIMIT 1");
                $stmt_check->execute([$current_session_id, $training_id]);

                if ($stmt_check->rowCount() == 0) {
                    $current_order++;
                    $stmt_insert->execute([$current_session_id, $training_id, $user_id, $current_order]);
                }
            }

            $pdo->commit();
        } catch (\PDOException $e) {
            $pdo->rollBack();
            echo "<h1>データ登録エラーが発生しました:</h1>";
            echo "<p>" . $e->getMessage() . "</p>";
            exit;
        }

        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // 4) 今日登録されている training_id を DB から取得（順番維持）
    $selected_training_ids = [];
    if ($current_session_id) {
        // ✅ DISTINCT + ORDER BY MIN() はエラーになるので GROUP BY にする
        $stmt_select_menu = $pdo->prepare("
            SELECT training_id
            FROM workout_sets
            WHERE session_id = ?
            GROUP BY training_id
            ORDER BY MIN(order_on) ASC
        ");
        $stmt_select_menu->execute([$current_session_id]);
        $selected_training_ids = $stmt_select_menu->fetchAll(PDO::FETCH_COLUMN);
    }

    // ✅ DBとセッションを同期（double_select.php / save_pending_superset.php 用）
    $_SESSION['workout_trainings'] = array_map('intval', $selected_training_ids);

} catch(PDOException $e) {
    echo "<h1>全体データ処理エラーが発生しました:</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
    exit;
}

// 現在の日付情報
$current_date  = new DateTime();
$current_month = $current_date->format('n');
$current_day   = $current_date->format('j');
$current_year  = $current_date->format('Y');

// 週の日付（日曜〜土曜）
$week_dates  = [];
$day_of_week = (int)$current_date->format('w'); // 0(日)〜6(土)

for ($i = 0; $i < 7; $i++) {
    $date = clone $current_date;
    $date->modify('-' . $day_of_week . ' days');
    $date->modify('+' . $i . ' days');

    $week_dates[] = [
        'day_label' => ['日','月','火','水','木','金','土'][$i],
        'day_num'   => $date->format('j'),
        'is_today'  => ($i === $day_of_week),
    ];
}

// 選択されたトレーニング情報を取得（superset_idも取得）
$trainings = [];
if (!empty($selected_training_ids)) {
    $placeholders = implode(',', array_fill(0, count($selected_training_ids), '?'));

    // ✅ ここだけ修正：FIELDを使わず、MIN(order_on)で並びを安定
    $stmt = $pdo->prepare("
        SELECT 
            t.training_id,
            t.training_name,
            GROUP_CONCAT(DISTINCT tt.type_id) AS type_ids,
            MAX(ws.superset_id) AS superset_id,
            MIN(ws.order_on) AS first_order
        FROM trainings t
        LEFT JOIN training_types tt ON t.training_id = tt.training_id
        LEFT JOIN workout_sets ws
               ON ws.training_id = t.training_id
              AND ws.session_id = ?
        WHERE t.training_id IN ($placeholders)
        GROUP BY t.training_id
        ORDER BY first_order ASC
    ");

    $params = array_merge([$current_session_id], $selected_training_ids);
    $stmt->execute($params);
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
                <?php
                    // ✅ ここだけ修正：superset判定を厳密に（0/NULL/文字列混在対策）
                    $currSup = !empty($training['superset_id']) ? (int)$training['superset_id'] : null;
                    $prevSup = isset($trainings[$index - 1]) && !empty($trainings[$index - 1]['superset_id'])
                        ? (int)$trainings[$index - 1]['superset_id'] : null;
                    $nextSup = isset($trainings[$index + 1]) && !empty($trainings[$index + 1]['superset_id'])
                        ? (int)$trainings[$index + 1]['superset_id'] : null;

                    $isGroup  = ($currSup !== null);
                    $isTop    = $isGroup && ($prevSup !== $currSup);
                    $isBottom = $isGroup && ($nextSup !== $currSup);

                    $cardClasses = 'training-card';
                    if ($isTop)    $cardClasses .= ' superset-top';
                    if ($isBottom) $cardClasses .= ' superset-bottom';
                ?>

                <div class="<?php echo $cardClasses; ?>"
                     data-training-id="<?php echo (int)$training['training_id']; ?>"
                     data-type-ids="<?php echo htmlspecialchars($training['type_ids'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

                    <div class="training-header">
                        <span class="training-number"><?php echo $index + 1; ?>種</span>
                        <span class="training-name"><?php echo htmlspecialchars($training['training_name']); ?></span>
                        <button class="info-btn" data-training-id="<?php echo (int)$training['training_id']; ?>">ⓘ</button>
                        <button class="menu-btn">⋮</button>
                    </div>

                    <!-- セットはJSで追加 -->
                    <div class="sets-container"></div>

                    <div class="set-actions">
                        <button class="delete-set-btn">－セット削除</button>
                        <button class="add-set-btn">＋セット追加</button>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- トレーニング追加 -->
            <button class="add-training-btn" onclick="location.href='training_list.php'">
                トレーニング追加
            </button>

            <!-- 全てリセット -->
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

    <!-- フッター -->
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

<!-- 詳細モーダル -->
<div id="detail-modal-overlay" class="modal-overlay" style="z-index: 2000; display: none;">
    <div id="detail-modal-content" class="modal-content detail-modal-box"></div>
</div>

<script>
    // JSで使う
    const CURRENT_SESSION_ID = <?php echo json_encode($current_session_id ?? null); ?>;
</script>
<script src="training_select.js"></script>
<script src="training_detail_modal.js"></script>

</body>
</html>
