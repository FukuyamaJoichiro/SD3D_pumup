/**
 * calendar.js
 * 日付クリックによる選択・遷移処理を管理
 */

// グローバル変数: 選択中の日付要素を保持
let selectedElement = null;

/**
 * 日付クリック時の処理
 * 1回目：選択状態にする
 * 2回目：画面遷移する
 * @param {HTMLElement} clickedElement クリックされた要素 (.date-clickable-wrapper)
 */
function handleDateClick(clickedElement) {
    // クリックされた日付データを取得
    const dateString = clickedElement.getAttribute('data-date');
    
    // 1. 既に選択されている要素があるかチェック
    if (selectedElement) {
        
        // A. クリックされた要素が、既に選択中の要素と同じ場合（2回目のクリック）
        if (clickedElement === selectedElement) {
            // ページ遷移を実行（遷移先は必要に応じて変更してください）
            window.location.href = 'date_detail.php?date=' + dateString;
            return; // 処理終了
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