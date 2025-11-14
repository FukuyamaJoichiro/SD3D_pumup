<?php
// データ処理部分をファイルの最上部に追加します
session_start();

// POSTリクエストがある（フォームが送信された）場合のみ、データ処理を実行
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // HTMLフォームの name="training_count" から選択された値を取得
    $training_count = $_POST['training_count'] ?? null; 

    if (empty($training_count)) {
        // エラー処理（本来はフォームの下にメッセージを表示すべき）
        exit('エラー: トレーニング頻度が選択されていません。');
    }

    // 選択されたトレーニング頻度をセッション変数に保持
    $_SESSION['training_frequency'] = $training_count;

    // ★★★ 登録内容確認画面へリダイレクト ★★★
    // (次の画面へのパスを調整してください)
    header('Location: bodydate_view.php'); 
    exit();
}
// POSTでない場合（直接アクセスされた場合）は、以下のHTMLが表示されます。
?>
<!DOCTYPE html>
<html lang="ja">
<head> 
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>トレーニング頻度設定</title>
    <link rel="stylesheet" href="training_count.css">
</head>
<body>
    <div class="phone-screen">
        <div class="logo-area">
            <img src="../images/Gorifit.ロゴ.png" alt="GoriFit Logo" class="onboarding-logo">
        </div>

        <div class="header-content">
            <a href="goal_detail.php" class="back-button">&lt;</a> 
            <div class="progress-bar-container" style="width: 100%;">
                <div class="progress-fill" style="width: 80%;"></div> 
            </div>
        </div>

        <div class="question-area">
            <h1>1週間に何回トレーニングすることが<br>できますか？</h1>
            <p class="sub-text">現実的に可能な回数を選択してください。</p>
        </div>

        <form id="trainingCountForm" class="goal-form" action="" method="post">
            
            <div class="option-container count-options">
                <div class="recommend-badge">
                    <span class="fire-emoji">👍</span> おすすめ
                </div>
                
                <div class="slider-container">
                    
                    <div class="options-row">
                        <label class="choice-label">
                            <input type="radio" name="training_count" value="1" class="radio-dot">
                            <span class="dot-visual"></span>
                        </label>
                        <label class="choice-label selected-choice"> <input type="radio" name="training_count" value="2" class="radio-dot" checked>
                            <span class="dot-visual"></span>
                        </label>
                        <label class="choice-label">
                            <input type="radio" name="training_count" value="3" class="radio-dot">
                            <span class="dot-visual"></span>
                        </label>
                        <label class="choice-label">
                            <input type="radio" name="training_count" value="4" class="radio-dot">
                            <span class="dot-visual"></span>
                        </label>
                        <label class="choice-label">
                            <input type="radio" name="training_count" value="5" class="radio-dot">
                            <span class="dot-visual"></span>
                        </label>
                        <label class="choice-label">
                            <input type="radio" name="training_count" value="6" class="radio-dot">
                            <span class="dot-visual"></span>
                        </label>
                        <label class="choice-label">
                            <input type="radio" name="training_count" value="7" class="radio-dot">
                            <span class="dot-visual"></span>
                        </label>
                    </div>

                    <div class="number-labels">
                        <span>1</span>
                        <span>2</span>
                        <span>3</span>
                        <span>4</span>
                        <span>5</span>
                        <span>6</span>
                        <span>7</span>
                    </div>

                </div>
            </div>

            <button type="submit" class="next-button">次へ</button>

        </form>
    </div>
</body>
</html>