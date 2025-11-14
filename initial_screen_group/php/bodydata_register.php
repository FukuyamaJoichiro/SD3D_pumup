<?php
// データ処理部分をファイルの最上部に追加します
session_start();

// POSTリクエストがある（フォームが送信された）場合のみ、データ処理を実行
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // データベース接続ファイルの読み込み
    require_once '../../db_connect.php';

    $weight = $_POST['weight'] ?? null;
    $height = $_POST['height'] ?? null;
    $birthday = $_POST['birthday'] ?? null;
    $gender = $_POST['gender'] ?? null;
    $email = $_POST['email'] ?? null;
    $password = $_POST['password'] ?? null;

    // フォームにない項目はnullで初期化
    $user_name = null;
    $muscle_ptc = null;
    $bodyfat_ptc = null;
    $goal_detail = null;


    if (empty($weight) || empty($height) || empty($birthday) || empty($gender) || empty($email) || empty($password)) {
        // エラー処理（ここでは一旦 exit していますが、実際にはフォームの下にエラーメッセージを表示するのが親切です）
        exit('必須項目（体重、身長、生年月日、性別、メールアドレス、パスワード）が入力されていません。');
    }

    $_SESSION['original_password_length'] = mb_strlen($password);

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (
                user_name, birthday, gender, height, weight, muscle_ptc, bodyfat_ptc, goal_detail,
                email,password
            ) VALUES (
                :user_name, :birthday, :gender, :height, :weight, :muscle_ptc, :bodyfat_ptc, :goal_detail,:email, :password
            )";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_name' => $user_name,
            ':birthday' => $birthday,
            ':gender' => $gender,
            ':height' => $height,
            ':weight' => $weight,
            ':muscle_ptc' => $muscle_ptc,
            ':bodyfat_ptc' => $bodyfat_ptc,
            ':goal_detail' => $goal_detail,
            ':email' => $email,
            ':password' => $hashed_password,
        ]);
        $user_id = $pdo->lastInsertId();
        $_SESSION['user_id'] = $user_id;
        
        // 処理成功後、次のページへ遷移
        header('Location: training_experience.php');
        exit();
    } catch (PDOException $e) {
        // SQL実行失敗時の処理
        exit('データ登録中にエラーが発生しました: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>トレーニングを開始</title>
    <link rel="stylesheet" href="bodydata_register.css">
</head>
<body>
    <div class="phone-screen">
        <div class="logo-area">
            <img src="../images/Gorifit.ロゴ.png" alt="GoriFit Logo" class="onboarding-logo">
        </div>

        <div class="progress-bar-container" style="width: 100%;">
            <div class="progress-fill"></div>
        </div>

        <div class="text-area">
            <h1>トレーニングを始めるために<br>必要な情報を入力してください</h1>
        </div>

        <form id="initialForm" class="full-width-form" 
              action="" 
              method="post">
            
            <label for="weight">体重<span class="required">●</span></label>
            <input type="number" id="weight" name="weight" placeholder="例 : 55 kg" inputmode="numeric" required>
            
            <label for="height">身長<span class="required">●</span></label>
            <input type="number" id="height" name="height" placeholder="例 : 160 cm" inputmode="numeric" required>
            
            <label for="birthday">生年月日<span class="required">●</span></label>
            <input type="date" id="birthday" name="birthday" placeholder="例 : 2024-08-12" required>

            <label for="email">メールアドレス<span class="required">●</span></label>
            <input type="email" id="email" name="email" placeholder="例 : yourname@example.com" required>

            <label for="password">パスワード<span class="required">●</span></label>
            <input type="password" id="password" name="password" placeholder="8文字以上の英数字" required minlength="8">

            <fieldset>
                <legend>性別<span class="required">●</span></legend>
                <div class="gender-options">
                    <label><input type="radio" name="gender" value="男性" checked> 男性</label>
                    <label><input type="radio" name="gender" value="女性"> 女性</label>
                </div>
            </fieldset>

            <button type="submit" class="next-button">次へ</button>
        </form>
    </div>
</body>
</html>