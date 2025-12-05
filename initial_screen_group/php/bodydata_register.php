<?php
// データ処理部分をファイルの最上部に追加します
session_start();

// エラーメッセージ用変数とフラグを定義
$email_error_message = ''; 
$is_duplicate_error = false; 

// POSTリクエストがある（フォームが送信された）場合のみ、データ処理を実行
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // データベース接続ファイルの読み込み
    // 接続情報（$pdo）がこのファイル（../../db_connect.php）で定義されている前提です
    require_once '../../db_connect.php';

    // フォームデータの受け取り
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


    // 必須項目チェック (ここでは簡略化。本来はより詳細なバリデーションが必要)
    if (empty($weight) || empty($height) || empty($birthday) || empty($gender) || empty($email) || empty($password)) {
        // 全ての必須項目が入力されていない場合は処理を中断し、画面表示へ進む
        // または、ここでエラーメッセージを設定し、フォームに表示することも可能です。
    } else {
        // --- 【重要】メールアドレス重複チェック ---
        try {
            $check_sql = "SELECT COUNT(*) FROM users WHERE email = :email";
            $check_stmt = $pdo->prepare($check_sql);
            // プリペアドステートメントでSQLインジェクションを防止
            $check_stmt->execute([':email' => $email]);
            $count = $check_stmt->fetchColumn();

            if ($count > 0) {
                // **重複あり**
                $is_duplicate_error = true;
                $email_error_message = 'このメールアドレスは既に登録されています！';
                // INSERT処理はスキップし、このままHTMLの表示に進みます
            } else {
                // **重複なし -> 新規登録処理**
                
                $_SESSION['original_password_length'] = mb_strlen($password);
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $sql = "INSERT INTO users (
                            user_name, birthday, gender, height, weight, muscle_ptc, bodyfat_ptc, goal_detail,
                            email,password
                        ) VALUES (
                            :user_name, :birthday, :gender, :height, :weight, :muscle_ptc, :bodyfat_ptc, :goal_detail,:email, :password
                        )";

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
            }
        } catch (PDOException $e) {
            // SQL実行失敗時の処理
            // 本番環境ではエラーメッセージは出さず、ログに記録すべきです
            exit('データベースエラーが発生しました。時間をおいて再度お試しください。');
        }
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

    <div id="duplicateModal" class="modal-overlay <?php if ($is_duplicate_error) { echo 'active'; } ?>">
        <div class="modal-content">
            <div class="modal-icon">🚨</div>
            <h2>登録できませんでした</h2>
            <p><?php echo htmlspecialchars($email_error_message); ?></p>
            <button class="modal-close-button">OK</button>
        </div>
    </div>

    <script src="bodydata_register.js"></script>
    
</body>
</html>