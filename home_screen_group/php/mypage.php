<?php
// mypage.php の上部
require_once("../../auth.php");
require_login('../../initial_screen_group/php/login.php');

// ------------------------------------------------
// MYボディデータの計算ロジック (統一された最新ロジック)
// ------------------------------------------------

$user_id = $_SESSION['user_id']; 
global $pdo; 

$weight_val = 0.0; // 計算用の数値変数
$height = 0.0;
$age = 0;
$body_fat_percentage = 0.0;
$muscle_percentage = 0.0;

try {
    // 性別(gender)を含めて取得
    $stmt = $pdo->prepare("SELECT weight, height, birthday, gender FROM users WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $db_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($db_data) {
        $weight_val = (float)($db_data['weight'] ?? 0.0);
        $height = (float)($db_data['height'] ?? 0.0);
        
        // 性別の変換 (男性=1, 女性=0)
        $gender_str = $db_data['gender'] ?? '男性';
        $gender_value = ($gender_str === '男性') ? 1 : 0;

        // 年齢の計算
        if (!empty($db_data['birthday'])) {
            $birthday = new DateTime($db_data['birthday']);
            $today_dt = new DateTime('today');
            $age = $birthday->diff($today_dt)->y;
        }

        // 体組成データの再計算 (Deurenbergの式)
        $height_m = $height / 100;
        $bmi = ($height_m > 0) ? $weight_val / ($height_m * $height_m) : 0; 
        
        if ($bmi > 0) {
            // 1. 体脂肪率の推定計算
            $calc_fat = (1.20 * $bmi) + (0.23 * $age) - (10.8 * $gender_value) - 5.4;
            $body_fat_percentage = max(5.0, min(50.0, round($calc_fat, 1)));

            // 2. 筋肉率の計算 (掛け算方式に統一)
            $lean_body_mass_percent = 100 - $body_fat_percentage;
            $calc_muscle = $lean_body_mass_percent * 0.5;

            // 異常値の制限 (上限を70.0に拡大)
            $muscle_percentage = max(10.0, min(70.0, round($calc_muscle, 1)));
        }
    } 
} catch (Exception $e) {
    error_log("マイページ計算エラー: " . $e->getMessage());
}

// ------------------------------------------------
// 表示用の値整形 (HTML側の変数名に合わせる)
// ------------------------------------------------

// 体重: $weight 変数を使用 (小数点第1位を表示)
$weight = ($weight_val > 0) ? htmlspecialchars(number_format($weight_val, 1)) : '-';

// 筋肉率: $muscle_rate 変数を使用
$muscle_rate = ($muscle_percentage > 0) 
    ? htmlspecialchars(number_format($muscle_percentage, 1) . '%') 
    : '-';

// 体脂肪率: $fat_rate 変数を使用
$fat_rate = ($body_fat_percentage > 0) 
    ? htmlspecialchars(number_format($body_fat_percentage, 1) . '%') 
    : '-';
?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>マイページ</title>

  <link rel="stylesheet" href="mypage_style.css">
</head>

<body>
  <div class="container">

    <section class="profile-section">
      <div class="profile-info">
        <p class="profile-register">
          プロフィール編集 <a href="profile.php" class="edit-icon">✏️</a>
        </p>
        <p class="sub-text">自分の記録を保存して下さい</p>
      </div>
    </section>

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

  <nav class="app-nav">
    <a href="home.php" class="nav-item">
      <span class="nav-item-icon">🏠</span> ホーム
    </a>
    <a href="../../training_screen_group/php/calendar.php" class="nav-item">
      <span class="nav-item-icon">💪</span> カレンダー
    </a>
    <a href="mypage.php" class="nav-item active">
      <span class="nav-item-icon">👤</span> マイページ
    </a>
  </nav>

</body>
</html>