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

/* ===============================
   ★ プロフィール変更処理（追加）
   =============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $newName   = trim($_POST['new_name'] ?? "");
    $newGender = trim($_POST['new_gender'] ?? "");
    $newWeight = trim($_POST['new_weight'] ?? "");
    $newHeight = trim($_POST['new_height'] ?? "");

    $stmt = $pdo->prepare("
        UPDATE users 
        SET 
            user_name = :name,
            gender = :gender,
            weight = :weight,
            height = :height
        WHERE user_id = :id
    ");
    $stmt->bindValue(":name", $newName, PDO::PARAM_STR);
    $stmt->bindValue(":gender", $newGender, PDO::PARAM_STR);
    $stmt->bindValue(":weight", $newWeight, PDO::PARAM_INT);
    $stmt->bindValue(":height", $newHeight, PDO::PARAM_INT);
    $stmt->bindValue(":id", $user_id, PDO::PARAM_INT);

    $stmt->execute();

    header("Location: mypage.php");
    exit;
}

/* ===============================
   DB: ユーザー情報取得
   =============================== */
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

<link rel="stylesheet" href="profile_style.css">

</head>
<body>
<div class="app">

<header>
  <div class="back" onclick="history.back()">&#x2039;</div>
  <h1>マイページ</h1>
</header>

<main>

<!-- ========== ニックネーム ========== -->
<div class="info-item">
  <span class="label">ニックネーム</span>
  <span class="value">
    <?= htmlspecialchars($user['user_name'] ?? '', ENT_QUOTES, 'UTF-8');?>
    <button class="ed it-btn" onclick="toggleEdit('editName')">変更</button>
  </span>
</div>
<div id="editName" class="edit-area">
  <form method="post">
    <input type="text" name="new_name" value="<?= htmlspecialchars($user['user_name']) ?>">
    <input type="hidden" name="new_gender" value="<?= htmlspecialchars($user['gender']) ?>">
    <input type="hidden" name="new_weight" value="<?= htmlspecialchars($user['weight']) ?>">
    <input type="hidden" name="new_height" value="<?= htmlspecialchars($user['height']) ?>">
    <button class="submit-btn">保存</button>
  </form>
</div>

<!-- ========== 性別 ========== -->
<div class="info-item">
  <span class="label">性別</span>
  <span class="value">
    <?= htmlspecialchars($user['gender']) ?>
    <button class="edit-btn" onclick="toggleEdit('editGender')">変更</button>
  </span>
</div>
<div id="editGender" class="edit-area">
  <form method="post">
    <select name="new_gender">
      <option value="男性" <?= $user['gender']=='男性'?'selected':'' ?>>男性</option>
      <option value="女性" <?= $user['gender']=='女性'?'selected':'' ?>>女性</option>
      <option value="その他" <?= $user['gender']=='その他'?'selected':'' ?>>その他</option>
    </select>

    <input type="hidden" name="new_name" value="<?= htmlspecialchars($user['user_name']) ?>">
    <input type="hidden" name="new_weight" value="<?= htmlspecialchars($user['weight']) ?>">
    <input type="hidden" name="new_height" value="<?= htmlspecialchars($user['height']) ?>">

    <button class="submit-btn">保存</button>
  </form>
</div>

<!-- ========== 体重 ========== -->
<div class="info-item">
  <span class="label">体重</span>
  <span class="value">
    <?= htmlspecialchars($user['weight']) ?> kg
    <button class="edit-btn" onclick="toggleEdit('editWeight')">変更</button>
  </span>
</div>
<div id="editWeight" class="edit-area">
  <form method="post">
    <input type="number" name="new_weight" value="<?= htmlspecialchars($user['weight']) ?>">

    <input type="hidden" name="new_name" value="<?= htmlspecialchars($user['user_name']) ?>">
    <input type="hidden" name="new_gender" value="<?= htmlspecialchars($user['gender']) ?>">
    <input type="hidden" name="new_height" value="<?= htmlspecialchars($user['height']) ?>">

    <button class="submit-btn">保存</button>
  </form>
</div>

<!-- ========== 身長 ========== -->
<div class="info-item">
  <span class="label">身長</span>
  <span class="value">
    <?= htmlspecialchars($user['height']) ?> cm
    <button class="edit-btn" onclick="toggleEdit('editHeight')">変更</button>
  </span>
</div>
<div id="editHeight" class="edit-area">
  <form method="post">
    <input type="number" name="new_height" value="<?= htmlspecialchars($user['height']) ?>">

    <input type="hidden" name="new_name" value="<?= htmlspecialchars($user['user_name']) ?>">
    <input type="hidden" name="new_gender" value="<?= htmlspecialchars($user['gender']) ?>">
    <input type="hidden" name="new_weight" value="<?= htmlspecialchars($user['weight']) ?>">

    <button class="submit-btn">保存</button>
  </form>
</div>

<!-- ========== 生年月日（編集なし） ========== -->
<div class="info-item">
  <span class="label">生年月日</span>
  <span class="value"><?= htmlspecialchars($user['birthday']) ?></span>
</div>

<!-- ===== IDコピー ===== -->
<div class="user-id">
  <input id="userId" type="text" readonly value="<?= htmlspecialchars($user['user_id']) ?>">
  <button class="copy-btn" onclick="copyUserId()">📋</button>
</div>

</main>

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

</div>
</body>
</html>