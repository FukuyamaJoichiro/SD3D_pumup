<?php
// mypage.php の元々の上部
require_once("../../auth.php");
require_login('../../initial_screen_group/php/login.php');

// ------------------------------------------------
// MYボディデータの計算ロジック (bodydata.php から移植)
// ------------------------------------------------

// ログイン済みのため $_SESSION['user_id'] を利用
$user_id = $_SESSION['user_id'] ?? 1; // bodydata.php に合わせた仮のID '1' の使用
global $pdo; // auth.php で $pdo が初期化されていることを前提

$weight = 0.0;
$height = 0.0;
$age = 0;
$body_fat_percentage = 0.0;
$muscle_percentage = 0.0;

try {
    // 体重、身長、生年月日を取得
    $stmt = $pdo->prepare("SELECT weight, height, birthday FROM users WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $db_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($db_data) {
        $weight = (float)($db_data['weight'] ?? 0.0);
        $height = (float)($db_data['height'] ?? 0.0);

        // 年齢の計算
        if (!empty($db_data['birthday'])) {
            $birthday = new DateTime($db_data['birthday']);
            $today_dt = new DateTime('today');
            $age = $birthday->diff($today_dt)->y;
        }

        // 体組成データの再計算 (bodydata.phpのロジックを完全に再現)
        $height_m = $height / 100;
        $bmi = ($height_m > 0) ? $weight / ($height_m * $height_m) : 0; 
        
        // 体脂肪率の推定計算
        if ($bmi < 18.5) {
            $body_fat_percentage = 15.0 - ($bmi * 0.1) + ($age * 0.05); 
        } elseif ($bmi < 25) {
            $body_fat_percentage = 20.0 + ($age * 0.05); 
        } else {
            // 注意: mypage.phpとbodydata.phpの計算式が厳密には異なる可能性がありますが、
            // bodydata.phpのコードを優先します。
            $body_fat_percentage = 25.0 + ($bmi * 0.5) + ($age * 0.1); 
        }
        $body_fat_percentage = max(5.0, min(50.0, round($body_fat_percentage, 1)));

        // 筋肉率の計算（体脂肪率に依存）
        $muscle_percentage = 100 - $body_fat_percentage - 15; // 仮に15%を骨/その他とする
        $muscle_percentage = max(10.0, min(60.0, round($muscle_percentage, 1)));
    } 
} catch (Exception $e) {
    error_log("マイページ計算エラー: " . $e->getMessage());
}

// ------------------------------------------------
// 表示用の値整形 (mypage.phpの既存の変数名に合わせる)
// ------------------------------------------------
// mypage.phpのHTMLに単位がないため、ここでは単位「%」を付与します。

// 体重 (例: 80.0)
$weight = ($weight > 0) ? htmlspecialchars(number_format($weight, 1)) : '-';

// 筋肉率 (例: 35.0 %)
$muscle_rate = ($muscle_percentage > 0) 
    ? htmlspecialchars(number_format($muscle_percentage, 1) . '%') 
    : '-';

// 体脂肪率 (例: 20.0 %)
$fat_rate = ($body_fat_percentage > 0) 
    ? htmlspecialchars(number_format($body_fat_percentage, 1) . '%') 
    : '-';
    
// HTML側で体脂肪率と筋肉率の「-」が消え、計算値が表示されます。
?>


<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>マイページ</title>

  <!-- CSS 読み込み -->
  <link rel="stylesheet" href="mypage_style.css">
</head>

<body>
  <div class="container">

    <!-- プロフィール登録 -->
    <section class="profile-section">
      <div class="profile-info">
        <p class="profile-register">
          プロフィール編集 <a href="profile.php" class="edit-icon">✏️</a>
        </p>
        <p class="sub-text">自分の記録を保存して下さい</p>
        <button class="upgrade-btn">アップグレードする</button>
      </div>
    </section>

    <!-- MYボディデータ -->
    <section class="body-data">
      <h2>MYボディデータ<a href="../../training_screen_group/php/mybodydata_edit.php" class="edit-link">
         📝</a></h2>
      <div class="body-card-container">
        <div class="body-card">
          <div class="icon">🏋️‍♀️</div>
          <p class="value">
            <?= $weight ?>
          </p>
          <p class="label">体重</p>
        </div>

        <div class="body-card">
          <div class="icon">💪</div>
          <p class="value">
            <?= $muscle_rate ?>
          </p>
          <p class="label">筋肉率</p>
        </div>

        <div class="body-card">
          <div class="icon">⚖️</div>
          <p class="value">
            <?= $fat_rate ?>
          </p>
          <p class="label">体脂肪率</p>
        </div>
      </div>
    </section>

    <!-- トレーニングレポート -->
    <section class="training-report">
      <div class="report-header">
        <h2>トレーニングレポート</h2>
        <span class="pro-badge">PRO</span>
      </div>

      <div class="report-content">
        <div class="report-item">🔥 トレーニング時間</div>
        <div class="report-item">📈 回数記録</div>
        <div class="report-item">🏆 継続日数</div>
      </div>
    </section>

  </div>

  <!-- 下部ナビ -->
  <nav class="app-nav">
    <a href="home.php" class="nav-item active">
      <span class="nav-item-icon">🏠</span> ホーム
    </a>
    <a href="../../training_screen_group/php/calendar.php" class="nav-item">
      <span class="nav-item-icon">💪</span> カレンダー
    </a>
    <a href="mypage.php" class="nav-item">
      <span class="nav-item-icon">👤</span> マイページ
    </a>
  </nav>

</body>
</html>
