<?php
// PHPエラー表示設定 (開発時のみ有効にすることを推奨)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ==========================================================
// 1. 必要なファイルの読み込みと認証
// ==========================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("../../auth.php"); 
require_once("../../db_connect.php"); 

require_login(); 
$user_id = $_SESSION['user_id']; 

// ----------------------------------------------------------
// 2. 日付関連の設定
// ----------------------------------------------------------
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$today = date('Y-m-d');

$start_date = "$year-$month-01";
$end_date = date("Y-m-t", strtotime($start_date));

$holidays = [
    "2025-11-03", // 文化の日
    "2025-11-23", // 勤労感謝の日
];

// ==========================================================
// 3. DBからアクティビティデータ（トレーニング/休息）を取得
// ==========================================================
$sql = "
    SELECT 
        activity_date AS date, 
        session_type,
        part_id 
    FROM calendar_activity
    WHERE user_id = :user_id
      AND activity_date BETWEEN :start AND :end
    ORDER BY part_id ASC
";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);
$stmt->bindValue(":start", $start_date);
$stmt->bindValue(":end", $end_date);
$stmt->execute();
$activity_data = $stmt->fetchAll(PDO::FETCH_ASSOC); 

$training_days = []; 
foreach ($activity_data as $row) {
    $training_days[$row['date']][] = [
        'type' => $row['session_type'], 
        'part_id' => $row['part_id']
    ];
}

// ----------------------------------------------------------
// 4. 累計日数・継続日数の計算
// ----------------------------------------------------------
$total_sql = "SELECT COUNT(DISTINCT activity_date) AS total_days FROM calendar_activity WHERE user_id = :uid AND session_type = 'WORKOUT'";
$total_stmt = $pdo->prepare($total_sql);
$total_stmt->bindValue(":uid", $user_id);
$total_stmt->execute();
$total = $total_stmt->fetch(PDO::FETCH_ASSOC)['total_days'];

$monthly_count = 0;
foreach($training_days as $date_key => $activities) {
    foreach($activities as $act) {
        if ($act['type'] === 'WORKOUT') {
            $monthly_count++;
            break; 
        }
    }
}

$streak = 0; 

?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GoriFit カレンダー</title>
<link rel="stylesheet" href="calendar_style.css">
</head>
<body>
    <div class="app-container">
        <div class="app-header">
            <p><?= $year ?>年<?= $month ?>月</p>
            <h2>Total : <?= $total ?>days!</h2>
            <p>Monthly Archive. <strong><?= $monthly_count ?>day</strong></p>
        </div>

        <div class="calendar-warpper">
            <table class="calender">
                <thead>
                    <tr>
                        <th>日</th><th>月</th><th>火</th><th>水</th><th>木</th><th>金</th><th>土</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $first_day_week = date('w', strtotime($start_date));
                $days_in_month = date('t', strtotime($start_date));

                $day = 1;
                echo "<tr>";
                for ($i=0; $i<$first_day_week; $i++) echo "<td></td>";

                while ($day <= $days_in_month) {
                    $current_date = sprintf("%04d-%02d-%02d", $year, $month, $day);
                    $weekday = date('w', strtotime($current_date));
                    $is_today = ($current_date == $today);
                    $is_holiday = in_array($current_date, $holidays);

                    $class_list = [];
                    if ($is_today) $class_list[] = "today";
                    if ($weekday == 0 || $is_holiday) $class_list[] = "holiday";
                    elseif ($weekday == 6) $class_list[] = "saturday";
                    
                    if (isset($training_days[$current_date])) {
                        foreach($training_days[$current_date] as $act) {
                            if ($act['type'] === 'WORKOUT') {
                                $class_list[] = "trained"; 
                                break;
                            } elseif ($act['type'] === 'REST') {
                                $class_list[] = "rest-day"; 
                            }
                        }
                    }

                    echo "<td class='" . implode(' ', $class_list) . "'>";
                    echo "<div class='date-clickable-wrapper' data-date='$current_date' onclick='handleDateClick(this)'>";
                    echo "<div class='day-num'>$day</div>";

                    // === 修正箇所：最大2個表示＋「+」マーク表示 ===
                    if (isset($training_days[$current_date])) {
                        echo "<div class='activity-container'>";
                        
                        $activities = $training_days[$current_date];
                        $limit = 2; // 表示する最大数
                        $count = 0;

                        foreach ($activities as $activity) {
                            if ($count < $limit) {
                                if ($activity['type'] === 'REST') {
                                    echo "<div class='rest-content'>";
                                    echo "  <div class='rest-bottom-row'>";
                                    echo "<div class='rest-icon'>😴</div>"; 
                                    echo "    <div class='rest-button'>おやすみ</div>";
                                    echo "  </div>";
                                    echo "</div>";
                                } 
                                elseif ($activity['type'] === 'WORKOUT') {
    $part_id = $activity['part_id'];
    
    // アイコンとラベルをセットで定義
    $part_info = match ((int)$part_id) {
        1 => ['icon' => '💪', 'label' => '胸'],
        2 => ['icon' => '🦁', 'label' => '背中'],
        3 => ['icon' => '🔺', 'label' => '肩'],
        4 => ['icon' => '🦵', 'label' => '脚'],
        5 => ['icon' => '🔥', 'label' => '腕'],
        6 => ['icon' => '🛡️', 'label' => '腹筋'],
        default => ['icon' => '🏋️', 'label' => 'トレ']
    };

    echo "<div class='workout-item'>"; // 新しい囲み要素
    echo "  <span class='activity-icon'>{$part_info['icon']}</span>";
    echo "  <span class='part-label'>{$part_info['label']}</span>";
    echo "</div>";
}
                            }
                            $count++;
                        }

                        // 3つ目以上がある場合にプラスマークを表示
                        if ($count > $limit) {
                            echo "<div class='more-mark'>+</div>";
                        }
                        
                        echo "</div>";
                    }
                    // === 修正箇所ここまで ===
                    
                    echo "</div></td>";

                    if ($weekday == 6) echo "</tr><tr>";
                    $day++;
                }

                $last_weekday = date('w', strtotime("$year-$month-$days_in_month"));
                for ($i=$last_weekday; $i<6; $i++) echo "<td></td>";
                echo "</tr>";
                ?>
                </tbody>
            </table>
        </div>

        <div class="calendar-footer">
            <p><?= date('n月j日 D', strtotime($today)) ?>（<?= $streak ?>日継続中！）</p>
            <button onclick="location.href='training_record.php'">今日のトレーニングプランを立てる</button>
        </div>

        <nav class="app-nav">
            <a href="../../home_screen_group/php/home.php" class="nav-item">
                <span class="nav-item-icon">🏠</span> ホーム
            </a>
            <a href="calendar.php" class="nav-item activ">
                <span class="nav-item-icon">💪</span> カレンダー
            </a>
            <a href="../../home_screen_group/php/mypage.php" class="nav-item">
                <span class="nav-item-icon">👤</span> マイページ
            </a>
        </nav>
    </div>

    <div id="activity-modal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <h3 id="modal-date-display"></h3> 
            <input type="hidden" id="selected-date-input">
            <button id="record-workout-btn" class="modal-btn primary-btn">✅ トレーニングを記録する</button>
            <button id="remove-rest-btn" class="modal-btn secondary-btn">❌ おやすみを解除する</button>
            <button id="change-rest-btn" class="modal-btn secondary-btn">🔄 おやすみを変更する</button>
            <button id="cancel-btn" class="modal-btn tertiary-btn">キャンセル</button>
        </div>
    </div>

    <script src="calendar.js"></script>
</body>
</html>