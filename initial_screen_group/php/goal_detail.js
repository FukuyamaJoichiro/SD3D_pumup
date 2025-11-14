// ファイル名: goal_detail.js

document.addEventListener('DOMContentLoaded', () => {
    
    // 目標IDに対応する表示テキストとCSSクラスの定義
    const goalMapping = {
        // DBに格納されているgoalの値(1, 2, 3, 4)と表示テキストの対応表
        '1': { title: '理想のマッスルボディへ', subtitle: '気合で頑張るしかない。', icon_class: 'icon-body' },
        '2': { title: 'ストレングス (筋力) をつける', subtitle: '誰よりも強い力を。', icon_class: 'icon-strength' },
        '3': { title: '身体能力を向上させる', subtitle: '様々な運動をうまくこなせるよう。', icon_class: 'icon-ability' },
        '4': { title: '体力をつける', subtitle: '疲れないように体力を。', icon_class: 'icon-stamina' },
    };

    // ------------------------------------------------
    // 1. セッションデータ (目標ID) の取得と表示
    // ------------------------------------------------
    
    // Ajax/Fetch APIでPHPファイルからセッションデータを取得
    // パスは goal_detail.js から見て、get_session_data.php への正しいパスを指定してください
    fetch('../php/get_session_data.php') 
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            const goalId = data.goal; // 例: '1' or '2'
            
            const goalInfo = goalMapping[goalId];
            
            if (goalInfo) {
                // HTML要素を更新して表示
                const goalButton = document.querySelector('.goal-button');
                if (goalButton) {
                    // 1. タイトルとサブタイトルを書き換え
                    goalButton.querySelector('h2').textContent = goalInfo.title;
                    goalButton.querySelector('p').textContent = goalInfo.subtitle;
                    
                    // 2. アイコンクラスとdata-goal属性を更新
                    const iconElement = goalButton.querySelector('.goal-icon');
                    if (iconElement) {
                       // 既存のアイコンクラスを削除し、新しいクラスを適用
                       iconElement.className = `goal-icon ${goalInfo.icon_class}`;
                    }
                    // 3. ボタン自体の属性を更新
                    goalButton.dataset.goal = goalId;
                    // ボタンのクラス自体も更新 (CSSによるデザイン変更のため)
                    goalButton.className = `goal-button goal-${goalId} selected`;
                }
            } else {
                console.error('Goal IDが不正です:', goalId);
            }
        })
        .catch(error => {
            console.error('目標データの取得に失敗しました:', error);
            // ユーザーに表示する代替テキストを設定するなどのエラー処理をここに追加
        });

    // ------------------------------------------------
    // 2. 具体的な目標 (textarea) の文字数カウント処理
    // ------------------------------------------------
    const textarea = document.getElementById('custom-goal');
    const charCountSpan = document.getElementById('char-count');
    const maxLength = 20;

    if (textarea && charCountSpan) {
        textarea.addEventListener('input', () => {
            let currentLength = textarea.value.length;
            
            // 20文字を超えないように制限
            if (currentLength > maxLength) {
                textarea.value = textarea.value.substring(0, maxLength);
                currentLength = maxLength;
            }
            
            charCountSpan.textContent = currentLength;
        });
        
        // 初期表示時のカウントを設定
        charCountSpan.textContent = textarea.value.length;
    }
});