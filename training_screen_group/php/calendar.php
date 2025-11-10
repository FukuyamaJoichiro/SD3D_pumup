<?php
session_start();
require_once("../../db_connect.php"); // パスは環境に合わせて変更

// 仮のログイン中ユーザーID
$user_id = 1;

// 現在年月を取得
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$today = date('Y-m-d');

// 月初と月末
$start_date = "$year-$month-01";
$end_date = date("Y-m-t", strtotime($start_date));

// トレーニングデータ取得
$sql = "
    SELECT DATE(ws.date) AS date, GROUP_CONCAT(DISTINCT p.part_name) AS parts
    FROM workout_sessions ws
    JOIN supersets s ON ws.session_id = s.session_id
    JOIN workout_sets wset ON s.superset_id = wset.superset_id
    JOIN training_parts tp ON wset.training_id = tp.training_id
    JOIN parts p ON tp.part_id = p.part_id
    WHERE ws.user_id = :user_id
      AND ws.date BETWEEN :start AND :end
    GROUP BY DATE(ws.date)
";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);
$stmt->bindValue(":start", $start_date);
$stmt->bindValue(":end", $end_date);
$stmt->execute();
$training_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 日付をキーに変換
$training_days = [];
foreach ($training_data as $row) {
    $training_days[$row['date']] = explode(',', $row['parts']);
}

// 累計日数
$total_sql = "SELECT COUNT(DISTINCT DATE(date)) AS total_days FROM workout_sessions WHERE user_id = :uid";
$total_stmt = $pdo->prepare($total_sql);
$total_stmt->bindValue(":uid", $user_id);
$total_stmt->execute();
$total = $total_stmt->fetch(PDO::FETCH_ASSOC)['total_days'];

// 月間日数
$monthly_count = count($training_days);

// 継続日数を仮で計算（連続日数ロジック）
$streak = 0;
$prev_date = null;
$dates = array_keys($training_days);
sort($dates);
foreach ($dates as $d) {
    if ($prev_date && date('Y-m-d', strtotime("$prev_date +1 day")) == $d) {
        $streak++;
    } else {
        $streak = 1;
    }
    $prev_date = $d;
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
            echo "<td class='" . ($is_today ? "today" : "") . "'>";

            echo "<div class='day-num'>$day</div>";

            if (isset($training_days[$current_date])) {
                foreach ($training_days[$current_date] as $part) {
                    echo "<div class='training-part'>$part</div>";
                }
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

    <div class="calendar-footer">
        <p><?= date('n月j日 D', strtotime($today)) ?>（<?= $streak ?>日継続中！）</p>
        <button onclick="location.href='training_record.php'">今日のトレーニングプランを立てる</button>
    </div>

    <div class="bottom-nav">
        <div class="nav-item"><a href="../../home_screen_group/php/home.php">🏠<br>ホーム</a></div>
        <div class="nav-item active"><a href="calendar.php">📅<br>カレンダー</a></div>
        <div class="nav-item"><a href="../../home_screen_group/php/mypage.php">👤<br>マイページ</a></div>
    </div>
</div>
</body>

</html>
