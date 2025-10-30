document.addEventListener('DOMContentLoaded', () => {
    // すべての目標ボタンを取得
    const goalButtons = document.querySelectorAll('.goal-button');
    // 隠しフィールドを取得 (選択値を格納するため)
    const selectedGoalInput = document.getElementById('selectedGoalInput');
    
    // 各ボタンにクリックイベントリスナーを設定
    goalButtons.forEach(button => {
        button.addEventListener('click', () => {
            
            // 1. すでに選択されているボタンから 'selected' クラスを削除
            goalButtons.forEach(btn => {
                btn.classList.remove('selected');
            });

            // 2. クリックされたボタンに 'selected' クラスを追加（赤い枠が表示される）
            button.classList.add('selected');

            // 3. 隠しフィールドに選択された目標の値を設定 (data-goal="1"などを取得)
            const selectedGoal = button.getAttribute('data-goal');
            selectedGoalInput.value = selectedGoal;
        });
    });

    // 💡 初期選択: ページロード時にデフォルトで最初の目標を選択状態にする
    const defaultButton = document.querySelector('.goal-button[data-goal="1"]');
    if (defaultButton) {
        defaultButton.click(); // クリックイベントを発火させて初期選択状態にする
    }
});