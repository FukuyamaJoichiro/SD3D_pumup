/**
 * calendar.js (修正版)
 * 日付クリックによる選択・遷移処理を管理
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
    // ID保持用フィールドもクリア
    if(document.getElementById('selected-activity-id')) {
        document.getElementById('selected-activity-id').value = '';
    }
}

/**
 * モーダルを表示する関数
 * @param {string} dateString - YYYY-MM-DD形式の日付
 * @param {string} activityType - 'REST' or 'WORKOUT'
 * @param {string} activityId - データベースの ID (id)
 */
function showActivityModal(dateString, activityType, activityId) {
    const modal = document.getElementById('activity-modal');
    const modalDateDisplay = document.getElementById('modal-date-display');
    const selectedDateInput = document.getElementById('selected-date-input');
    
    // 💡 修正ポイント: 削除に使うIDを隠しフィールドに保存
    let idInput = document.getElementById('selected-activity-id');
    if (!idInput) {
        idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.id = 'selected-activity-id';
        modal.appendChild(idInput);
    }
    idInput.value = activityId;
    selectedDateInput.value = dateString;
    
    const dateObj = new Date(dateString + 'T00:00:00');
    const dateStringFormatted = `${dateObj.getFullYear()}年${dateObj.getMonth() + 1}月${dateObj.getDate()}日`;
    
    let title = dateStringFormatted;
    
    // 全ての削除ボタンを一旦共通で使えるように表示制御
    const removeBtn = document.getElementById('remove-rest-btn');

    if (activityType === 'REST') {
        title += ' の「おやすみ」を編集';
        removeBtn.textContent = '🗑️ おやすみを解除する';
        removeBtn.style.display = 'block';
        document.getElementById('change-rest-btn').style.display = 'block';
        document.getElementById('record-workout-btn').textContent = '✅ トレーニングを記録する';

    } else if (activityType === 'WORKOUT') {
        title += ' の「トレーニング」を編集';
        // トレーニングも削除可能にする
        removeBtn.textContent = '🗑️ トレーニング記録を削除';
        removeBtn.style.display = 'block';
        document.getElementById('change-rest-btn').style.display = 'none';
        document.getElementById('record-workout-btn').textContent = '📝 トレーニング記録を編集する';
    }
    
    modalDateDisplay.textContent = title;
    modal.style.display = 'flex';
}

/**
 * 日付クリック時の処理
 */
function handleDateClick(clickedElement) {
    const dateString = clickedElement.getAttribute('data-date');
    // 💡 修正ポイント: HTMLからIDを取得
    const activityId = clickedElement.getAttribute('data-id'); 
    const activityCell = clickedElement.closest('td');

    let activityType = 'none';
    if (activityCell.classList.contains('rest-day')) {
        activityType = 'REST';
    } else if (activityCell.classList.contains('trained')) {
        activityType = 'WORKOUT';
    }

    // 記録がある日は即座にモーダルを表示
    if (activityType !== 'none') {
        if (selectedElement) {
            selectedElement.classList.remove('selected');
            selectedElement = null;
        }
        showActivityModal(dateString, activityType, activityId);
        return;
    }

    // 記録がない日のロジック
    if (selectedElement) {
        if (clickedElement === selectedElement) {
            window.location.href = 'training_record.php?date=' + dateString; 
            return; 
        } else {
            selectedElement.classList.remove('selected');
        }
    }
    
    clickedElement.classList.add('selected');
    selectedElement = clickedElement;
}

// --- イベントリスナー設定 ---
document.addEventListener('DOMContentLoaded', () => {
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

    // 💡 修正ポイント: 「削除（解除）」ボタンの共通処理
    document.getElementById('remove-rest-btn').addEventListener('click', () => {
        const activityId = document.getElementById('selected-activity-id').value;
        if (!activityId) return;
        
        if (confirm(`この記録を本当に削除しますか？`)) {
            fetch('delete_activity.php', { 
                method: 'POST', 
                headers: { 'Content-Type': 'application/json' }, 
                // 💡 日付ではなく ID を送るように変更
                body: JSON.stringify({ activity_id: activityId }) 
            })
            .then(res => {
                if (!res.ok) return res.json().then(err => { throw new Error(err.message); });
                return res.json();
            })
            .then(data => {
                alert(`削除しました。`);
                window.location.reload(); 
            })
            .catch(error => {
                alert(`削除に失敗しました: ${error.message}`);
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