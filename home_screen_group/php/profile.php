<?php
require_once("../../auth.php");
require_login('../../initial_screen_group/php/login.php');

// 仮ログイン（ログイン後は不要）
//$_SESSION['user_id'] = 100;

$user_id = (int)$_SESSION['user_id']; 

// 最新ユーザー情報取得
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :id");
$stmt->bindValue(":id", $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
    'user_name' => '',
    'gender'    => '',
    'weight'    => '',
    'height'    => '',
    'birth'     => '',
    'user_id'   => $user_id,
];
?>


<?php
// 仮のユーザーデータ（本来はDBから取得）
/*$user = [
    'nickname' => '',
    'gender' => '男性',
    'weight' => '65kg',
    'height' => '165cm',
    'birth' => '2004-01-01',
    'user_id' => 'srgeYide7GacthVcs57Dht7'
];*/
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>マイページ</title>
    <link rel="stylesheet" href="profile_style.css">
</head>
<body>
<div class="app-container">

    <!-- プロフィール情報 -->
    <div class="profile-container">
        <div class="icon-area">
            <div class="circle-icon"></div>
        </div>

        <!-- 🔽 ニックネーム押下で画面遷移 -->
        <div class="info-box" onclick="location.href='nickname_setting.php'">
            <label>ニックネーム</label>
            <div class="value"><?= htmlspecialchars($user['user_name']) ?: '未設定' ?></div>
        </div>

        <div class="info-box">
            <label>性別</label>
            <div class="value"><?= htmlspecialchars($user['gender']) ?></div>
        </div>

        <div class="info-box">
            <label>体重</label>
            <div class="value"><?= htmlspecialchars($user['weight']) ?></div>
        </div>

        <div class="info-box">
            <label>身長</label>
            <div class="value"><?= htmlspecialchars($user['height']) ?></div>
        </div>

        <div class="info-box">
            <label>生年月日</label>
            <div class="value"><?= htmlspecialchars($user['birth']) ?></div>
        </div>

        <div class="info-box">
            <div class="value id-box">
                <?= htmlspecialchars($user['user_id']) ?>
                <button class="copy-btn" onclick="copyText('<?= $user['user_id'] ?>')">📋</button>
            </div>
        </div>
    </div>

    <!-- 下部ナビ -->
    <nav class="app-nav">
        <a href="home.php" class="nav-item active">
            <span class="nav-item-icon">🏠</span> ホーム
        </a>
        <a href="calendar.php" class="nav-item">
            <span class="nav-item-icon">💪</span> カレンダー
        </a>
        <a href="mypage.php" class="nav-item">
            <span class="nav-item-icon">👤</span> マイページ
        </a>
    </nav>

    <script>
        function copyText(text) {
            navigator.clipboard.writeText(text);
            alert("コピーしました！");
        }
    </script>

</body>
</html>
