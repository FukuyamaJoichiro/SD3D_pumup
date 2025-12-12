/**
 * calendar.js
 * 日付クリックによる選択・遷移処理を管理
 */

// グローバル変数: 選択中の日付要素を保持
let selectedElement = null;

/**
 * ユーティリティ関数: モーダルを閉じる
 */
function closeModal() {
    const modal = document.getElementById('activity-modal');
    // アニメーションを考慮し、display: none を遅延させて設定
    modal.style.opacity = '0';
    setTimeout(() => {
        modal.style.display = 'none';
        modal.style.opacity = '1'; // 次回表示のためにリセット
    }, 300); // CSSのアニメーション時間(0.3s)と合わせる

    document.getElementById('selected-date-input').value = '';
}

/**
 * モーダルを表示する関数
 * @param {string} dateString - YYYY-MM-DD形式の日付
 * @param {string} activityType - 'REST' or 'WORKOUT'
 */
function showActivityModal(dateString, activityType) {
    const modal = document.getElementById('activity-modal');
    const modalDateDisplay = document.getElementById('modal-date-display');
    const selectedDateInput = document.getElementById('selected-date-input');

    selectedDateInput.value = dateString;
    
    // 日付を「Y年M月D日」形式で表示
    const dateObj = new Date(dateString + 'T00:00:00'); // タイムゾーン問題を回避
    const dateStringFormatted = `${dateObj.getFullYear()}年${dateObj.getMonth() + 1}月${dateObj.getDate()}日`;
    
    let title = dateStringFormatted;
    
    if (activityType === 'REST') {
        title += ' の「おやすみ」を編集';
        // おやすみ関連ボタンの表示
        document.getElementById('remove-rest-btn').style.display = 'block';
        document.getElementById('change-rest-btn').style.display = 'block';
        document.getElementById('record-workout-btn').textContent = '✅ トレーニングを記録する (おやすみ解除)';

    } else if (activityType === 'WORKOUT') {
        title += ' の「トレーニング」を編集';
        // トレーニングの場合は解除・変更ボタンを非表示に（ロジックは別途検討が必要）
        document.getElementById('remove-rest-btn').style.display = 'none';
        document.getElementById('change-rest-btn').style.display = 'none';
        document.getElementById('record-workout-btn').textContent = '📝 トレーニング記録を編集する';
    } else {
        // 未記録の日付が誤ってモーダルを表示した場合のフォールバック
        title += ' の記録';
        document.getElementById('remove-rest-btn').style.display = 'none';
        document.getElementById('change-rest-btn').style.display = 'none';
        document.getElementById('record-workout-btn').textContent = '✅ トレーニングを記録する';
    }
    
    modalDateDisplay.textContent = title;
    modal.style.display = 'flex';
}

/**
 * 日付クリック時の処理 (修正版)
 * 既存のロジックにモーダル表示処理を組み込む
 * @param {HTMLElement} clickedElement クリックされた要素 (.date-clickable-wrapper)
 */
function handleDateClick(clickedElement) {
    const dateString = clickedElement.getAttribute('data-date');
    const activityCell = clickedElement.closest('td');

    let activityType = 'none';
    if (activityCell.classList.contains('rest-day')) {
        activityType = 'REST';
    } else if (activityCell.classList.contains('trained')) {
        activityType = 'WORKOUT';
    }

    // ----------------------------------------------------------------
    // 【最優先】記録がある日（RESTまたはWORKOUT）はモーダルを即座に表示する
    // ----------------------------------------------------------------
    if (activityType !== 'none') {
        // 既存の選択状態を解除して、即座にモーダルを表示
        if (selectedElement) {
            selectedElement.classList.remove('selected');
            selectedElement = null;
        }
        showActivityModal(dateString, activityType);
        return; // モーダル表示後は既存の1回/2回クリックロジックをスキップ
    }

    // ----------------------------------------------------------------
    // 【既存ロジック】記録がない日、またはWORKOUT/RESTでない日の処理
    // ----------------------------------------------------------------

    // 1. 既に選択されている要素があるかチェック
    if (selectedElement) {
        
        // A. クリックされた要素が、既に選択中の要素と同じ場合（2回目のクリック）
        if (clickedElement === selectedElement) {
            // ページ遷移を実行（遷移先は必要に応じて変更してください）
            // 未記録の日付を2回クリックした場合の遷移先
            window.location.href = 'training_record.php?date=' + dateString; 
            return; 
        } 
        
        // B. 別の要素がクリックされた場合（選択の切り替え）
        else {
            // 古い選択を解除
            selectedElement.classList.remove('selected');
        }
    }
    
    // 2. 新しい要素を選択状態にする
    clickedElement.classList.add('selected');
    selectedElement = clickedElement;
}


// --- モーダル内のボタンイベントリスナー設定 ---
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('activity-modal');
    
    // 1. 黒い背景をクリックしてキャンセル
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    // 2. 「キャンセル」ボタン
    document.getElementById('cancel-btn').addEventListener('click', closeModal);

    // 3. 「トレーニングを記録する」ボタン (REST解除＋遷移、またはWORKOUT編集)
    document.getElementById('record-workout-btn').addEventListener('click', () => {
        const date = document.getElementById('selected-date-input').value;
        if (!date) return;
        
        // 【重要】ここでAPIを呼び出して'REST'を削除する処理を実装する必要があります
        console.log(`${date} の記録を解除/編集し、トレーニング記録画面へ遷移します。`);

        // API呼び出し（おやすみ解除の場合）：fetch('delete_activity.php', ...)
        window.location.href = `training_record.php?date=${date}`;

        closeModal();
    });

    // 4. 「おやすみを解除する」ボタン
    document.getElementById('remove-rest-btn').addEventListener('click', () => {
        const date = document.getElementById('selected-date-input').value;
        if (!date) return;
        
        if (confirm(`${date} のおやすみ記録を本当に解除しますか？`)) {
            
            // 🚨 APIの呼び出しURLを修正: 'delete_activity.php'
            fetch('delete_activity.php', { 
                method: 'POST', 
                headers: { 'Content-Type': 'application/json' }, 
                body: JSON.stringify({ date: date, type: 'REST' }) 
            })
            .then(res => {
                // レスポンスがJSON形式でない場合のデバッグロジックを含めることが推奨されますが、
                // PHP側で修正したため、ここではシンプルに続行します。
                if (!res.ok) {
                    // HTTPエラー（400, 404, 500など）の場合
                    return res.json().then(err => { throw new Error(err.message || 'API call failed'); });
                }
                return res.json();
            })
            .then(data => {
                // 成功
                alert(`${data.date} のおやすみ記録を解除しました。`);
                window.location.reload(); // データを更新するためリロード
            })
            .catch(error => {
                // 通信エラーやサーバーからのエラーメッセージを表示
                console.error('Error:', error);
                alert(`解除に失敗しました: ${error.message}`);
                closeModal();
            });

        }
    });
    
    // 5. 「おやすみを変更する」ボタン
    document.getElementById('change-rest-btn').addEventListener('click', () => {
        const date = document.getElementById('selected-date-input').value;
        if (!date) return;
        
        // 編集画面へ遷移
        console.log(`${date} のおやすみ記録の編集画面へ遷移します。`);
        window.location.href = `rest_edit.php?date=${date}`;

        closeModal();
    });
});