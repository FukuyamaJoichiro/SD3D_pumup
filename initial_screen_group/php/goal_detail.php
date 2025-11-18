<?php
// データ処理部分をファイルの最上部に追加します
session_start();
// POSTリクエストがある（フォームが送信された）場合のみ、データ処理を実行
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // データベース接続ファイルの読み込み (★★★ パスを確認してください ★★★)
    require_once '../../db_connect.php'; 

    $user_id = $_SESSION['user_id'] ?? null;
    // HTMLフォームの name="customGoal" から入力された具体的な目標を取得
    $goal_detail = $_POST['customGoal'] ?? null; 

    if (!$user_id) {
        // ユーザーIDがない場合はエラー
        exit('エラー: ユーザー情報が見つかりません。最初からやり直してください。');
    }

    // 20文字以内のバリデーション (サーバーサイドチェック)
    if (mb_strlen($goal_detail, 'UTF-8') > 20) {
        exit('エラー: 具体的な目標は20文字以内で入力してください。');
    }

    // DBの goal_detail カラムを新しい値で上書き更新するSQL
    $sql = "UPDATE users SET goal_detail = :goal_detail WHERE user_id = :user_id";

    try {
        $stmt = $pdo->prepare($sql);
        
        // プレースホルダに値をバインドし実行
        $stmt->execute([
            ':goal_detail' => $goal_detail, // 入力された具体的な目標を格納
            ':user_id' => $user_id // 現在のユーザーIDを特定
        ]);
        
        // DB更新後、次の画面 training_count.html へリダイレクト
        // (★★★ HTMLファイルへのパスを適切に調整してください ★★★)
        header('Location: training_count.php'); 
        exit();

    } catch (PDOException $e) {
        // DBエラー処理
        error_log("DB Error: " . $e->getMessage()); 
        exit('エラー: 目標の詳細を登録中に問題が発生しました。');
    }
}
// POSTでない場合（直接アクセスされた場合）は、以下のHTMLが表示されます。
?>
<!DOCTYPE html>
<html lang="ja">
<head> 
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>目標詳細設定</title>
    <link rel="stylesheet" href="goal_detail.css">
</head>
<body>
    <div class="phone-screen">
        <div class="logo-area">
            <img src="../images/Gorifit.ロゴ.png" alt="GoriFit Logo" class="onboarding-logo">
        </div>

        <div class="header-content">
            <a href="goal_setting.php" class="back-button">&lt;</a> 
            <div class="progress-bar-container" style="width: 100%;">
                <div class="progress-fill" style="width: 60%;"></div>
            </div>
        </div>

        <div class="question-area">
            <h1>具体的に達成したい目標も<br>一緒に入力してください</h1>
        </div>

        <form id="goalDetailForm" class="goal-form" action="" method="post">
            
            <div class="option-container goal-options">
                <button type="button" class="goal-button goal-1 selected" data-goal="1">
                    <span class="goal-icon icon-body"></span> 
                    <div class="text-content">
                        <h2>理想のマッスルボディへ</h2>
                        <p>気合で頑張るしかない。</p>
                    </div>
                </button>
                
                <label for="custom-goal" class="custom-goal-label">私だけの目標 (選択)</label>
                
                <div class="input-container">
                    <textarea 
                        id="custom-goal" 
                        name="customGoal" 
                        class="custom-goal-input" 
                        placeholder="具体的に達成したい目標を20文字以内で入力してください"
                        maxlength="20"
                    ></textarea>
                    
                    <p class="char-counter"><span id="char-count">0</span>/20</p>
                </div>
                
            </div>

            <button type="submit" class="next-button">次へ</button>

        </form>
    </div>
    <script src="goal_detail.js"></script>
</body>
</html>