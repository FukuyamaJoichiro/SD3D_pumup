<?php
require_once("../../db_connect.php");
session_start();

// 仮のユーザーID（ログイン後に差し替え）
$user_id = 1;

// クエリパラメータから日付を取得、なければ今日の日付を使用
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// 今日の日付 (丸印ハイライト用)
$today = date('Y-m-d');
$month = date('n', strtotime($selected_date));

// $selected_date を基準に、その週の日付を計算
$timestamp = strtotime($selected_date);
$day_of_week = date('w', $timestamp); // 0 (日) から 6 (土)
// 週の始まり（日曜日）のタイムスタンプ
$start_of_week_timestamp = strtotime("-$day_of_week days", $timestamp); 

$dates_of_week = [];
for ($i = 0; $i < 7; $i++) {
    $current_timestamp = strtotime("+$i days", $start_of_week_timestamp);
    $dates_of_week[] = [
        'full_date' => date('Y-m-d', $current_timestamp),
        'day' => date('j', $current_timestamp), // 日付(1, 2, 3...)
        'weekday' => date('w', $current_timestamp) // 曜日番号
    ];
}

// 選択された日付のトレーニングが登録済みか確認
$sql = "SELECT COUNT(*) AS count FROM workout_sessions WHERE user_id = :user_id AND DATE(date) = :selected_date";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':user_id', $user_id);
$stmt->bindValue(':selected_date', $selected_date);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$hasTraining = $result['count'] > 0;
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
<div class="back-btn" onclick="history.back()">&#x2039;</div>
<div class="month"><?= $month ?>月</div>
</header>

<div class="calendar-week">
<table class="date-slider">
<tr>
<th>日</th><th>月</th><th>火</th><th>水</th><th>木</th><th>金</th><th>土</th>
</tr>
<tr>
<?php foreach ($dates_of_week as $date_info): ?>
<?php
     $classes = [];
     
     // 選択された日付に 'selected' クラスを付与
     if ($date_info['full_date'] == $selected_date) {
         $classes[] = 'selected';
     }
     
     // 今日(本日)の日付に 'today-mark' クラスを付与（赤い●用）
     if ($date_info['full_date'] == $today) {
         $classes[] = 'today-mark';
     }
     
     // 日曜日(0)と土曜日(6)にクラスを付与
     if ($date_info['weekday'] == 0) $classes[] = 'weekday-sun';
     if ($date_info['weekday'] == 6) $classes[] = 'weekday-sat';

     $class_string = implode(' ', $classes);
?>
<td class="<?= $class_string ?>">
<a href="?date=<?= $date_info['full_date'] ?>" data-date="<?= $date_info['full_date'] ?>">
<?= $date_info['day'] ?>
</a>
</td>
<?php endforeach; ?>
</tr>
</table>
</div>

<div class="tab-menu">
<div class="tab active">トレーニング記録</div>
<div class="tab"><a href="bodydata.php" >ボディデータ</a></div>
</div>

<section class="training-card">
<h3>今日のトレーニング</h3>
<p>トレーニングを計画してみましょう！</p>
<button class="training-btn" onclick="location.href='training_list.php'">トレーニング選択</button>
</section>

 <section class="status-section">
 <p class="timer" onclick="location.href='interval_timer.php'">
 ⏱ Interval Timer ⏱
 </p>

<p class="rest" onclick="location.href='training_rest.php'">
 🛏 今日は休みます 🛏
 </p>
 </section>

</div>
 <script src="training_record.js"></script>
</body>
</html>