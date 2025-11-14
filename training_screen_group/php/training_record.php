<?php
require_once("../../db_connect.php");
session_start();

// 仮のユーザーID（ログイン後に差し替え）
$user_id = 1;

// 今日の日付
$today = date('Y-m-d');
$month = date('n');
// --- データ取得例（必要に応じて拡張可能） ---
// 今日のトレーニングが登録済みか確認
$sql = "SELECT COUNT(*) AS count FROM workout_sessions WHERE user_id = :user_id AND DATE(date) = :today";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':user_id', $user_id);
$stmt->bindValue(':today', $today);
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
  <link rel="stylesheet" href="training_recode_style.css">
</head>
<body>
  <div class="app-container">

    <!-- ヘッダー -->
    <header class="header">
      <div class="back-btn" onclick="history.back()">&#x2039;</div>
      <div class="month"><?= $month ?>月</div>
    </header>

    <!-- 曜日ヘッダー -->
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

    <!-- タブメニュー -->
    <div class="tab-menu">
      <div class="tab active">トレーニング記録</div>
      <div class="tab"><a href="bodydata.php" >ボディデータ</a></div>
    </div>

    <!-- トレーニング選択カード -->
    <section class="training-card">
      <h3>今日のトレーニング</h3>
      <p>トレーニングを計画してみましょう！</p>
      <button class="training-btn" onclick="location.href='training_select.php'">トレーニング選択</button>
    </section>

<!-- タイマー・休み表示 -->
<section class="status-section">
  <!-- タイマー画面へ遷移 -->
  <p class="timer" onclick="location.href='interval_timer.php'">
    ⏱ Interval Timer ⏱
  </p>

  <!-- 休み画面へ遷移 -->
  <p class="rest" onclick="location.href='training_rest.php'">
    🛏 今日は休みます 🛏
  </p>
</section>



  </div>
</body>
</html>
