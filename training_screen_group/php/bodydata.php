<?php
// DB接続とセッションの開始
require_once("../../db_connect.php"); // ★ 接続ファイルはここ
session_start();

// ユーザーIDの取得 (ログイン処理が完了していることを前提)
// 環境に合わせて適切なセッション変数名に修正してください。
$user_id = $_SESSION['user_id'] ?? 1; // ログインしていなければ仮のID '1' を使用

global $pdo; // db_connect.phpでPDOオブジェクトが$pdoに格納されていることを想定

// 今日の日付
$today = date('Y-m-d');
$month = date('n');

// --- データ取得と計算 ---
$weight = 0.0;
$height = 0.0;
$age = 0;
$body_fat_percentage = 0.0;
$muscle_percentage = 0.0;

try {
    // 必要なデータ (身長、体重、生年月日) をDBから取得
    // muscle_ptc, bodyfat_ptc は bodydata_edit.php の流れではDBに保存されていないため、
    // ここで weight, height, birthday を取得し、再計算します。
    $stmt = $pdo->prepare("SELECT weight, height, birthday FROM users WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $db_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($db_data) {
        $weight = (float)$db_data['weight'];
        $height = (float)$db_data['height'];

        // 年齢の計算
        $birthday = new DateTime($db_data['birthday']);
        $today_dt = new DateTime('today');
        $age = $birthday->diff($today_dt)->y;

        // 体組成データの再計算 (mybodydata_edit.phpと同一のロジック)
        $height_m = $height / 100;
        $bmi = ($height_m > 0) ? $weight / ($height_m * $height_m) : 0; 

        if ($bmi < 18.5) {
            $body_fat_percentage = 15.0 - ($bmi * 0.1) + ($age * 0.05); 
        } elseif ($bmi < 25) {
            $body_fat_percentage = 20.0 + ($age * 0.05); 
        } else {
            $body_fat_percentage = 25.0 + ($bmi * 0.5) + ($age * 0.1); 
        }
        $body_fat_percentage = max(5.0, min(50.0, round($body_fat_percentage, 1)));

        // 筋肉率の計算（体脂肪率に依存）
        $muscle_percentage = 100 - $body_fat_percentage - 15; // 仮に15%を骨/その他とする
        $muscle_percentage = max(10.0, min(60.0, round($muscle_percentage, 1)));
        
    } else {
        // データがない、またはユーザーが見つからない場合の初期値設定
        // 初期値のメッセージはHTML側で表示されます。
    }

} catch (Exception $e) {
    error_log("ボディデータ取得エラー: " . $e->getMessage());
    // エラー時の初期値設定（このまま進める）
}

// データが0の場合は、HTMLで "データを追加してください。" を表示するために null または false を使用
$display_weight = ($weight > 0) ? number_format($weight, 1) . ' kg' : 'データを追加してください。';
$display_height = ($height > 0) ? number_format($height, 1) . ' cm' : 'データを追加してください。';
$display_muscle = ($muscle_percentage > 0) ? number_format($muscle_percentage, 1) . ' %' : 'データを追加してください。';
$display_fat = ($body_fat_percentage > 0) ? number_format($body_fat_percentage, 1) . ' %' : 'データを追加してください。';

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
 <div class="back-btn" onclick="location.href='training_record.php'">&#x2039;</div>
 <div class="month"><?= $month ?>月</div>
 </header>

 <div class="calendar-week">
 <table>
 <tr>
 <th>日</th><th>月</th><th>火</th><th>水</th><th>木</th><th>金</th><th>土</th>
 </tr>
 <tr>
 <td class="inactive">7</td>
     <td>8</td>
     <td class="today">9</td>
     <td>10</td>
     <td>11</td>
     <td>12</td>
     <td>13</td>
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
<div class="arrow">></div>
</div>
</div>
 
</div>

 <script src="bodydata.js"></script>
</body>
</html>