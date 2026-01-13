<?php
require_once '../../auth.php'; 
require_once '../../db_connect.php'; 
require_login(); 

$user_id = $_SESSION['user_id']; 

// 1. 選択日付の取得
$selected_date = filter_input(INPUT_GET, 'date', FILTER_DEFAULT);
if (!$selected_date) {
    $selected_date = date('Y-m-d');
}

// 2. DBへの「おやすみ」登録処理 (UPSERT)
try {
    $stmt = $pdo->prepare("
        INSERT INTO calendar_activity (user_id, activity_date, session_type, part_id)
        VALUES (:uid, :date, 'REST', NULL)
        ON DUPLICATE KEY UPDATE 
            session_type = VALUES(session_type),
            part_id = VALUES(part_id)
    ");
    $stmt->execute([':uid' => $user_id, ':date' => $selected_date]);
} catch (PDOException $e) {
    die("DB登録中にエラーが発生しました: " . $e->getMessage());
}

// 3. カレンダー表示用の日付計算（1週間分）
$current_date  = new DateTime($selected_date);
$display_month = $current_date->format('n');
$day_of_week   = (int)$current_date->format('w'); 

$week_dates = [];
for ($i = 0; $i < 7; $i++) {
    $date = clone $current_date;
    $date->modify('-' . $day_of_week . ' days');
    $date->modify('+' . $i . ' days');
    $week_dates[] = [
        'day_label' => ['日','月','火','水','木','金','土'][$i],
        'day_num'   => $date->format('j'),
        'full_date' => $date->format('Y-m-d'),
        'is_selected' => ($date->format('Y-m-d') === $selected_date),
        'w_index' => $i
    ];
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
                <h1 class="month-title"><?= $display_month ?>月</h1>
            </div>
            
            <div class="week-days">
                <?php foreach ($week_dates as $date): 
                    $class = "day-item";
                    if ($date['w_index'] == 0) $class .= " day-sun";
                    if ($date['w_index'] == 6) $class .= " day-sat";
                    if ($date['is_selected']) $class .= " active";
                ?>
                <div class="<?= $class ?>" onclick="location.href='?date=<?= $date['full_date'] ?>'">
                    <?= $date['day_label'] ?><span class="date"><?= $date['day_num'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            
            <nav class="tab-menu">
                <a href="training_select.php?date=<?= $selected_date ?>" class="tab-item active">トレーニング記録</a>
                <a href="#" class="tab-item">ボディデータ</a>
            </nav>
        </header>

        <main class="content-area">
            <div class="rest-container">
                <div class="rest-illustration">
                    <img src="../tr_img/おやすみ画面.png" alt="おやすみイラスト">
                </div>
                <p class="rest-message">トレーニングおやすみ U_U</p>
                <a href="training_select.php?date=<?= $selected_date ?>" class="cancel-link">キャンセル</a>
            </div>
        </main>
    </div>
</body>
</html>