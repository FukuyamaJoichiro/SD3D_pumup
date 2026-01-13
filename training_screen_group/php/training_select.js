// ===== 状態管理 =====
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
