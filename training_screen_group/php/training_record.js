/**
 * training_record.js
 * 日付スライダーのスワイプ操作を処理
 */

document.addEventListener('DOMContentLoaded', () => {
    const table = document.querySelector('.date-slider');
    if (!table) return;

    let touchstartX = 0;
    let touchendX = 0;
    const swipeThreshold = 50; // スワイプと認識するピクセル数

    function checkDirection() {
        // 現在選択されている日付（URLパラメータから取得されている日付）を取得
        const selectedAnchor = table.querySelector('td.selected a');
        if (!selectedAnchor) return; 
        
        const currentDate = selectedAnchor.dataset.date;
        const newDate = new Date(currentDate);

        let dateChanged = false;

        if (touchendX < touchstartX - swipeThreshold) {
            // 左スワイプ (未来へ/次の週へ)
            newDate.setDate(newDate.getDate() + 7);
            dateChanged = true;
        }

        if (touchendX > touchstartX + swipeThreshold) {
            // 右スワイプ (過去へ/前の週へ)
            newDate.setDate(newDate.getDate() - 7);
            dateChanged = true;
        }

        // 新しい日付をYYYY-MM-DD形式でフォーマット
        if (dateChanged) {
            const year = newDate.getFullYear();
            // getMonth() は 0-11 を返すため +1
            const month = String(newDate.getMonth() + 1).padStart(2, '0'); 
            const day = String(newDate.getDate()).padStart(2, '0');
            const newDateString = `${year}-${month}-${day}`;

            // URLに新しい日付パラメータを付与して遷移（週の切り替え）
            // 遷移することでPHPが再実行され、新しい週が表示される
            window.location.href = `?date=${newDateString}`;
        }
    }

    table.addEventListener('touchstart', e => {
        touchstartX = e.changedTouches[0].screenX;
    });

    table.addEventListener('touchend', e => {
        touchendX = e.changedTouches[0].screenX;
        checkDirection();
    });
});