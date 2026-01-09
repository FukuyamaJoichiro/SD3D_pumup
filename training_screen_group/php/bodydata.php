<?php
// DB接続とセッションの開始
require_once("../../db_connect.php"); 
session_start();

// ユーザーIDの取得
$user_id = $_SESSION['user_id'] ?? 1; 

global $pdo;

// --- カレンダー表示用の日付計算ロジック ---
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$today = date('Y-m-d');
$month = date('n', strtotime($selected_date));

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

// --- データ取得と計算 ---
$weight = 0.0;
$height = 0.0;
$age = 0;
$body_fat_percentage = 0.0;
$muscle_percentage = 0.0;

try {
    // ユーザー情報を取得
    $stmt = $pdo->prepare("SELECT weight, height, birthday, gender FROM users WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $db_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($db_data) {
        $weight = (float)$db_data['weight'];
        $height = (float)$db_data['height'];
        
        // --- 性別文字列を計算用の数値に変換 ---
        $gender_str = $db_data['gender'] ?? '男性';
        $gender_value = ($gender_str === '男性') ? 1 : 0;

        // 年齢の計算
        $birthday = new DateTime($db_data['birthday']);
        $today_dt = new DateTime('today');
        $age = $birthday->diff($today_dt)->y;

        // BMIの計算
        $height_m = $height / 100;
        $bmi = ($height_m > 0) ? $weight / ($height_m * $height_m) : 0; 

        if ($bmi > 0) {
            /**
             * 1. 体脂肪率の推定（Deurenbergの式）
             */
            $calc_fat = (1.20 * $bmi) + (0.23 * $age) - (10.8 * $gender_value) - 5.4;
            $body_fat_percentage = max(5.0, min(50.0, round($calc_fat, 1)));

            /**
             * 2. 筋肉率の推定 (修正箇所)
             * 除脂肪率(100 - 体脂肪) に 筋肉係数(0.5) を掛ける方式に変更
             */
            $lean_body_mass_percent = 100 - $body_fat_percentage;
            $calc_muscle = $lean_body_mass_percent * 0.5;

            // 異常値の制限 (範囲を 70.0% まで拡大)
            $muscle_percentage = max(10.0, min(70.0, round($calc_muscle, 1)));
        }
    } 
} catch (Exception $e) {
    error_log("ボディデータ取得エラー: " . $e->getMessage());
}

// 表示用データの整形
$display_weight = ($weight > 0) ? number_format($weight, 1) . ' kg' : '未登録';
$display_height = ($height > 0) ? number_format($height, 1) . ' cm' : '未登録';
$display_muscle = ($muscle_percentage > 0) ? number_format($muscle_percentage, 1) . ' %' : '--- %';
$display_fat = ($body_fat_percentage > 0) ? number_format($body_fat_percentage, 1) . ' %' : '--- %';

?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Myボディデータ</title>
<link rel="stylesheet" href="bodydata.css"> 
<style>
/* Phosphor Icons/Inline SVG for icons */
.icon-weight { background-color: #92e6a7; }
.icon-height { background-color: #6ed3cf; }
.icon-muscle { background-color: #ffb75e; }
.icon-fat { background-color: #ff99c4; }
</style>
</head>
<body>
<div class="app-container">

<header class="header">
<div class="back-btn" onclick="location.href='calendar.php'">&#x2039;</div>
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
// 今日(本日)の日付に 'today-mark' クラスを付与
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
<div class="tab"><a href="training_record.php">トレーニング記録</a></div>
<div class="tab active"><a href="bodydata.php">ボディデータ</a></div>
</div>

<section class="bodydata-header">
<h2 class="title">MYボディデータ</h2>
<a href="mybodydata_edit.php" class="view-all">すべてを表示</a>
</section>

<div class="data-cards-container">
<div class="data-card" data-type="weight">
<div class="card-content">
<div class="icon-circle icon-weight">
<svg viewBox="0 0 24 24" width="24" height="24" stroke="white" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a10 10 0 0 0-8.9 5.09A10 10 0 0 0 12 22a10 10 0 0 0 8.9-5.09A10 10 0 0 0 12 2z"></path><path d="M15 9l-3 3-3-3m3 3V7"></path></svg>
</div>
<div class="data-info">
<p class="data-label">体重</p>
<p class="data-value"><?= $display_weight ?></p>
</div>
</div>
<div class="arrow">></div>
</div>

<div class="data-card" data-type="height">
<div class="card-content">
<div class="icon-circle icon-height">
<svg viewBox="0 0 24 24" width="24" height="24" stroke="white" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20v-8m0 0l-3 3m3-3l3 3m-9-3c0-4.42 3.58-8 8-8s8 3.58 8 8"></path></svg>
</div>
<div class="data-info">
<p class="data-label">身長</p>
<p class="data-value"><?= $display_height ?></p>
</div>
</div>
<div class="arrow">></div>
</div>
 
 <div class="data-card" data-type="muscle">
 <div class="card-content">
 <div class="icon-circle icon-muscle">
<svg viewBox="0 0 24 24" width="24" height="24" stroke="white" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M15 17L12 20L9 17L6 20L3 17V7L6 4L9 7L12 4L15 7L18 4L21 7V17L18 20L15 17Z"></path></svg>
 </div>
 <div class="data-info">
 <p class="data-label">筋肉率</p>
<p class="data-value"><?= $display_muscle ?></p>
</div>
</div>
<div class="arrow">></div>
</div>
<div class="data-card" data-type="fat">
 <div class="card-content">
<div class="icon-circle icon-fat">
<svg viewBox="0 0 24 24" width="24" height="24" stroke="white" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z"></path><path d="M12 2V22M2 12H22M15 9H9M15 15H9"></path></svg>
</div>
<div class="data-info">
<p class="data-label">体脂肪率</p>
<p class="data-value"><?= $display_fat ?></p>
</div>
</div>
</div>
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

<script src="bodydata.js"></script>
</body>
</html>