<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>インターバルタイマー</title>
    <link rel="stylesheet" href="interval_timer.css">
</head>
<body>

    <div class="app-container">

        <div class="timer-container">
            <a href="training_record.php" class="back-button">&lt;</a> 

            <div class="timer-display">
                <svg class="progress-ring" width="300" height="300">
                    <circle class="progress-ring-bg" stroke="#FFFFFF" stroke-width="8" fill="transparent" r="145" cx="150" cy="150" />
                    <circle class="progress-ring-bg-1" stroke="#FFFFFF" stroke-width="8" fill="transparent" r="145" cx="150" cy="150" />

                    <circle class="progress-ring-fg" stroke="#FFFFFF" stroke-width="8" fill="transparent" r="125" cx="150" cy="150" />
                    <circle class="progress-ring-fg-1" stroke="#FFFFFF" stroke-width="8" fill="transparent" r="125" cx="150" cy="150" />
                </svg>
                
                <div class="timer-info">
                    <p class="timer-label">総トレーニング時間</p>
                    <div class="timer-time" id="timer-time">03:00</div>
                    <button id="play-pause-button" class="play-pause-button">
                        <span class="play-icon">▶</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="settings-container">
            <div class="setting-item">
                <input type="number" id="hour-input" value="0" min="0" max="23">
                <label>hour</label>
            </div>
            <div class="setting-item">
                <input type="number" id="minute-input" value="3" min="0" max="59">
                <label>mintue</label>
            </div>
            <div class="setting-item">
                <input type="number" id="second-input" value="0" min="0" max="59">
                <label>second</label>
            </div>
        </div>
        
    </div>
    <script src="interval_timer.js"></script>
</body>
</html>