<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../../auth.php';
require_login();
require_once __DIR__ . '/../../db_connect.php';

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header("Location: ../../initial_screen_group/php/login.php");
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT user_id, user_name, gender, weight, height, birthday
        FROM users
        WHERE user_id = :id
        LIMIT 1
    ");
    $stmt->bindValue(":id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $user = [
            'user_name' => '未登録',
            'gender'    => '-',
            'weight'    => '-',
            'height'    => '-',
            'birthday'  => '-',
        ];
    }
} catch (PDOException $e) {
    die("DBエラー: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>マイページ | GoriFit</title>
<style>
/* ===== 背景中央寄せ設定 ===== */
body {
  margin: 0;
  padding: 0;
  background: #eaeaea;
  display: flex;
  justify-content: center;
  min-height: 100vh;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  color: #222;
}

/* ===== アプリ全体コンテナ ===== */
.app {
  width: 100%;
  max-width: 420px;
  min-height: 100vh;
  background: #fff;
  display: flex;
  flex-direction: column;
  position: relative;
}

/* ===== ヘッダー ===== */
header {
  display: flex;
  align-items: center;
  background: #f2f2f2;
  padding: 14px;
  border-bottom: 1px solid #ddd;
}
header .back {
  font-size: 20px;
  margin-right: 10px;
  cursor: pointer;
}
header h1 {
  font-size: 16px;
  font-weight: 600;
  margin: 0;
}

/* ===== メイン内容 ===== */
main {
  padding: 10px 16px 80px; /* ナビの高さ分余白 */
  flex: 1;
  overflow-y: auto;
}

/* ===== プロフィールリスト ===== */
.info-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #ddd;
  padding: 14px 0;
  font-size: 15px;
}
.info-item span.label {
  color: #555;
}
.info-item span.value {
  font-weight: 500;
}

/* ===== ユーザーID行 ===== */
.user-id {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #ddd;
  padding: 14px 0;
  font-size: 14px;
}
.user-id input {
  width: 80%;
  border: none;
  background: transparent;
  font-size: 14px;
  color: #333;
}
.copy-btn {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 18px;
}

/* ===== ボトムナビ ===== */
nav {
  display: flex;
  justify-content: space-around;
  align-items: center;
  border-top: 1px solid #ddd;
  background: #fff;
  height: 60px;
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  border-radius: 0 0 12px 12px;
  box-shadow: 0 -2px 8px rgba(0,0,0,0.05);
}

nav a {
  text-decoration: none;
  color: #999;
  text-align: center;
  font-size: 12px;
}
nav a.active {
  color: #ff6e6e;
}
nav span {
  display: block;
  font-size: 20px;
  margin-bottom: 2px;
}
</style>

<script>
function copyUserId() {
  const userId = document.getElementById('userId');
  userId.select();
  document.execCommand('copy');
  alert('ユーザーIDをコピーしました');
}
</script>
</head>
<body>
<div class="app">
  <!-- ヘッダー -->
  <header>
    <div class="back" onclick="history.back()">&#x2039;</div>
    <h1>マイページ</h1>
  </header>

  <!-- メイン -->
  <main>
    <div class="info-item"><span class="label">ニックネーム</span><span class="value"><?= htmlspecialchars($user['user_name']) ?></span></div>
    <div class="info-item"><span class="label">性別</span><span class="value"><?= htmlspecialchars($user['gender']) ?></span></div>
    <div class="info-item"><span class="label">体重</span><span class="value"><?= htmlspecialchars($user['weight']) ?> kg</span></div>
    <div class="info-item"><span class="label">身長</span><span class="value"><?= htmlspecialchars($user['height']) ?> cm</span></div>
    <div class="info-item"><span class="label">生年月日</span><span class="value"><?= htmlspecialchars($user['birthday']) ?></span></div>

    <div class="user-id">
      <input id="userId" type="text" readonly value="<?= htmlspecialchars($user['user_id']) ?>">
      <button class="copy-btn" onclick="copyUserId()">📋</button>
    </div>
  </main>

  <!-- ナビ -->
  <nav>
    <a href="../../home_screen_group/php/home.php">
      <span>🏠</span>ホーム
    </a>
    <a href="../../training_screen_group/php/calendar.php">
      <span>📅</span>カレンダー
    </a>
    <a href="#" class="active">
      <span>👤</span>マイページ
    </a>
  </nav>
</div>
</body>
</html>
