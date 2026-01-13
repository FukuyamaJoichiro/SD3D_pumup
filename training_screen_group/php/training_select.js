/**
 * training_select.js (完全機能復旧版)
 */

// ===== 状態管理 =====
<<<<<<< Updated upstream
let currentTrainingCard = null;

// ===== 初期化 =====
document.addEventListener('DOMContentLoaded', () => {
  initTrainingMenu();
  initSupersetModal();
  initUnsuperset();
  initDelete();
});

// ===== メニュー表示 =====
function initTrainingMenu() {
  const overlay = document.getElementById('training-menu-overlay');
  if (!overlay) return;

  document.querySelectorAll('.menu-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      currentTrainingCard = btn.closest('.training-card');

      const nameEl = document.getElementById('menu-training-name');
      if (nameEl && currentTrainingCard) {
        nameEl.textContent = currentTrainingCard.querySelector('.training-name')?.textContent ?? '';
      }

      overlay.classList.add('active');
      document.body.style.overflow = 'hidden';
    });
  });

  document.getElementById('menu-close-btn')?.addEventListener('click', closeMenu);

  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) closeMenu();
  });
}

function closeMenu() {
  const overlay = document.getElementById('training-menu-overlay');
  overlay?.classList.remove('active');
  document.body.style.overflow = '';
  currentTrainingCard = null;
}

// ===== スーパーセット（チェック画面） =====
function initSupersetModal() {
  const openBtn = document.getElementById('menu-superset');
  const overlay = document.getElementById('superset-overlay');
  if (!openBtn || !overlay) return;

  const backBtn = document.getElementById('superset-back');
  const closeBtn = document.getElementById('superset-close');
  const confirmBtn = document.getElementById('superset-confirm');

  const cbs = Array.from(overlay.querySelectorAll('.superset-cb'));
  const tags = () => Array.from(overlay.querySelectorAll('.superset-tag'));

  openBtn.addEventListener('click', () => {
    if (!currentTrainingCard) return;

    // 初期化
    cbs.forEach(cb => cb.checked = false);
    tags().forEach(t => t.textContent = '');

    // 1つ目（今選択中）を自動チェック
    const firstId = currentTrainingCard.dataset.trainingId;
    const firstCb = cbs.find(cb => cb.value === firstId);
    if (firstCb) firstCb.checked = true;

    refreshTags();

    closeMenu();
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
  });

  const closeSuperset = () => {
    overlay.classList.remove('active');
=======
let currentTrainingCard = null; // 操作対象のカード
let totalTimer = 0;
let totalInterval = null;

// トレーニング開始状態 & 休憩タイマー
let workoutStarted = false;
let restTimer = 0;
let restIntervalId = null;
const REST_SECONDS = 60; 

// ===== 初期化 =====
document.addEventListener('DOMContentLoaded', () => {
    initTrainingCards();
    initTimers();
    initTrainingMenu(); // 💡 削除・三点リーダー制御
    initInfoButton();   // 💡 ⓘマーク詳細制御
    initExchangeModal();
    initTabsAndDays();  // 💡 日付遷移制御
    initUnsuperset();   // 💡 スーパーセット解除制御
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

        const addBtn = card.querySelector('.add-set-btn');
        const delBtn = card.querySelector('.delete-set-btn');
        
        if (addBtn) addBtn.onclick = () => addSet(card, config);
        if (delBtn) delBtn.onclick = () => deleteLastSet(card);
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

    const updateDB = async () => {
        const weightInput = row.querySelector('.weight-input');
        const repsInput = row.querySelector('.reps-input');
        const weight = weightInput.value || 0;
        const reps = repsInput.value || 0;
        const tid = card.dataset.trainingId;
        const allSetsInCard = Array.from(container.querySelectorAll('.set-row'));
        const setIndex = allSetsInCard.indexOf(row);

        if (!CURRENT_SESSION_ID) return;

        const formData = new URLSearchParams();
        formData.append('training_id', tid);
        formData.append('weight', weight);
        formData.append('reps', reps);
        formData.append('set_index', setIndex);
        formData.append('session_id', CURRENT_SESSION_ID);

        try {
            await fetch('update_workout_set.php', { method: 'POST', body: formData });
        } catch (e) { console.error("Fetch Error:", e); }
    };

    row.querySelector('.weight-input').onchange = updateDB;
    row.querySelector('.reps-input').onchange = updateDB;

    const btn = row.querySelector('.complete-btn');
    btn.onclick = (e) => {
        e.stopPropagation();
        const done = btn.dataset.completed === 'true';
        btn.dataset.completed = (!done).toString();
        btn.textContent = !done ? '完了' : '未完了';
        btn.classList.toggle('completed', !done);
        if (!done && workoutStarted) startRestTimer(REST_SECONDS);
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

// ===== 2. 三点リーダーメニュー (削除など) 制御 =====
function initTrainingMenu() {
    const overlay = document.getElementById('training-menu-overlay');
    if (!overlay) return;

    // document全体で監視 (確実な検知)
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.menu-btn');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            currentTrainingCard = btn.closest('.training-card');
            const nameEl = document.getElementById('menu-training-name');
            if (nameEl && currentTrainingCard) {
                nameEl.textContent = currentTrainingCard.querySelector('.training-name').textContent;
            }
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }, true);

    const closeBtn = document.getElementById('menu-close-btn');
    if (closeBtn) closeBtn.onclick = closeMenu;
    overlay.onclick = (e) => { if (e.target === overlay) closeMenu(); };

    const deleteBtn = document.getElementById('menu-delete');
    if (deleteBtn) {
        deleteBtn.onclick = async () => {
            if (!currentTrainingCard || !confirm('このトレーニング種目を今日の記録から削除しますか？')) return;
            const tid = currentTrainingCard.dataset.trainingId;
            try {
                const res = await fetch('remove_training.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `training_id=${encodeURIComponent(tid)}&session_id=${encodeURIComponent(CURRENT_SESSION_ID)}`
                });
                if ((await res.json()).success) {
                    currentTrainingCard.remove();
                    closeMenu();
                    location.reload();
                }
            } catch (e) { console.error(e); }
        };
    }
}

// iマーク (詳細表示) 制御
function initInfoButton() {
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.info-btn');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            const tid = btn.dataset.trainingId;
            if (typeof showTrainingDetail === 'function') {
                showTrainingDetail(tid);
            }
        }
    }, true);
}

function closeMenu() {
    const overlay = document.getElementById('training-menu-overlay');
    if (overlay) overlay.classList.remove('active');
>>>>>>> Stashed changes
    document.body.style.overflow = '';
  };

  backBtn?.addEventListener('click', closeSuperset);
  closeBtn?.addEventListener('click', closeSuperset);
  overlay.addEventListener('click', (e) => { if (e.target === overlay) closeSuperset(); });

  // 最大2つまで
  cbs.forEach(cb => {
    cb.addEventListener('change', () => {
      const checked = getChecked();
      if (checked.length > 2) cb.checked = false;
      refreshTags();
    });
  });

  confirmBtn?.addEventListener('click', async () => {
    const ids = getChecked();
    if (ids.length !== 2) {
      alert('スーパーセットにする種目を2つ選んでね');
      return;
    }

    // ★ ここでDB保存APIを叩く（create_superset.php）
    // 旧版には CURRENT_SESSION_ID が無いので date だけ送る。新しい版なら session_id も送ってOK。
    try {
      const res = await fetch('create_superset.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          date: SELECTED_DATE,
          session_id: CURRENT_SESSION_ID ?? '',
          a_training_id: ids[0],
          b_training_id: ids[1]
        })
      });

      const data = await res.json().catch(() => null);
      if (!res.ok || (data && data.success === false)) {
        alert((data && data.message) ? data.message : 'スーパーセット作成に失敗しました');
        return;
      }

      closeSuperset();
      location.reload();
    } catch (e) {
      console.error(e);
      alert('通信エラーが発生しました');
    }
  });

  function getChecked() {
    return cbs.filter(cb => cb.checked).map(cb => cb.value);
  }

  function refreshTags() {
    tags().forEach(t => t.textContent = '');
    const checked = cbs.filter(cb => cb.checked);
    checked.forEach((cb, idx) => {
      const row = cb.closest('label');
      const tag = row?.querySelector('.superset-tag');
      if (tag) tag.textContent = idx === 0 ? 'A' : 'B';
    });
  }
}

// ===== スーパーセット解除 =====
function initUnsuperset() {
  const btn = document.getElementById('menu-unsuperset');
  if (!btn) return;

<<<<<<< Updated upstream
  btn.addEventListener('click', async () => {
    if (!currentTrainingCard) return;
    if (!confirm('スーパーセットを解除しますか？')) return;

    const trainingId = currentTrainingCard.dataset.trainingId;

    try {
      const res = await fetch('release_superset.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          training_id: trainingId,
          date: SELECTED_DATE,
          session_id: CURRENT_SESSION_ID ?? ''
        })
      });

      const data = await res.json().catch(() => null);
      if (!res.ok || (data && data.success === false)) {
        alert((data && data.message) ? data.message : '解除に失敗しました');
        return;
      }

      closeMenu();
      location.reload();
    } catch (e) {
      console.error(e);
      alert('通信エラーが発生しました');
    }
  });
=======
// ===== 3. タイマー & 完了処理 =====
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
    workoutStarted = true;
    btn.textContent = 'トレーニング終了';
    btn.style.backgroundColor = '#666';
    totalInterval = setInterval(() => {
        totalTimer++;
        updateTimerDisplay('total-timer', totalTimer);
    }, 1000);
}

async function stopTraining(btn) {
    workoutStarted = false;
    stopRestTimer();
    clearInterval(totalInterval);
    const formData = new URLSearchParams();
    formData.append('date', SELECTED_DATE);
    try {
        await fetch('save_workout_status.php', { method: 'POST', body: formData });
        window.location.href = 'calendar.php';
    } catch (e) { window.location.href = 'calendar.php'; }
>>>>>>> Stashed changes
}

// ===== 削除 =====
function initDelete() {
  const btn = document.getElementById('menu-delete');
  if (!btn) return;

  btn.addEventListener('click', async () => {
    if (!currentTrainingCard) return;
    if (!confirm('この種目を削除しますか？')) return;

    const trainingId = currentTrainingCard.dataset.trainingId;

    try {
      const res = await fetch('remove_training.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          training_id: trainingId,
          date: SELECTED_DATE,
          session_id: CURRENT_SESSION_ID ?? ''
        })
      });

      const data = await res.json().catch(() => null);
      if (!res.ok || (data && data.success === false)) {
        alert((data && data.message) ? data.message : '削除に失敗しました');
        return;
      }

      closeMenu();
      location.reload();
    } catch (e) {
      console.error(e);
      alert('通信エラーが発生しました');
    }
  });
}
<<<<<<< Updated upstream
=======

function renderRestTimer(sec) {
    const el = document.getElementById('rest-timer');
    if (!el) return;
    const m = Math.floor(sec / 60);
    const s = sec % 60;
    el.textContent = `${m}:${s.toString().padStart(2, '0')}`;
}

// ===== 4. UI遷移 (日付・タブ) =====
function initTabsAndDays() {
    const dayItems = document.querySelectorAll('.day-item');
    dayItems.forEach((el, index) => {
        el.onclick = () => {
            const baseDate = new Date(SELECTED_DATE);
            const dayOfWeek = baseDate.getDay();
            const clickedDate = new Date(baseDate);
            clickedDate.setDate(baseDate.getDate() - dayOfWeek + index);
            const y = clickedDate.getFullYear();
            const m = (clickedDate.getMonth() + 1).toString().padStart(2, '0');
            const d = clickedDate.getDate().toString().padStart(2, '0');
            const dateStr = `${y}-${m}-${d}`;
            window.location.href = `training_select.php?date=${dateStr}`;
        };
    });

    const tabs = document.querySelectorAll('.tab');
    tabs.forEach(el => {
        el.onclick = () => {
            tabs.forEach(i => i.classList.remove('active'));
            el.classList.add('active');
        };
    });
}

// ===== 5. 交換機能 =====
function initExchangeModal() {
    const exchangeBtn = document.getElementById('menu-exchange');
    if (exchangeBtn) {
        exchangeBtn.onclick = () => {
            closeMenu();
            alert('交換機能は未実装です');
        };
    }
}
>>>>>>> Stashed changes
