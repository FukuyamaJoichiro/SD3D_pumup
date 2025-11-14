<?php
// データ処理部分をファイルの最上部に追加します
session_start();

// POSTリクエストがある（フォームが送信された）場合のみ、データ処理を実行
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // データベース接続ファイルの読み込み
    require_once '../../db_connect.php'; 

    $user_id = $_SESSION['user_id'] ?? null;
    $goal_level = $_POST['selected_goal'] ?? null;

    if (!$user_id) {
        exit('ユーザー情報が見つかりません。最初からやり直してください。');
    }

    if (empty($goal_level)) {
        // エラー処理（本来はフォームの下にメッセージを表示すべき）
        exit('目標が選択されていません。');
    }

    // DBの goal カラムを更新するSQL
    $sql = "UPDATE users SET goal = :goal_level WHERE user_id = :user_id";

    try {
        $stmt = $pdo->prepare($sql);
        
        $stmt->execute([
            ':goal_level' => $goal_level, // goalカラムにレベルを格納
            ':user_id' => $user_id  // どのユーザーを更新するか指定
        ]);
        
        // DB更新後、次の画面へリダイレクト
        header('Location: fetch_goal_data.php');
        exit();

    } catch (PDOException $e) {
        exit('目標の登録中にエラーが発生しました: ' . $e->getMessage());
    }
}
// POSTでない場合（直接アクセスされた場合）は、以下のHTMLが表示されます。
?>
<!DOCTYPE html>
<html lang="ja">
<head> 
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>目標設定</title>
    <link rel="stylesheet" href="goal_setting.css">
</head>
<body>
    <div class="phone-screen">
        <div class="logo-area">
            <img src="../images/Gorifit.ロゴ.png" alt="GoriFit Logo" class="onboarding-logo">
        </div>

        <div class="header-content">
            <a href="training_experience.php" class="back-button">&lt;</a>
            <div class="progress-bar-container" style="width: 100%;">
                <div class="progress-fill" style="width: 60%;"></div>
            </div>
        </div>

        <div class="question-area">
            <h1>どんな目標を達成したいですか？</h1>
            <p class="sub-text">目標はいつでも自由に変更できます</p>
        </div>

        <form id="goalForm" class="goal-form" action="" method="post">
            <input type="hidden" name="selected_goal" id="selectedGoalInput" value="">
        
            <div class="option-container goal-options">
                <button type="button" class="goal-button goal-1" data-goal="1">
                    <span class="goal-icon icon-body"></span> 
                    <div class="text-content">
                        <h2>理想のマッスルボディへ</h2>
                        <p>気合で頑張るしかない。</p>
                    </div>
                </button>

                <button type="button" class="goal-button goal-2" data-goal="2">
                    <span class="goal-icon icon-strength"></span> 
                    <div class="text-content">
                        <h2>ストレングス (筋力) をつける</h2>
                        <p>誰よりも強い力を。</p>
                    </div>
                </button>

                <button type="button" class="goal-button goal-3" data-goal="3">
                    <span class="goal-icon icon-ability"></span> 
                    <div class="text-content">
                        <h2>身体能力を向上させる</h2>
                        <p>様々な運動をうまくこなせるよう。</p>
                    </div>
                </button>

                <button type="button" class="goal-button goal-4" data-goal="4">
                    <span class="goal-icon icon-stamina"></span> 
                    <div class="text-content">
                        <h2>体力をつける</h2>
                        <p>疲れないように体力を。</p>
                    </div>
                </button>
            </div>

            <button type="submit" class="next-button">次へ</button>

        </form>
    </div>
    <script src="goal_setting.js"></script>
</body>
</html>