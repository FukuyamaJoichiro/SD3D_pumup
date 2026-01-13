<?php
require_once("../../db_connect.php");
session_start();

// ユーザーIDの取得
$user_id = $_SESSION['user_id'] ?? 1;

// クエリパラメータから日付を取得
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$today = date('Y-m-d');
$month = date('n', strtotime($selected_date));

// カレンダー用：指定週の日付計算
$timestamp = strtotime($selected_date);
$day_of_week = date('w', $timestamp); 
$start_of_week_timestamp = strtotime("-$day_of_week days", $timestamp); 

$dates_of_week = [];
for ($i = 0; $i < 7; $i++) {
    $current_timestamp = strtotime("+$i days", $start_of_week_timestamp);
    $dates_of_week[] = [
        'full_date' => date('Y-m-d', $current_timestamp),
        'day' => date('j', $current_timestamp),
        'weekday' => date('w', $current_timestamp)
    ];
}

// --- 【修正版】トレーニング詳細データの取得ロジック ---
$recorded_trainings = [];
try {
    // 1. 選択された日付のセッションIDを取得
    $stmt_sess = $pdo->prepare("SELECT session_id FROM workout_sessions WHERE user_id = ? AND date = ? LIMIT 1");
    $stmt_sess->execute([$user_id, $selected_date]);
    $session = $stmt_sess->fetch(PDO::FETCH_ASSOC);

    if ($session) {
        // 2. workout_sets と trainings (マスタ) を結合して取得
        // ※ training_masters ではなく trainings テーブルを使用
        $stmt_work = $pdo->prepare("
            SELECT 
                t.training_name, 
                ws.weight, 
                ws.reps, 
                ws.training_id,
                ws.order_on
            FROM workout_sets ws
            JOIN trainings t ON ws.training_id = t.training_id
            WHERE ws.session_id = ? AND ws.user_id = ?
            ORDER BY ws.order_on ASC, ws.set_id ASC
        ");
        $stmt_work->execute([$session['session_id'], $user_id]);
        $rows = $stmt_work->fetchAll(PDO::FETCH_ASSOC);

        // 種目ごとにまとめる
        foreach ($rows as $row) {
            $name = $row['training_name'];
            if (!isset($recorded_trainings[$name])) {
                $recorded_trainings[$name] = [
                    'training_id' => $row['training_id'],
                    'sets' => []
                ];
            }
            $recorded_trainings[$name]['sets'][] = $row;
        }
    }
} catch (Exception $e) {
    error_log("記録取得エラー: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>今日のトレーニング</title>
    <link rel="stylesheet" href="training_record_style.css">
</head>
<body>
    <div class="app-container">

        <header class="header">
            <div class="back-btn" onclick="location.href='calendar.php'">&#x2039;</div>
            <div class="month"><?= $month ?>月</div>
        </header>

        <div class="calendar-week">
            <table class="date-slider">
                <tr><th>日</th><th>月</th><th>火</th><th>水</th><th>木</th><th>金</th><th>土</th></tr>
                <tr>
                <?php foreach ($dates_of_week as $date_info): ?>
                <?php
                    $classes = [];
                    if ($date_info['full_date'] == $selected_date) $classes[] = 'selected';
                    if ($date_info['full_date'] == $today) $classes[] = 'today-mark';
                    if ($date_info['weekday'] == 0) $classes[] = 'weekday-sun';
                    if ($date_info['weekday'] == 6) $classes[] = 'weekday-sat';
                    $class_string = implode(' ', $classes);
                ?>
                <td class="<?= $class_string ?>" data-full-date="<?= $date_info['full_date'] ?>">
                    <?= $date_info['day'] ?>
                        </td>
                </td>
                <?php endforeach; ?>
                </tr>
            </table>
        </div>

        <div class="tab-menu">
            <div class="tab active">トレーニング記録</div>
            <div class="tab"><a href="bodydata.php?date=<?= $selected_date ?>">ボディデータ</a></div>
        </div>

        <div class="training-list-section">
            <?php if (empty($recorded_trainings)): ?>
                <section class="training-card empty-card">
                    <h3>今日のトレーニング</h3>
                    <p>トレーニングを計画してみましょう！</p>
                    <button class="training-btn" onclick="location.href='training_list.php?date=<?= $selected_date ?>'">トレーニング選択</button>
                </section>
            <?php else: ?>
                <?php $idx = 1; foreach ($recorded_trainings as $trainingName => $data): ?>
                    <section class="training-card">
                        <div class="training-header">
                            <span class="training-number"><?= $idx++ ?>種</span>
                            <span class="training-name"><?= htmlspecialchars($trainingName) ?></span>
                        </div>

                        <div class="sets-container">
                            <div class="set-header">
                                <span>セット</span><span>kg</span><span>回数</span>
                            </div>
                            <?php foreach ($data['sets'] as $sIdx => $setData): ?>
                                <div class="set-row">
                                    <span class="set-label"><?= $sIdx + 1 ?></span>
                                    <span class="set-value"><?= number_format($setData['weight'], 1) ?></span>
                                    <span class="set-value"><?= $setData['reps'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
                <div style="text-align: center; margin-bottom: 20px;">
                    <button class="training-btn" onclick="location.href='training_list.php'">さらに追加する</button>
                </div>
            <?php endif; ?>
        </div>

        <section class="status-section">
            <p class="rest" onclick="location.href='training_rest.php?date=<?= $selected_date ?>'">🛏 今日は休みます 🛏</p>
        </section>

        <nav class="app-nav">
            <a href="../../home_screen_group/php/home.php" class="nav-item"><span class="nav-item-icon">🏠</span> ホーム</a>
            <a href="calendar.php" class="nav-item active"><span class="nav-item-icon">💪</span> カレンダー</a>
            <a href="../../home_screen_group/php/mypage.php" class="nav-item"><span class="nav-item-icon">👤</span> マイページ</a>
        </nav>

    </div>
    <script src="training_record.js"></script>
</body>
</html>