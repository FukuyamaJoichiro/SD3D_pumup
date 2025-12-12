<?php
require_once("../../auth.php");
require_login('../../initial_screen_group/php/login.php');

// 仮ログイン（ログイン後は不要）
// ログイン済みのため $_SESSION['user_id'] を利用

// ユーザー情報取得
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :id");
$stmt->bindValue(":id", $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
// 表示用の値整形
$weight = isset($user['weight']) && $user['weight'] !== '' ? htmlspecialchars($user['weight']) : '-';
$muscle_rate = isset($user['muscle_rate']) && $user['muscle_rate'] !== '' ? htmlspecialchars($user['muscle_rate']) : '-';
$fat_rate = isset($user['fat_rate']) && $user['fat_rate'] !== '' ? htmlspecialchars($user['fat_rate']) : '-';
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
          プロフィール登録 <a href="profile.php" class="edit-icon">✏️</a>
        </p>
        <p class="sub-text">自分の記録を保存して下さい</p>
        <button class="upgrade-btn">アップグレードする</button>
      </div>
    </section>

    <!-- MYボディデータ -->
    <section class="body-data">
      <h2>MYボディデータ</h2>

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
