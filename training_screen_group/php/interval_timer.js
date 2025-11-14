// --- DOM要素の取得 ---
const timerTimeEl = document.getElementById('timer-time');
const playPauseButton = document.getElementById('play-pause-button');
const hourInput = document.getElementById('hour-input');
const minuteInput = document.getElementById('minute-input');
const secondInput = document.getElementById('second-input');

// ★変更/追加: 4つのリングのDOM要素を取得（HTMLとCSSのクラス名に合わせて修正）
const outerProgressRing = document.querySelector('.progress-ring-bg-1'); // 大きいリングの経過 (.bg-1)
const innerProgressRing = document.querySelector('.progress-ring-fg-1'); // 小さいリングの経過 (.fg-1)
const settingItems = document.querySelectorAll('.setting-item'); // 設定アイテムのコンテナ


// --- グローバル変数 ---
let totalTimeInSeconds = 180; // 初期値: 3分 = 180秒
let timeRemaining = totalTimeInSeconds;
let timerInterval = null;
let isRunning = false;

// ★変更/追加: 2つの異なる半径と円周を定義
const outerRadius = 145; // 大きい円の半径 (bg, bg-1)
const outerCircumference = 2 * Math.PI * outerRadius;

const innerRadius = 125; // 小さい円の半径 (fg, fg-1)
const innerCircumference = 2 * Math.PI * innerRadius;


// CSS変数の初期設定 (円周のセットアップ)
// ★変更: 2つの経過リングにそれぞれ円周を設定
outerProgressRing.style.strokeDasharray = `${outerCircumference} ${outerCircumference}`;
outerProgressRing.style.strokeDashoffset = 0;

innerProgressRing.style.strokeDasharray = `${innerCircumference} ${innerCircumference}`;
innerProgressRing.style.strokeDashoffset = 0;


// --- 関数定義 ---

/**
 * 残り秒数を「MM:SS」形式の文字列に変換する
 */
function formatTime(totalSeconds) {
    if (totalSeconds < 0) totalSeconds = 0;
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;
    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
}

/**
 * プログレスバー（円）の表示を更新する
 * ★変更: 2つのリングのオフセットを同時に更新
 */
function updateProgress() {
    // 経過率を計算
    const normalizedTime = totalTimeInSeconds > 0 ? timeRemaining / totalTimeInSeconds : 0;
    
    // 1. 外側リング (.bg-1) のオフセット計算と適用
    const outerOffset = outerCircumference * (1 - normalizedTime);
    outerProgressRing.style.strokeDashoffset = outerOffset;

    // 2. 内側リング (.fg-1) のオフセット計算と適用
    const innerOffset = innerCircumference * (1 - normalizedTime);
    innerProgressRing.style.strokeDashoffset = innerOffset;
}

/**
 * タイマー表示、ボタン、プログレスバーをリセットする
 */
function resetTimer() {
    // 設定された時間を秒に変換して初期化
    const hours = parseInt(hourInput.value) || 0;
    const minutes = parseInt(minuteInput.value) || 0;
    const seconds = parseInt(secondInput.value) || 0;
    
    const newTotalTime = (hours * 3600) + (minutes * 60) + seconds;
    
    if (!isRunning) {
        totalTimeInSeconds = newTotalTime;
        timeRemaining = totalTimeInSeconds;
    }

    timerTimeEl.textContent = formatTime(timeRemaining);
    playPauseButton.innerHTML = `<span class="play-icon">▶</span>`;
    updateProgress();
}

/**
 * 1秒ごとに実行されるタイマーのロジック
 */
function tick() {
    if (timeRemaining <= 0) {
        clearInterval(timerInterval);
        isRunning = false;
        timeRemaining = 0;
        timerTimeEl.textContent = "00:00"; 
        playPauseButton.innerHTML = `<span class="play-icon">▶</span>`;
        updateProgress(); // 完全にリングを消す
        return;
    }

    timeRemaining--;
    timerTimeEl.textContent = formatTime(timeRemaining);
    updateProgress();
}

/**
 * 再生/一時停止ボタンのイベントハンドラ
 */
function handlePlayPause() {
    if (isRunning) {
        // 一時停止 (Pause)
        clearInterval(timerInterval);
        isRunning = false;
        playPauseButton.innerHTML = `<span class="play-icon">▶</span>`;
    } else {
        // 再生 (Play)
        // タイマーが完全に停止している場合は時間を再設定
        if (timeRemaining === 0 || timeRemaining === totalTimeInSeconds) {
            resetTimer(); // 時間設定の値を読み込む
            if (totalTimeInSeconds <= 0) return; // 0秒以下なら実行しない
        }

        isRunning = true;
        playPauseButton.innerHTML = `<span class="pause-icon">||</span>`; // 一時停止アイコン
        timerInterval = setInterval(tick, 1000); // 1秒ごとにtickを実行
    }
}

/**
 * 指定された単位の入力値を1つ増やす (クリックインクリメント機能)
 */
function incrementSetting(unit) {
    let inputEl;
    let maxVal;
    let rollover = true;

    switch (unit) {
        case 'hour':
            inputEl = hourInput;
            maxVal = 23;
            break;
        case 'minute':
            inputEl = minuteInput;
            maxVal = 59;
            break;
        case 'second':
            inputEl = secondInput;
            maxVal = 59;
            break;
        default:
            return;
    }

    let currentValue = parseInt(inputEl.value) || 0;
    let newValue = currentValue + 1;

    // 最大値に達したら0に戻す
    if (newValue > maxVal && rollover) {
        newValue = 0;
    } else if (newValue > maxVal && !rollover) {
        newValue = maxVal;
    }
    
    inputEl.value = newValue;
    
    // 値が変更されたらタイマーをリセット
    if (!isRunning) {
        resetTimer();
    }
}


// --- イベントリスナーの追加 ---

// 再生/一時停止ボタン
playPauseButton.addEventListener('click', handlePlayPause);

// ★追加: 設定エリア全体をクリックしたときの処理 (インクリメント機能)
settingItems.forEach(item => {
    item.addEventListener('click', (event) => {
        const inputEl = item.querySelector('input');
        
        if (inputEl) {
            const unit = inputEl.id.split('-')[0];
            incrementSetting(unit);
            
            // 再生中の場合はタイマーを停止し、新しい値を反映
            if (isRunning) {
                clearInterval(timerInterval);
                isRunning = false;
                playPauseButton.innerHTML = `<span class="play-icon">▶</span>`;
                resetTimer();
            }
        }
    });
});

// 設定入力欄が変更されたらタイマー表示をリセット
[hourInput, minuteInput, secondInput].forEach(input => {
    input.addEventListener('change', () => {
        if (!isRunning) {
            resetTimer();
        }
    });
});


// 初期ロード時にタイマーを一度リセットして初期表示を行う
document.addEventListener('DOMContentLoaded', resetTimer);