/**
 * training_finish.js
 * 完了画面に今日の日付と曜日を表示するロジック
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. 今日の日付情報を取得
    const today = new Date();
    const todayMonth = today.getMonth() + 1; // 1 (1月) 〜 12 (12月)
    const todayDate = today.getDate();       // 1 〜 31日
    const todayDayIndex = today.getDay();    // 0 (日) 〜 6 (土)

    const dayNames = ['日', '月', '火', '水', '木', '金', '土'];
    const todayDayName = dayNames[todayDayIndex];

    // 2. 表示する文字列を作成 (例: 11月11日 火)
    const dateString = `${todayMonth}月${todayDate}日 ${todayDayName}`;
    
    // 3. HTML要素を更新
    const dateInfoElement = document.getElementById('current-date-day');
    if (dateInfoElement) {
        dateInfoElement.textContent = dateString;
    }
});