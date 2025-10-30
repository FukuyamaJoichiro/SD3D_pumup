document.addEventListener('DOMContentLoaded', () => {
// すべてのレベルボタンを取得
 const levelButtons = document.querySelectorAll('.level-button');

    // ★★★ 修正箇所: ID名と変数名を HTMLの隠しフィールドに合わせて変更 ★★★
const experienceLevelInput = document.getElementById('experienceLevelInput'); 

// 各ボタンにクリックイベントリスナーを設定
levelButtons.forEach(button => {
 button.addEventListener('click', () => {
 
 // 1. すでに選択されているボタンから 'selected' クラスを削除
 levelButtons.forEach(btn => {
 btn.classList.remove('selected');
 });

 // 2. クリックされたボタンに 'selected' クラスを追加（赤い枠が表示される）
 button.classList.add('selected');

// 3. 隠しフィールドに選択されたレベルの値を設定
 const selectedLevel = button.getAttribute('data-level');

            // ★★★ 修正箇所: 変数名を experienceLevelInput に合わせる ★★★
 experienceLevelInput.value = selectedLevel; 
});
 });

// 💡 初期選択: ページロード時にデフォルトでLv.1を選択状態にする
const defaultButton = document.querySelector('.level-button[data-level="1"]');
 if (defaultButton) {
 defaultButton.click(); // クリックイベントを発火させて初期選択状態にする
}
});