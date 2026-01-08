// ===== 状態管理 =====
let currentTrainingCard = null; // 操作対象のカード
let totalTimer = 0;
let totalInterval = null;

// ===== 初期化 =====
document.addEventListener('DOMContentLoaded', () => {
    initTrainingCards();
    initTimers();
    initTrainingMenu();
    initExchangeModal();
    initTabsAndDays();
    initUnsuperset(); // ← ★追加
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

        // 最初の1セットを自動追加
        addSet(card, config);

        // セット追加・削除ボタン
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
        <input type="number" class="set-input" placeholder="0" step="0.5">
        <input type="number" class="set-input" placeholder="0">
        <button type="button" class="complete-btn" data-completed="false">未完了</button>
    `;

    const btn = row.querySelector('.complete-btn');
    btn.onclick = () => {
        const done = btn.dataset.completed === 'true';
        btn.dataset.completed = (!done).toString();
        btn.textContent = !done ? '完了' : '未完了';
        btn.classList.toggle('completed', !done);
    };

    row.querySelectorAll('.set-input').forEach(i => {
        i.onfocus = () => i.select();
    });

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

    // スーパーセット
    const supersetBtn = document.getElementById('menu-superset');
    if (supersetBtn) {
        supersetBtn.onclick = () => {
            if (!currentTrainingCard) return;
            const trainingId = currentTrainingCard.dataset.trainingId;
            closeMenu();
            location.href = `double_select.php?first_training_id=${trainingId}`;
        };
    }

    // 削除
    document.getElementById('menu-delete').onclick = async () => {
        if (!confirm('このトレーニング種目を今日の記録から削除しますか？')) return;

        if (!currentTrainingCard || !CURRENT_SESSION_ID) {
            alert('セッション情報の取得に失敗しました。');
            return;
        }

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

                const remaining = document.querySelectorAll('.training-card');
                if (remaining.length === 0) {
                    location.reload();
                } else {
                    remaining.forEach((card, i) => {
                        const num = card.querySelector('.training-number');
                        if (num) num.textContent = `${i + 1}種`;
                    });
                }
            } else {
                alert(data.message || '削除に失敗しました');
            }
        } catch (e) {
            console.error(e);
            alert('通信エラーが発生しました。');
        }
    };
}

function closeMenu() {
    document.getElementById('training-menu-overlay').classList.remove('active');
    document.body.style.overflow = '';
    currentTrainingCard = null;
}

// ===== ★ 追加：スーパーセット解除 =====
function initUnsuperset() {
    const unsupersetBtn = document.getElementById('menu-unsuperset');
    if (!unsupersetBtn) return;

    unsupersetBtn.onclick = async () => {
        if (!currentTrainingCard) return;

        const trainingId = currentTrainingCard.dataset.trainingId;
        if (!trainingId) return;

        if (!confirm('スーパーセットを解除しますか？')) return;

        try {
            const res = await fetch('release_superset.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `training_id=${encodeURIComponent(trainingId)}`
            });

            const data = await res.json();

            if (data.success) {
                closeMenu();
                location.reload(); // ← 点線を消すため再読込
            } else {
                alert(data.message || '解除に失敗しました');
            }
        } catch (e) {
            console.error(e);
            alert('通信エラーが発生しました');
        }
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
    btn.textContent = 'トレーニング終了';
    btn.style.backgroundColor = '#666';
    totalInterval = setInterval(() => {
        totalTimer++;
        updateTimerDisplay('total-timer', totalTimer);
    }, 1000);
}

async function stopTraining(btn) {
    clearInterval(totalInterval);

    try {
        const res = await fetch('save_workout_status.php', { method: 'POST' });
        const data = await res.json();

        if (data.success) {
            window.location.href = 'calendar.php';
        } else {
            alert('データの保存に失敗しました。');
            btn.textContent = 'トレーニングスタート';
            btn.style.backgroundColor = '#ff6b6b';
        }
    } catch (e) {
        console.error(e);
        window.location.href = 'calendar.php';
    }
}

function updateTimerDisplay(id, sec) {
    const el = document.getElementById(id);
    if (!el) return;
    const m = Math.floor(sec / 60);
    const s = sec % 60;
    el.textContent = `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
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
