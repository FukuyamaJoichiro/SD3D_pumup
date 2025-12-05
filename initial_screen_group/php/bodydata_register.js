// bodydata_register.js
document.addEventListener('DOMContentLoaded', function() {
    // モーダルの要素を取得
    const modal = document.getElementById('duplicateModal');
    
    // モーダルが存在し、かつ PHPによって 'active' クラスが付与されている場合のみ処理を実行
    if (modal && modal.classList.contains('active')) {
        const closeButton = modal.querySelector('.modal-close-button');

        // OKボタンクリックでモーダルを閉じる
        closeButton.addEventListener('click', function() {
            modal.classList.remove('active');
        });
        
        // オーバーレイ（モーダルの背景）クリックでモーダルを閉じる
        modal.addEventListener('click', function(e) {
            // クリックされた要素がモーダルオーバーレイ自体であるか確認
            if (e.target === modal) {
                modal.classList.remove('active');
            }
        });
    }

    // 必要に応じて、他のフォームバリデーションやJS処理をここに追加してください。
});