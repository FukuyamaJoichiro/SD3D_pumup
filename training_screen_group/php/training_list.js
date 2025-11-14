// ===== 検索機能 =====
const searchInput = document.getElementById('search-input');
const trainingItems = document.querySelectorAll('.training-item');

searchInput.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    
    trainingItems.forEach(item => {
        const trainingName = item.querySelector('.training-name').textContent.toLowerCase();
        if (trainingName.includes(searchTerm)) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
});

// ===== フィルター機能 =====
let activePartIds = [];

document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const partId = this.getAttribute('data-part-id');
        
        // 「□」ボタン（全て表示）がクリックされた場合
        if (partId === 'all') {
            activePartIds = [];
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            filterTrainings();
            return;
        }
        
        // 「□」ボタンの選択を解除
        const allBtn = document.querySelector('.filter-btn[data-part-id="all"]');
        if (allBtn) allBtn.classList.remove('active');
        
        // クリックされたボタンの切り替え
        this.classList.toggle('active');
        
        // アクティブな部位IDを更新
        if (this.classList.contains('active')) {
            if (!activePartIds.includes(partId)) {
                activePartIds.push(partId);
            }
        } else {
            activePartIds = activePartIds.filter(id => id !== partId);
        }
        
        // フィルターが何も選択されていない場合は全て表示
        if (activePartIds.length === 0 && allBtn) {
            allBtn.classList.add('active');
        }
        
        filterTrainings();
    });
});

// トレーニングのフィルタリング処理
function filterTrainings() {
    trainingItems.forEach(item => {
        const itemPartIds = item.getAttribute('data-part-ids');
        
        // フィルターが選択されていない場合は全て表示
        if (activePartIds.length === 0) {
            item.style.display = 'flex';
            return;
        }
        
        // 部位IDが設定されていない種目は非表示
        if (!itemPartIds || itemPartIds === '') {
            item.style.display = 'none';
            return;
        }
        
        // 種目の部位IDリスト（配列に変換）
        const itemPartIdArray = itemPartIds.split(',').map(id => id.trim());
        
        // 選択された部位のいずれかに該当するか確認
        const matches = activePartIds.some(partId => itemPartIdArray.includes(String(partId)));
        
        item.style.display = matches ? 'flex' : 'none';
    });
}

// ===== ブックマーク機能 =====
document.querySelectorAll('.bookmark-icon').forEach(icon => {
    icon.addEventListener('click', function() {
        this.textContent = this.textContent === '🏴' ? '🚩' : '🏴';
    });
});

// ===== モーダル機能 =====
const addBtn = document.querySelector('.add-btn');
const modalOverlay = document.getElementById('modal-overlay');
const modalCloseBtn = document.getElementById('modal-close-btn');
const addTrainingForm = document.getElementById('add-training-form');

// +ボタンクリックでモーダルを表示
addBtn.addEventListener('click', function() {
    modalOverlay.classList.add('active');
    document.body.style.overflow = 'hidden'; // スクロール無効化
});

// 閉じるボタンクリックでモーダルを閉じる
modalCloseBtn.addEventListener('click', function() {
    closeModal();
});

// オーバーレイクリックでモーダルを閉じる
modalOverlay.addEventListener('click', function(e) {
    if (e.target === modalOverlay) {
        closeModal();
    }
});

// モーダルを閉じる関数
function closeModal() {
    modalOverlay.classList.remove('active');
    document.body.style.overflow = ''; // スクロール有効化
    addTrainingForm.reset(); // フォームをリセット
    // すべてのトグルボタンの選択を解除
    document.querySelectorAll('.toggle-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    // hidden inputもクリア
    document.getElementById('part_id').value = '';
    document.getElementById('tool_id').value = '';
    document.getElementById('type_id').value = '';
}

// トグルボタンの処理
document.querySelectorAll('.toggle-btn').forEach(button => {
    button.addEventListener('click', function() {
        const name = this.getAttribute('data-name');
        const value = this.getAttribute('data-value');
        
        // type_idは複数選択可能
        if (name === 'type_id') {
            this.classList.toggle('active');
            updateHiddenInput(name);
        } else {
            // その他は単一選択
            const group = document.querySelectorAll(`.toggle-btn[data-name="${name}"]`);
            group.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            document.getElementById(name).value = value;
        }
    });
});

// 複数選択の値をhidden inputに設定
function updateHiddenInput(name) {
    const activeButtons = document.querySelectorAll(`.toggle-btn[data-name="${name}"].active`);
    const values = Array.from(activeButtons).map(btn => btn.getAttribute('data-value'));
    document.getElementById(name).value = values.join(',');
}

// フォーム送信処理
addTrainingForm.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('training_add.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('トレーニングを追加しました！');
            closeModal();
            // ページをリロードして新しいトレーニングを表示
            location.reload();
        } else {
            alert('エラー: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('トレーニングの追加に失敗しました。');
    });
});