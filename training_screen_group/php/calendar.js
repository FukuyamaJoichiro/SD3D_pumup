/**
 * calendar.js
 * 日付クリックによる選択・アクション処理を管理
 */

let selectedElement = null;

/**
 * ユーティリティ関数: モーダルを閉じる
 */
function closeModal() {
    const modal = document.getElementById('activity-modal');
    modal.style.opacity = '0';
    setTimeout(() => {
        modal.style.display = 'none';
        modal.style.opacity = '1';
    }, 300);

    document.getElementById('selected-date-input').value = '';
    if(document.getElementById('selected-activity-id')) {
        document.getElementById('selected-activity-id').value = '';
    }
}

/**
 * モーダルを表示する関数
 */
function showActivityModal(dateString, activityType, activityId) {
    const modal = document.getElementById('activity-modal');
    const modalDateDisplay = document.getElementById('modal-date-display');
    const selectedDateInput = document.getElementById('selected-date-input');
    
    let idInput = document.getElementById('selected-activity-id');
    if (!idInput) {
        idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.id = 'selected-activity-id';
        modal.querySelector('.modal-content').appendChild(idInput);
    }
    idInput.value = activityId;
    selectedDateInput.value = dateString;
    
    const dateObj = new Date(dateString + 'T00:00:00');
    const dateStringFormatted = `${dateObj.getFullYear()}年${dateObj.getMonth() + 1}月${dateObj.getDate()}日`;
    
    let title = dateStringFormatted;
    const removeBtn = document.getElementById('remove-rest-btn');

    if (activityType === 'REST') {
        title += ' の「おやすみ」を編集';
        removeBtn.textContent = '🗑️ おやすみを解除する';
        removeBtn.style.display = 'block';
        document.getElementById('change-rest-btn').style.display = 'block';
        document.getElementById('record-workout-btn').textContent = '✅ トレーニングを記録する';
    } else if (activityType === 'WORKOUT') {
        title += ' の「トレーニング」を編集';
        removeBtn.textContent = '🗑️ トレーニング記録を削除';
        removeBtn.style.display = 'block';
        document.getElementById('change-rest-btn').style.display = 'none';
        document.getElementById('record-workout-btn').textContent = '📝 トレーニング記録を編集する';
    }
    
    modalDateDisplay.textContent = title;
    modal.style.display = 'flex';
}

// --- イベントリスナー設定 ---
document.addEventListener('DOMContentLoaded', () => {
    const cells = document.querySelectorAll('.calendar-cell');

    cells.forEach(cell => {
        cell.addEventListener('click', () => {
            const dateString = cell.getAttribute('data-date');
            const activityId = cell.getAttribute('data-id');
            
            // --- 💡 英語の曜日(Tue, Mon等)と継続日数を維持するロジック ---
            const dateObj = new Date(dateString + 'T00:00:00');
            const month = dateObj.getMonth() + 1;
            const date = dateObj.getDate();
            
            // 英語の略称曜日配列
            const dayNamesEn = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            const dayNameEn = dayNamesEn[dateObj.getDay()];
            
            // PHPの date('n月j日 D') と同じ「1月12日 Mon」の形式を作成
            const formattedDate = `${month}月${date}日 ${dayNameEn}`;
            
            const displayDateText = document.getElementById('display-date-text');
            if (displayDateText) {
                // 現在の表示から「（〇〇日継続中！）」の部分を確実に抜き出す
                const match = displayDateText.innerText.match(/（\d+日継続中！/);
                const streakText = match ? match[0] + "）" : "";
                
                // 合体させて更新
                displayDateText.innerHTML = `${formattedDate}${streakText}`;
            }

            // 状態判定
            let activityType = 'none';
            if (cell.classList.contains('rest-day')) {
                activityType = 'REST';
            } else if (cell.classList.contains('trained')) {
                activityType = 'WORKOUT';
            }

            if (selectedElement === cell) {
                if (activityType !== 'none') {
                    showActivityModal(dateString, activityType, activityId);
                } else {
                    window.location.href = 'training_record.php?date=' + dateString;
                }
            } else {
                if (selectedElement) {
                    selectedElement.classList.remove('selected');
                }
                cell.classList.add('selected');
                selectedElement = cell;
            }
        });
    });

    const modal = document.getElementById('activity-modal');
    modal.addEventListener('click', (event) => {
        if (event.target === modal) closeModal();
    });

    document.getElementById('cancel-btn').addEventListener('click', closeModal);

    document.getElementById('record-workout-btn').addEventListener('click', () => {
        const date = document.getElementById('selected-date-input').value;
        if (!date) return;
        window.location.href = `training_record.php?date=${date}`;
        closeModal();
    });

    document.getElementById('remove-rest-btn').addEventListener('click', () => {
        const activityId = document.getElementById('selected-activity-id').value;
        if (!activityId) return;
        
        if (confirm(`この記録を本当に削除しますか？`)) {
            fetch('delete_activity.php', { 
                method: 'POST', 
                headers: { 'Content-Type': 'application/json' }, 
                body: JSON.stringify({ activity_id: activityId }) 
            })
            .then(res => res.json())
            .then(data => {
                alert(`削除しました。`);
                window.location.reload(); 
            })
            .catch(error => {
                alert(`削除に失敗しました`);
                closeModal();
            });
        }
    });

    document.getElementById('change-rest-btn').addEventListener('click', () => {
        const date = document.getElementById('selected-date-input').value;
        if (date) window.location.href = `rest_edit.php?date=${date}`;
        closeModal();
    });
});