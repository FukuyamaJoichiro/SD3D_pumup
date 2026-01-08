// ===== 状態管理 =====
let currentTrainingCard = null; // 操作対象のカード
let totalTimer = 0;
let totalInterval = null;

// ★追加：トレーニング開始状態 & 休憩タイマー
let workoutStarted = false;
let restTimer = 0;
let restIntervalId = null;
const REST_SECONDS = 60; // ← 休憩時間（秒）。必要ならここを変更

// ===== 初期化 =====
document.addEventListener('DOMContentLoaded', () => {
    initTrainingCards();
    initTimers();
    initTrainingMenu();
    initExchangeModal();
    initTabsAndDays();
    initUnsuperset();
});

// ===== 1. トレーニングカードの制御 =====
function initTrainingCards() {
    document.querySelectorAll('.training-card').forEach(card => {
        const typeIds = card.getAttribute('data-type-ids') || "";
        const config = {
            weight: typeIds.includes('1'),
            reps: typeIds.includes('1') || typeIds.includes('2'),
            duration: typeIds.includes('3')
        };

        addSet(card, config);

        card.querySelector('.add-set-btn').onclick = () => addSet(card, config);
        card.querySelector('.delete-set-btn').onclick = () => deleteLastSet(card);
    });
}

function addSet(card, config) {
    const container = card.querySelector('.sets-container');
    const count = container.querySelectorAll('.set-row').length;

    if (count === 0) {
        container.insertAdjacentHTML('beforeend', `
            <div class="set-header">
                <span>セット</span><span>kg</span><span>回数</span><span>完了</span>
            </div>`);
    }

const row = document.createElement('div');
    row.className = 'set-row';
    row.innerHTML = `
        <span class="set-label">${count + 1}</span>
        <input type="number" class="set-input weight-input" placeholder="0" step="0.5">
        <input type="number" class="set-input reps-input" placeholder="0">
        <button type="button" class="complete-btn" data-completed="false">未完了</button>
    `;

// --- ★DB更新ロジック (確実に動くように調整) ---
    const updateDB = async () => {
        const weightInput = row.querySelector('.weight-input');
        const repsInput = row.querySelector('.reps-input');
        
        const weight = weightInput.value || 0;
        const reps = repsInput.value || 0;
        const tid = card.dataset.trainingId;
        
        // カード内の全セット行から現在の行のインデックスを取得
        const allSetsInCard = Array.from(container.querySelectorAll('.set-row'));
        const setIndex = allSetsInCard.indexOf(row);

        // デバッグ用ログ (これがコンソールに出るか確認)
        console.log("Attempting to save:", {tid, weight, reps, setIndex, CURRENT_SESSION_ID});

        if (!CURRENT_SESSION_ID) {
            console.error("SESSION ID IS MISSING!");
            return;
        }

        const formData = new URLSearchParams();
        formData.append('training_id', tid);
        formData.append('weight', weight);
        formData.append('reps', reps);
        formData.append('set_index', setIndex);
        formData.append('session_id', CURRENT_SESSION_ID);

        try {
            const response = await fetch('update_workout_set.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            console.log("Server Response:", result);
        } catch (e) {
            console.error("Fetch Error:", e);
        }
    };

// 最も確実な 'onchange' と 'oninput' を直接指定
    const wIn = row.querySelector('.weight-input');
    const rIn = row.querySelector('.reps-input');
    
    wIn.onchange = updateDB;
    rIn.onchange = updateDB;
    wIn.oninput = updateDB;
    rIn.oninput = updateDB;

    // --- ここまで ---

    const btn = row.querySelector('.complete-btn');
    btn.onclick = () => {
        const done = btn.dataset.completed === 'true';

        // 状態更新（未完了⇄完了）
        btn.dataset.completed = (!done).toString();
        btn.textContent = !done ? '完了' : '未完了';
        btn.classList.toggle('completed', !done);

        // ★未完了→完了 かつ トレーニング開始中なら休憩タイマー自動スタート
        if (!done && workoutStarted) {
            startRestTimer(REST_SECONDS);
        }
    };

    container.appendChild(row);
}

function deleteLastSet(card) {
    const sets = card.querySelectorAll('.set-row');
    if (sets.length > 1) {
        sets[sets.length - 1].remove();
    } else if (sets.length === 1 && confirm('最後のセットを削除しますか？')) {
        card.querySelector('.sets-container').innerHTML = '';
    }
}

// ===== 2. トレーニングメニュー & 削除処理 =====
function initTrainingMenu() {
    const overlay = document.getElementById('training-menu-overlay');
    document.querySelectorAll('.menu-btn').forEach(btn => {
        btn.onclick = (e) => {
            e.stopPropagation();
            currentTrainingCard = btn.closest('.training-card');
            const nameEl = document.getElementById('menu-training-name');
            nameEl.textContent = currentTrainingCard.querySelector('.training-name').textContent;
            nameEl.setAttribute('data-training-id', currentTrainingCard.dataset.trainingId);
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        };
    });
    document.getElementById('menu-close-btn').onclick = closeMenu;
    overlay.onclick = (e) => { if (e.target === overlay) closeMenu(); };

    const supersetBtn = document.getElementById('menu-superset');
    if (supersetBtn) {
        supersetBtn.onclick = () => {
            if (!currentTrainingCard) return;
            const trainingId = currentTrainingCard.dataset.trainingId;
            closeMenu();
            location.href = `double_select.php?first_training_id=${trainingId}`;
        };
    }

    document.getElementById('menu-delete').onclick = async () => {
        if (!confirm('このトレーニング種目を今日の記録から削除しますか？')) return;
        if (!currentTrainingCard || !CURRENT_SESSION_ID) return;
        const tid = currentTrainingCard.dataset.trainingId;
        try {
            const res = await fetch('remove_training.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `training_id=${encodeURIComponent(tid)}&session_id=${encodeURIComponent(CURRENT_SESSION_ID)}`
            });
            const data = await res.json();
            if (data.success) {
                currentTrainingCard.remove();
                closeMenu();
                if (document.querySelectorAll('.training-card').length === 0) {
                    location.reload();
                }
            }
        } catch (e) { console.error(e); }
    };
}

function closeMenu() {
    document.getElementById('training-menu-overlay').classList.remove('active');
    document.body.style.overflow = '';
    currentTrainingCard = null;
}

function initUnsuperset() {
    const unsupersetBtn = document.getElementById('menu-unsuperset');
    if (!unsupersetBtn) return;
    unsupersetBtn.onclick = async () => {
        if (!currentTrainingCard) return;
        const trainingId = currentTrainingCard.dataset.trainingId;
        if (!confirm('スーパーセットを解除しますか？')) return;
        try {
            const res = await fetch('release_superset.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `training_id=${encodeURIComponent(trainingId)}`
            });
            if ((await res.json()).success) {
                closeMenu();
                location.reload();
            }
        } catch (e) { console.error(e); }
    };
}

// ===== 3. タイマー & トレーニング完了処理 =====
function initTimers() {
    const startBtn = document.querySelector('.start-btn');
    if (!startBtn) return;
    startBtn.onclick = () => {
        if (startBtn.textContent === 'トレーニングスタート') {
            startTraining(startBtn);
        } else if (confirm('トレーニングを終了しますか？')) {
            stopTraining(startBtn);
        }
    };
}

function startTraining(btn) {
    workoutStarted = true; // ★追加
    btn.textContent = 'トレーニング終了';
    btn.style.backgroundColor = '#666';

    totalInterval = setInterval(() => {
        totalTimer++;
        updateTimerDisplay('total-timer', totalTimer);
    }, 1000);
}

async function stopTraining(btn) {
    workoutStarted = false; // ★追加
    stopRestTimer();        // ★追加（休憩タイマー停止）
    clearInterval(totalInterval);
    try {
        const res = await fetch('save_workout_status.php', { method: 'POST' });
        if ((await res.json()).success) {
            window.location.href = 'calendar.php';
        }
    } catch (e) { window.location.href = 'calendar.php'; }
}

function updateTimerDisplay(id, sec) {
    const el = document.getElementById(id);
    if (!el) return;
    const m = Math.floor(sec / 60);
    const s = sec % 60;
    el.textContent = `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
}

// ===== ★追加：休憩タイマー =====
function startRestTimer(seconds = REST_SECONDS) {
    const restEl = document.getElementById('rest-timer');
    if (!restEl) return;

    // 既に動いてたら上書き
    stopRestTimer();

    restTimer = seconds;
    renderRestTimer(restTimer);

    restIntervalId = setInterval(() => {
        restTimer--;
        renderRestTimer(restTimer);

        if (restTimer <= 0) {
            stopRestTimer();
            // ここで音/通知などを追加したければ後から追加可能
        }
    }, 1000);
}

function stopRestTimer() {
    if (restIntervalId) {
        clearInterval(restIntervalId);
        restIntervalId = null;
    }
}

function renderRestTimer(sec) {
    const el = document.getElementById('rest-timer');
    if (!el) return;
    const m = Math.floor(sec / 60);
    const s = sec % 60;
    el.textContent = `${m}:${s.toString().padStart(2, '0')}`;
}

// ===== 4. UI切り替え =====
function initTabsAndDays() {
    const bind = (selector) => {
        document.querySelectorAll(selector).forEach(el => {
            el.onclick = () => {
                document.querySelectorAll(selector).forEach(i => i.classList.remove('active'));
                el.classList.add('active');
            };
        });
    };
    bind('.day-item');
    bind('.tab');
}

// ===== 5. 交換モーダル =====
function initExchangeModal() {
    const exchangeBtn = document.getElementById('menu-exchange');
    if (exchangeBtn) {
        exchangeBtn.onclick = () => {
            closeMenu();
            alert('交換機能は未実装です');
        };
    }
}