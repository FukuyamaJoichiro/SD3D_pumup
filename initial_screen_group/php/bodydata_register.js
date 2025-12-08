document.addEventListener('DOMContentLoaded', function() {
    // 既存のモーダル処理 (変更なし)
    const modal = document.getElementById('duplicateModal');
    
    if (modal && modal.classList.contains('active')) {
        const closeButton = modal.querySelector('.modal-close-button');

        closeButton.addEventListener('click', function() {
            modal.classList.remove('active');
        });
        
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.classList.remove('active');
            }
        });
    }

    // --- 【リアルタイム処理】パスワードのカスタムバリデーション処理 ---

    const passwordInput = document.getElementById('password');
    const form = document.getElementById('initialForm');
    
    // HTMLで追加した警告吹き出し要素を取得
    const errorTip = document.getElementById('password-error-tip');
    const warningTextElement = errorTip ? errorTip.querySelector('.warning-text') : null;
    
    const MIN_LENGTH = 8;
    const MAX_LENGTH = 16;
    
    
    // 🚨 変更点1: input イベントリスナー (リアルタイムチェック) を再定義
    passwordInput.addEventListener('input', function() {
        const value = this.value;
        const length = value.length;
        
        // setCustomValidity()をクリア
        this.setCustomValidity('');
        
        // 1. 文字数が0の場合はエラー表示を非表示
        if (length === 0) {
            if (errorTip) {
                errorTip.style.display = 'none';
            }
            return;
        }

        // 2. 範囲外の場合に警告を表示
        if (length < MIN_LENGTH || length > MAX_LENGTH) {
            // エラーメッセージを動的に生成
            const customMessage = `8文字以上16文字以内で入力してください。(現在${length}文字)。`;
            
            // 警告UIを更新し、表示する
            if (errorTip && warningTextElement) {
                warningTextElement.textContent = customMessage;
                errorTip.style.display = 'flex'; // 警告UIを表示
            }
            
            // フォーム送信を阻止するためのエラーメッセージを設定
            this.setCustomValidity(customMessage); 
            
        } else {
            // 3. 有効な場合、警告UIを非表示にする
            if (errorTip) {
                errorTip.style.display = 'none';
            }
            // setCustomValidityはすでにループ冒頭でクリアされているため、再度設定する必要はない
        }
    });

});