<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>トレーニング記録</title>
    <link rel="stylesheet" href="training_rest.css">
</head>
<body>

    <div class="app-container">
        
        <header class="calendar-header">
            <div class="header-top">
                <a href="calendar.php" class="back-button">&lt;</a>
                <h1 class="month-title">9月</h1>
            </div>
            
            <div class="week-days">
                <div class="day-item day-sun">日<span class="date">9</span></div>
                <div class="day-item">月<span class="date">10</span></div>
                <div class="day-item day-fire active">火<span class="date">11</span></div>
                <div class="day-item">水<span class="date">12</span></div>
                <div class="day-item">木<span class="date">13</span></div>
                <div class="day-item">金<span class="date">14</span></div>
                <div class="day-item day-sat">土<span class="date">15</span></div>
            </div>
            
            <nav class="tab-menu">
                <a href="#" class="tab-item active">トレーニング記録</a>
                <a href="#" class="tab-item">ボディデータ</a>
                <div class="tab-indicator"></div>
            </nav>
        </header>

        <main class="content-area">
            <div class="rest-container">
                <div class="rest-illustration">
                    <img src="../tr_img/おやすみ画面.png" alt="トレーニングおやすみイラスト">
                </div>
                
                <p class="rest-message">トレーニングおやすみ U_U</p>
                <a href="training_record.php" class="cancel-link">キャンセル</a>
            </div>
        </main>
    </div>

    <script src="training_rest.js"></script>
</body>
</html>