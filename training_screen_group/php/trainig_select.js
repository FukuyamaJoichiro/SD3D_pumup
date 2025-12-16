console.log('### UPDATED training_select.js loaded ###');

// ===== 初期化 =====
document.addEventListener('DOMContentLoaded', function () {
    initializeTrainingCards();
    initializeTimers();
    initializeTrainingMenu();
    initializeExchangeModal();
});

// ============================
// トレーニングカード
// ============================
function initializeTrainingCards() {
    const cards = document.querySelectorAll('.training-card');

    cards.forEach(card => {
        const typeIds = card.getAttribute('data-type-ids');
        const hasWeight   = typeIds && typeIds.includes('1');
        const hasReps     = typeIds && (typeIds.includes('1') || typeIds.includes('2'));
        const hasDuration = typeIds && typeIds.includes('3');

        addSet(card, hasWeight, hasReps, hasDuration);

        const addBtn = card.querySelector('.add-set-btn');
        if (addBtn) {
            addBtn.addEventListener('click', () => {
                addSet(card, hasWeight, hasReps, hasDuration);
            });
        }

        const deleteBtn = card.querySelector('.delete-set-btn');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', () => {
                deleteLastSet(card);
            });
        }
    });
}

// ============================
// セット関連
// ============================
function addSet(card, hasWeight, hasReps, hasDuration) {
    const container = card.querySelector('.sets-container');
    const setCount = container.querySelectorAll('.set-row').length;

    if (setCount === 0) {
        const header = document.createElement('div');
        header.className = 'set-header';
        header.innerHTML = `
            <span>セット</span>
            <span>kg</span>
            <span>回数</span>
            <span>完了</span>
        `;
        container.appendChild(header);
    }

    const setRow = document.createElement('div');
    setRow.className = 'set-row';

    setRow.innerHTML = `
        <span class="set-label">${setCount + 1}</span>
        <input type="number" class="set-input" placeholder="0" step="0.5">
        <input type="number" class="set-input" placeholder="0">
        <button type="button" class="complete-btn">未完了</button>
    `;

    container.appendChild(setRow);

    const completeBtn = setRow.querySelector('.complete-btn');
    if (completeBtn) {
        completeBtn.addEventListener('click', () => {
            completeBtn.classList.toggle('completed');
            completeBtn.textContent =
                completeBtn.classList.contains('completed') ? '完了' : '未完了';
        });
    }
}

function deleteLastSet(card) {
    const container = card.querySelector('.sets-container');
    const sets = container.querySelectorAll('.set-row');

    if (sets.length > 1) {
        sets[sets.length - 1].remove();
    } else if (sets.length === 1 && confirm('最後のセットを削除しますか？')) {
        sets[0].remove();
        const header = container.querySelector('.set-header');
        if (header) header.remove();
    }
}

// ============================
// タイマー
// ============================
let totalTimer = 0;
let totalInterval = null;

function initializeTimers() {
    const startBtn = document.querySelector('.start-btn');
    if (!startBtn) return;

    startBtn.addEventListener('click', () => {
        if (!totalInterval) {
            startBtn.textContent = 'トレーニング終了';
            totalInterval = setInterval(() => {
                totalTimer++;
            }, 1000);
        } else if (confirm('トレーニングを終了しますか？')) {
            clearInterval(totalInterval);
            totalInterval = null;
            startBtn.textContent = 'トレーニングスタート';
        }
    });
}

// ============================
// トレーニングメニュー（︙）
// ============================
let currentTrainingCard = null;

function initializeTrainingMenu() {
    const overlay  = document.getElementById('training-menu-overlay');
    const closeBtn = document.getElementById('menu-close-btn');
    if (!overlay || !closeBtn) return;

    document.querySelectorAll('.menu-btn').forEach(btn => {
        btn.addEventListener('click', e => {
            e.stopPropagation();
            currentTrainingCard = btn.closest('.training-card');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    });

    closeBtn.addEventListener('click', closeTrainingMenu);

    overlay.addEventListener('click', e => {
        if (e.target === overlay) closeTrainingMenu();
    });

    // ===== 変更なし：入れ替え =====
    const exchangeBtn = document.getElementById('menu-exchange');
    if (exchangeBtn) {
        exchangeBtn.addEventListener('click', () => {
            closeTrainingMenu();
            openExchangeModal();
        });
    }

    // ===== ★ここが重要：削除（通信対応） =====
    const deleteBtn = document.getElementById('menu-delete');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', async () => {
            if (!currentTrainingCard) return;
            if (!confirm('削除しますか？')) return;

            const trainingId = currentTrainingCard.dataset.trainingId;

            try {
                const res = await fetch('delete_training.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        training_id: trainingId
                    })
                });

                const data = await res.json();

                if (!data.success) {
                    alert(data.message || '削除に失敗しました');
                    return;
                }

                // 成功したらDOM削除
                currentTrainingCard.remove();
                closeTrainingMenu();

            } catch (e) {
                console.error(e);
                alert('通信エラーが発生しました');
            }
        });
    }
}

function closeTrainingMenu() {
    const overlay = document.getElementById('training-menu-overlay');
    if (!overlay) return;
    overlay.classList.remove('active');
    document.body.style.overflow = '';
    currentTrainingCard = null;
}

// ============================
// トレーニング交換モーダル
// ============================
let selectedExchangeTrainingId = null;
let allTrainings = [];

function initializeExchangeModal() {
    const overlay     = document.getElementById('exchange-modal-overlay');
    const cancelBtn   = document.getElementById('exchange-cancel-btn');
    const confirmBtn  = document.getElementById('exchange-confirm-btn');
    const searchInput = document.getElementById('exchange-search');

    if (!overlay) return;

    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeExchangeModal);
    }

    overlay.addEventListener('click', e => {
        if (e.target === overlay) closeExchangeModal();
    });

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            filterExchangeList(searchInput.value.toLowerCase());
        });
    }

    if (confirmBtn) {
        confirmBtn.addEventListener('click', () => {
            if (selectedExchangeTrainingId && currentTrainingCard) {
                exchangeTraining(
                    currentTrainingCard.dataset.trainingId,
                    selectedExchangeTrainingId
                );
            }
        });
    }
}

function openExchangeModal() {
    const overlay = document.getElementById('exchange-modal-overlay');
    if (!overlay) return;
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeExchangeModal() {
    const overlay = document.getElementById('exchange-modal-overlay');
    if (!overlay) return;
    overlay.classList.remove('active');
    document.body.style.overflow = '';
    selectedExchangeTrainingId = null;
}

function filterExchangeList(term) {
    const filtered = allTrainings.filter(t =>
        t.training_name.toLowerCase().includes(term)
    );
    console.log(filtered);
}

function exchangeTraining(oldId, newId) {
    console.log('exchange', oldId, newId);
    closeExchangeModal();
}
