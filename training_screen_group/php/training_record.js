/**
 * training_record.js
 * 1回クリック：選択（赤丸移動）
 * 2回クリック：その日のページへ遷移
 * スワイプ：週（7日分）の切り替え
 */
document.addEventListener('DOMContentLoaded', () => {
    const table = document.querySelector('.date-slider');
    if (!table) return;

    const cells = table.querySelectorAll('td');
    let touchstartX = 0;
    let touchendX = 0;
    const swipeThreshold = 50; 

    // --- 1. クリック（タップ）時の挙動制御 ---
    cells.forEach(cell => {
        cell.addEventListener('click', () => {
            // PHP側で td に設定した data-full-date を取得
            const fullDate = cell.getAttribute('data-full-date');
            if (!fullDate) return;

            // すでに赤丸（selectedクラス）がついているセルをもう一度クリックした場合
            if (cell.classList.contains('selected')) {
                // その日の記録画面へ遷移
                window.location.href = `?date=${fullDate}`;
            } else {
                // まだ選択されていない日をクリックした場合
                // 1) すべてのセルの赤丸（selectedクラス）を一旦消す
                cells.forEach(c => c.classList.remove('selected'));
                // 2) クリックしたセルだけに赤丸をつける
                cell.classList.add('selected');
            }
        });
    });

    // --- 2. スワイプ（週切り替え）の処理ロジック ---
    function checkDirection() {
        const urlParams = new URLSearchParams(window.location.search);
        // 現在表示中の日付をURLから取得（なければ今日）
        const currentDateStr = urlParams.get('date') || new Date().toISOString().split('T')[0];
        const newDate = new Date(currentDateStr);

        let dateChanged = false;
        const diffX = touchendX - touchstartX;

        // 横スワイプの距離がしきい値を超えているか判定
        if (diffX < -swipeThreshold) {
            // 左スワイプ (次の週へ)
            newDate.setDate(newDate.getDate() + 7);
            dateChanged = true;
        } else if (diffX > swipeThreshold) {
            // 右スワイプ (前の週へ)
            newDate.setDate(newDate.getDate() - 7);
            dateChanged = true;
        }

        if (dateChanged) {
            const year = newDate.getFullYear();
            const month = String(newDate.getMonth() + 1).padStart(2, '0'); 
            const day = String(newDate.getDate()).padStart(2, '0');
            const newDateString = `${year}-${month}-${day}`;

            // 週を切り替えてリロード
            window.location.href = `?date=${newDateString}`;
        }
    }

    // --- 3. タッチイベントの登録 ---
    table.addEventListener('touchstart', e => {
        touchstartX = e.changedTouches[0].screenX;
    }, {passive: true});

    table.addEventListener('touchend', e => {
        touchendX = e.changedTouches[0].screenX;
        
        // スワイプ（50px以上の移動）が起きた時だけ週切り替えを実行
        // それ以下の微細な動きは「クリック」として扱うため無視する
        if (Math.abs(touchendX - touchstartX) > swipeThreshold) {
            checkDirection();
        }
    }, {passive: true});
});