/**
 * training_rest.js
 * 今月の月名表示と、今日の日付のハイライトを処理します。
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. 今日の日付情報を取得
    const today = new Date();
    const todayDate = today.getDate(); // 1〜31日

    // --- 月名の表示 ---
    const monthNames = ["1月", "2月", "3月", "4月", "5月", "6月", "7月", "8月", "9月", "10月", "11月", "12月"];
    const currentMonthIndex = today.getMonth();
    const currentMonthName = monthNames[currentMonthIndex];
    
    const monthTitleElement = document.querySelector('.month-title');
    if (monthTitleElement) {
        monthTitleElement.textContent = currentMonthName;
    }
    
    // --- 日付のハイライト ---
    
    // カレンダーの日付範囲 (HTML構造と一致: 9, 10, 11, 12, 13, 14, 15日)
    const calendarDates = [9, 10, 11, 12, 13, 14, 15];
    const calendarDays = document.querySelectorAll('.day-item');

    // 2. カレンダー表示のリセット (冗長なforEachを統合)
    calendarDays.forEach(day => {
        // 既存のHTML上のアクティブクラスと色クラスをリセット
        day.classList.remove('active', 'day-fire', 'day-sun', 'day-sat');
        
        // 日付の文字色をデフォルト（CSSで設定された色）に戻す
        const dateSpan = day.querySelector('.date');
        if (dateSpan) dateSpan.style.color = ''; // style属性をクリア
    });

    // 3. 今日の日付を特定し、ハイライトを適用
    const todayIndex = calendarDates.indexOf(todayDate);

    if (todayIndex !== -1) {
        const todayElement = calendarDays[todayIndex];
        
        // ★修正: 赤い丸ハイライト用の 'active' クラスだけを付与
        todayElement.classList.add('active');
        
        // 曜日ごとの色付け (日、土、火) のためのクラスを適用
        const todayDay = today.getDay(); // 0(日)〜6(土)
        
        if (todayDay === 0) {
            todayElement.classList.add('day-sun');
        } else if (todayDay === 6 || todayDay === 5) { // 土曜と金曜を青に
            todayElement.classList.add('day-sat');
        } else if (todayDay === 2) {
            todayElement.classList.add('day-fire'); // 火曜を赤に
        }
        
        // activeな日の日付文字の色をCSSで設定した丸の白文字にする（CSSに任せる）
    }


    // 4. キャンセルリンク処理 (HTMLのhrefに任せるため、イベントリスナーを削除)
    // 以前の e.preventDefault(); が画面遷移を止めていたため、ここでは処理しない
});