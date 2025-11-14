<?php
require_once("../../db_connect.php");
session_start();

// 仮のユーザーID（ログイン後に差し替え）
$user_id = 1;

// 今日の日付
$today = date('Y-m-d');
$month = date('n');

// --- データ取得例（必要に応じて拡張可能） ---
// ここでは、ユーザーの最新のボディデータを取得するSQLを追加できます。
// 例: $sql = "SELECT weight, height, muscle_rate, fat_rate FROM body_data WHERE user_id = :user_id ORDER BY date DESC LIMIT 1";

// 既存のトレーニング記録のPHPコードは、ここでは省略または最小限にとどめます。
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Myボディデータ</title>
  <!-- CSSファイル名をbodydata.cssに変更 -->
  <link rel="stylesheet" href="bodydata.css"> 
  <!-- アイコン表示のためにFont Awesomeを追加 (例として、アイコンはSVGを使用します) -->
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

    <!-- ヘッダー (共通部分) -->
    <header class="header">
      <!-- training_record.phpへ戻る -->
      <div class="back-btn" onclick="location.href='training_record.php'">&#x2039;</div>
      <div class="month"><?= $month ?>月</div>
    </header>

    <!-- 曜日ヘッダー (共通部分) -->
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

    <!-- タブメニュー (共通部分: bodydataをactiveに変更) -->
    <div class="tab-menu">
      <!-- training_record.phpへ遷移 (非アクティブ) -->
      <div class="tab"><a href="training_record.php">トレーニング記録</a></div>
      <!-- bodydata.php自身をアクティブに設定 -->
      <div class="tab active"><a href="bodydata.php">ボディデータ</a></div>
    </div>

    <!-- Myボディデータ セクション -->
    <section class="bodydata-header">
        <h2 class="title">MYボディデータ</h2>
        <!-- 実際には履歴画面などに遷移 -->
        <a href="#" class="view-all">すべてを表示</a>
    </section>

    <!-- データ入力/表示カード群 -->
    <div class="data-cards-container">
        <!-- 体重カード -->
        <div class="data-card" data-type="weight">
            <div class="card-content">
                <div class="icon-circle icon-weight">
                    <!-- 体重アイコン -->
                    <svg viewBox="0 0 24 24" width="24" height="24" stroke="white" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a10 10 0 0 0-8.9 5.09A10 10 0 0 0 12 22a10 10 0 0 0 8.9-5.09A10 10 0 0 0 12 2z"></path><path d="M15 9l-3 3-3-3m3 3V7"></path></svg>
                </div>
                <div class="data-info">
                    <p class="data-label">体重</p>
                    <p class="data-value">データを追加してください。</p>
                </div>
            </div>
            <div class="arrow">></div>
        </div>

        <!-- 身長カード -->
        <div class="data-card" data-type="height">
            <div class="card-content">
                <div class="icon-circle icon-height">
                    <!-- 身長アイコン -->
                    <svg viewBox="0 0 24 24" width="24" height="24" stroke="white" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20v-8m0 0l-3 3m3-3l3 3m-9-3c0-4.42 3.58-8 8-8s8 3.58 8 8"></path></svg>
                </div>
                <div class="data-info">
                    <p class="data-label">身長</p>
                    <p class="data-value">データを追加してください。</p>
                </div>
            </div>
            <div class="arrow">></div>
        </div>
        
        <!-- 筋肉率カード -->
        <div class="data-card" data-type="muscle">
            <div class="card-content">
                <div class="icon-circle icon-muscle">
                    <!-- 筋肉アイコン -->
                    <svg viewBox="0 0 24 24" width="24" height="24" stroke="white" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M15 17L12 20L9 17L6 20L3 17V7L6 4L9 7L12 4L15 7L18 4L21 7V17L18 20L15 17Z"></path></svg>
                </div>
                <div class="data-info">
                    <p class="data-label">筋肉率</p>
                    <p class="data-value">データを追加してください。</p>
                </div>
            </div>
            <div class="arrow">></div>
        </div>
        
        <!-- 体脂肪率カード -->
        <div class="data-card" data-type="fat">
            <div class="card-content">
                <div class="icon-circle icon-fat">
                    <!-- 体脂肪アイコン (カスタム) -->
                    <svg viewBox="0 0 24 24" width="24" height="24" stroke="white" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z"></path><path d="M12 2V22M2 12H22M15 9H9M15 15H9"></path></svg>
                </div>
                <div class="data-info">
                    <p class="data-label">体脂肪率</p>
                    <p class="data-value">データを追加してください。</p>
                </div>
            </div>
            <div class="arrow">></div>
        </div>
        
    </div>
    
    <!-- ここに下部ナビゲーションなどの共通要素を追加する場合は、training_record.phpからコピーしてください -->

  </div>

  <script src="bodydata.js"></script>
</body>
</html>