<?php
// データ処理部分をファイルの最上部に追加します
session_start();

// POSTリクエストがある（フォームが送信された）場合のみ、データ処理を実行
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // HTMLフォームの name="experience_level" から選択された値を取得
    if (isset($_POST['experience_level'])) {
        $selected_level = $_POST['experience_level'];
        
        // 選択されたレベルをセッション変数に保持
        $_SESSION['training_level'] = $selected_level; 
        
        // 次の画面（goal_setting.html）にリダイレクト (パスを調整してください)
        header('Location: goal_setting.php'); 
        exit;
        
    } else {
        // エラー処理（本来はフォームの下にメッセージを表示すべき）
        // 現時点では、統合後の次の画面が 'goal_setting.php' なので、そちらへリダイレクトするように修正しました
        echo "エラー: トレーニングレベルが送信されていません。";
    }
}
// POSTでない場合（直接アクセスされた場合）は、以下のHTMLが表示されます。
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>トレーニング経験</title>
    <link rel="stylesheet" href="training_experience.css">
</head>
<body>
    <div class="phone-screen">
        <div class="logo-area">
            <img src="../images/Gorifit.ロゴ.png" alt="GoriFit Logo" class="onboarding-logo">
        </div>
        
        <div class="header-content">
            <a href="bodydata_register.php" class="back-button">&lt;</a>
            <div class="progress-bar-container" style="width: 100%;">
                <div class="progress-fill" style="width: 40%;"></div>
            </div>
        </div>

        <div class="question-area">
            <h1>トレーニングの経験はありますか？</h1>
            <p class="sub-text">ご自身の考えで選択して下さい。</p>
        </div>

        <form id="experienceForm" class="experience-form" action="" method="post">
            <input type="hidden" name="experience_level" id="experienceLevelInput" value="">
            
            <div class="option-container">
                <button type="button" class="level-button level-1" data-level="1">
                    <span class="level-badge">Lv.1</span> 初めてです。
                </button>
                <button type="button" class="level-button level-2" data-level="2">
                    <span class="level-badge">Lv.2</span> トレーニングの経験はあるが、<br>まだ初心者だ。
                </button>
                <button type="button" class="level-button level-3" data-level="3">
                    <span class="level-badge">Lv.3</span> 少しずつ慣れてきている。
                </button>
                <button type="button" class="level-button level-4" data-level="4">
                    <span class="level-badge">Lv.4</span> どんなトレーニングでも自信がある。
                </button>
            </div>

            <button type="submit" class="next-button">次へ</button>

        </form>
    </div>
    <script src="training_experience.js"></script>
</body>
</html>