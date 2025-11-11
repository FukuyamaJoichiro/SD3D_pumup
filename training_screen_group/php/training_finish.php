<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>トレーニング完了</title>
    <link rel="stylesheet" href="training_finish.css">
</head>
<body>

    <div class="app-container">
        
        <header class="finish-header">
            </header>

        <main class="content-area">
            <div class="finish-message-container">
                <h1 class="today-date-info">
                    <span id="current-date-day"></span>
                </h1>
                
                <p class="greeting">今日もお疲れさまでした！</p>
                <p class="completion-message">トレーニング記録が完了しました</p>
                
                <div class="finish-illustration">
                    <img src="../tr_img/トレーニング完了.png" alt="トレーニング完了イラスト">
                </div>
            </div>
        </main>

        <footer class="finish-footer">
            <button class="confirm-button" onclick="location.href='training_record.php'">確認</button>
        </footer>
    </div>

    <script src="training_finish.js"></script>
</body>
</html>