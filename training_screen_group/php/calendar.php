<?php
date_default_timezone_set('Asia/Tokyo');
// PHPエラー表示設定
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("../../auth.php"); 
require_once("../../db_connect.php"); 

require_login(); 
$user_id = $_SESSION['user_id']; 

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
// 3. DBからデータを取得
// ==========================================================
$sql = "
    SELECT 
        id, 
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
        'id' => $row['id'], 
        'type' => $row['session_type'], 
        'part_id' => $row['part_id']
    ];
}

// 累計計算
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
$check_date = date('Y-m-d');

while (true) {
    $check_sql = "SELECT COUNT(*) FROM calendar_activity WHERE user_id = :uid AND activity_date = :d AND session_type = 'WORKOUT'";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->bindValue(":uid", $user_id);
    $check_stmt->bindValue(":d", $check_date);
    $check_stmt->execute();
    
    if ($check_stmt->fetchColumn() > 0) {
        $streak++;
        $check_date = date('Y-m-d', strtotime($check_date . ' -1 day'));
    } else {
        break;
    }
}
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

                    $class_list = ["calendar-cell"]; // JSで判定しやすいよう共通クラス追加
                    if ($is_today) $class_list[] = "today";
                    if ($weekday == 0 || $is_holiday) $class_list[] = "holiday";
                    elseif ($weekday == 6) $class_list[] = "saturday";
                    
                    if (isset($training_days[$current_date])) {
                        foreach($training_days[$current_date] as $act) {
                            if ($act['type'] === 'WORKOUT') { $class_list[] = "trained"; break; }
                            elseif ($act['type'] === 'REST') { $class_list[] = "rest-day"; }
                        }
                    }

                    // 💡 data-id属性とdata-dateをTDに直接持たせる
                    $first_id = isset($training_days[$current_date][0]) ? $training_days[$current_date][0]['id'] : '';
                    
                    echo "<td class='" . implode(' ', $class_list) . "' data-date='$current_date' data-id='$first_id'>";
                    echo "<div class='day-num'>$day</div>";

                    if (isset($training_days[$current_date])) {
                        echo "<div class='activity-container'>";
                        $activities = $training_days[$current_date];
                        $limit = 2;
                        $count = 0;

                        foreach ($activities as $activity) {
                            if ($count < $limit) {
                                if ($activity['type'] === 'REST') {
                                    echo "<div class='rest-content'>";
                                    echo "  <div class='rest-bottom-row'>";
                                    echo "<div class='rest-icon'>😴</div>"; 
                                    echo "    <div class='rest-button'>おやすみ</div>";
                                    echo "  </div></div>";
                                } 
                                elseif ($activity['type'] === 'WORKOUT') {
                                    $part_id = $activity['part_id'];
                                    $part_info = match ((int)$part_id) {
                                        1 => ['icon' => '🔥', 'label' => '胸'],
                                        2 => ['icon' => '🔺', 'label' => '肩'],
                                        3 => ['icon' => '🦁', 'label' => '背中'],
                                        4 => ['icon' => '💪', 'label' => '腕'],
                                        5 => ['icon' => '🛡️', 'label' => '腹筋'],
                                        6 => ['icon' => '🦵', 'label' => '脚'],
                                        default => ['icon' => '🏋️', 'label' => 'トレ']
                                    };
                                    echo "<div class='workout-item'>";
                                    echo "  <span class='activity-icon'>{$part_info['icon']}</span>";
                                    echo "  <span class='part-label'>{$part_info['label']}</span>";
                                    echo "</div>";
                                }
                            }
                            $count++;
                        }
                        if ($count > $limit) echo "<div class='more-mark'>+</div>";
                        echo "</div>";
                    }
                    echo "</td>";

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
            <p id="display-date-text"><?= date('n月j日 D', strtotime($today)) ?>（<?= $streak ?>日継続中！）</p>
            <button onclick="location.href='training_record.php'">今日のトレーニングプランを立てる</button>
        </div>
    </div>

    <nav class="app-nav">
        <a href="../../home_screen_group/php/home.php" class="nav-item">
            <span class="nav-item-icon">🏠</span> ホーム
        </a>
        <a href="calendar.php" class="nav-item active">
            <span class="nav-item-icon">💪</span> カレンダー
        </a>
        <a href="../../home_screen_group/php/mypage.php" class="nav-item">
            <span class="nav-item-icon">👤</span> マイページ
        </a>
    </nav>

    <div id="activity-modal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <h3 id="modal-date-display"></h3> 
            <input type="hidden" id="selected-date-input">
            <input type="hidden" id="selected-activity-id"> 
            
            <button id="record-workout-btn" class="modal-btn primary-btn">✅ トレーニングを記録する</button>
            <button id="remove-rest-btn" class="modal-btn secondary-btn">🗑️ 記録を削除する</button>
            <button id="change-rest-btn" class="modal-btn secondary-btn">🔄 おやすみを変更する</button>
            <button id="cancel-btn" class="modal-btn tertiary-btn">キャンセル</button>
        </div>
    </div>

    <script src="calendar.js"></script>
</body>
</html>