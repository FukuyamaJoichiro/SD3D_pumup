// ===== 初期化 =====
document.addEventListener('DOMContentLoaded', function() {
    initializeTrainingCards();
    initializeTimers();
    initializeTrainingMenu();
});

// トレーニングカードの初期化
function initializeTrainingCards() {
    const cards = document.querySelectorAll('.training-card');
    
    cards.forEach(card => {
        const typeIds = card.getAttribute('data-type-ids');
        const hasWeight = typeIds && typeIds.includes('1');
        const hasReps = typeIds && (typeIds.includes('1') || typeIds.includes('2'));
        const hasDuration = typeIds && typeIds.includes('3');
        
        // 最初のセットを追加
        addSet(card, hasWeight, hasReps, hasDuration);
        
        // セット追加ボタン
        const addBtn = card.querySelector('.add-set-btn');
        addBtn.addEventListener('click', () => {
            addSet(card, hasWeight, hasReps, hasDuration);
        });
        
        // セット削除ボタン
        const deleteBtn = card.querySelector('.delete-set-btn');
        deleteBtn.addEventListener('click', () => {
            deleteLastSet(card);
        });
    });
}

// セットを追加
function addSet(card, hasWeight, hasReps, hasDuration) {
    const container = card.querySelector('.sets-container');
    const setCount = container.querySelectorAll('.set-row').length;
    
    // ヘッダーが存在しない場合は追加
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
    
    const setNumber = setCount + 1;
    
    setRow.innerHTML = `
        <span class="set-label">${setNumber}</span>
        <input type="number" class="set-input" placeholder="0" step="0.5" data-type="weight">
        <input type="number" class="set-input" placeholder="0" data-type="reps">
        <button type="button" class="complete-btn" data-completed="false">未完了</button>
    `;
    
    container.appendChild(setRow);
    
    // 入力欄にフォーカスイベントを追加
    const inputs = setRow.querySelectorAll('.set-input');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.select();
        });
    });
    
    // 完了ボタンのイベント
    const completeBtn = setRow.querySelector('.complete-btn');
    completeBtn.addEventListener('click', function() {
        const isCompleted = this.getAttribute('data-completed') === 'true';
        if (isCompleted) {
            this.setAttribute('data-completed', 'false');
            this.textContent = '未完了';
            this.classList.remove('completed');
        } else {
            this.setAttribute('data-completed', 'true');
            this.textContent = '完了';
            this.classList.add('completed');
        }
    });
}

// 最後のセットを削除
function deleteLastSet(card) {
    const container = card.querySelector('.sets-container');
    const sets = container.querySelectorAll('.set-row');
    
    if (sets.length > 1) {
        sets[sets.length - 1].remove();
    } else if (sets.length === 1) {
        if (confirm('最後のセットを削除しますか？')) {
            sets[0].remove();
            const header = container.querySelector('.set-header');
            if (header) header.remove();
        }
    }
}

// ===== タイマー機能 =====
let restTimer = 0;
let totalTimer = 0;
let restInterval = null;
let totalInterval = null;

function initializeTimers() {
    const startBtn = document.querySelector('.start-btn');
    
    if (startBtn) {
        startBtn.addEventListener('click', function() {
            if (this.textContent === 'トレーニングスタート') {
                startTraining();
                this.textContent = 'トレーニング終了';
                this.style.backgroundColor = '#666';
            } else {
                if (confirm('トレーニングを終了しますか？')) {
                    stopTraining();
                    this.textContent = 'トレーニングスタート';
                    this.style.backgroundColor = '#ff6b6b';
                }
            }
        });
    }
}

function startTraining() {
    // 通算時間を開始
    totalInterval = setInterval(() => {
        totalTimer++;
        updateTimerDisplay('total-timer', totalTimer);
    }, 1000);
}

function stopTraining() {
    // すべてのタイマーを停止
    if (totalInterval) {
        clearInterval(totalInterval);
        totalInterval = null;
    }
    if (restInterval) {
        clearInterval(restInterval);
        restInterval = null;
    }
    
    // データを保存する処理をここに追加
    saveWorkoutData();
}

function updateTimerDisplay(elementId, seconds) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    const minutes = Math.floor(seconds / 60);
    const secs = seconds % 60;
    
    if (elementId === 'rest-timer') {
        element.textContent = `${minutes}:${secs.toString().padStart(2, '0')}`;
    } else {
        element.textContent = `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
}

// ワークアウトデータを保存
function saveWorkoutData() {
    const cards = document.querySelectorAll('.training-card');
    const workoutData = [];
    
    cards.forEach(card => {
        const trainingId = card.getAttribute('data-training-id');
        const sets = card.querySelectorAll('.set-row');
        const trainingName = card.querySelector('.training-name').textContent;
        
        const setsData = [];
        sets.forEach((set, index) => {
            const inputs = set.querySelectorAll('.set-input:not(.hidden)');
            const setData = {
                set_number: index + 1,
                weight: inputs[0] && !inputs[0].classList.contains('hidden') ? inputs[0].value : null,
                reps: inputs[1] && !inputs[1].classList.contains('hidden') ? inputs[1].value : null,
                duration: inputs[2] && !inputs[2].classList.contains('hidden') ? inputs[2].value : null
            };
            setsData.push(setData);
        });
        
        workoutData.push({
            training_id: trainingId,
            training_name: trainingName,
            sets: setsData
        });
    });
    
    const memo = document.querySelector('.memo-input');
    const sessionData = {
        trainings: workoutData,
        memo: memo ? memo.value : '',
        total_time: totalTimer
    };
    
    console.log('保存データ:', sessionData);
    
    // サーバーに送信する処理をここに追加
    // fetch('save_workout.php', {
    //     method: 'POST',
    //     headers: {
    //         'Content-Type': 'application/json',
    //     },
    //     body: JSON.stringify(sessionData)
    // })
    // .then(response => response.json())
    // .then(data => {
    //     if (data.success) {
    //         alert('トレーニングを保存しました！');
    //         location.href = 'home.php';
    //     }
    // });
}

// ===== 日付選択 =====
document.querySelectorAll('.day-item').forEach(item => {
    item.addEventListener('click', function() {
        document.querySelectorAll('.day-item').forEach(i => i.classList.remove('active'));
        this.classList.add('active');
    });
});

// ===== タブ切り替え =====
document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        
        // タブに応じたコンテンツ切り替え処理をここに追加
    });
});

// ===== トレーニングメニュー =====
let currentTrainingCard = null;

function initializeTrainingMenu() {
    const overlay = document.getElementById('training-menu-overlay');
    const closeBtn = document.getElementById('menu-close-btn');
    
    // すべての︙ボタンにイベントを設定
    document.querySelectorAll('.menu-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const card = this.closest('.training-card');
            const trainingName = card.querySelector('.training-name').textContent;
            currentTrainingCard = card;
            
            // モーダルのタイトルを更新
            document.getElementById('menu-training-name').textContent = trainingName;
            
            // モーダルを表示
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    });
    
    // 閉じるボタン
    closeBtn.addEventListener('click', closeTrainingMenu);
    
    // オーバーレイクリックで閉じる
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            closeTrainingMenu();
        }
    });
    
    // メニュー項目のイベント
    document.getElementById('menu-exchange').addEventListener('click', function() {
        alert('トレーニング交換機能（未実装）');
        closeTrainingMenu();
    });
    
    document.getElementById('menu-superset').addEventListener('click', function() {
        alert('スーパーセット機能（未実装）');
        closeTrainingMenu();
    });
    
    document.getElementById('menu-delete').addEventListener('click', function() {
        if (confirm('このトレーニングを削除しますか？')) {
            if (currentTrainingCard) {
                const trainingId = currentTrainingCard.getAttribute('data-training-id');
                
                // モーダルを閉じる
                closeTrainingMenu();
                
                // セッションから削除（サーバー側で処理）
                fetch('remove_training.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `training_id=${trainingId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // カードを画面から削除
                        currentTrainingCard.remove();
                        
                        // 残りのトレーニングカードを確認
                        const remainingCards = document.querySelectorAll('.training-card');
                        
                        if (remainingCards.length === 0) {
                            // 全て削除された場合、ページをリロードして空の状態を表示
                            location.reload();
                        } else {
                            // 残りがある場合は番号を振り直す
                            remainingCards.forEach((card, index) => {
                                const numberSpan = card.querySelector('.training-number');
                                if (numberSpan) {
                                    numberSpan.textContent = `${index + 1}種`;
                                }
                            });
                        }
                    } else {
                        alert('削除に失敗しました: ' + (data.message || ''));
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('通信エラーが発生しました');
                    location.reload();
                });
            }
        }
    });
}

function closeTrainingMenu() {
    const overlay = document.getElementById('training-menu-overlay');
    overlay.classList.remove('active');
    document.body.style.overflow = '';
    currentTrainingCard = null;
}