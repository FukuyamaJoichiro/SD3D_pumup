document.addEventListener('DOMContentLoaded', () => {
    // === DOM要素の取得 ===
    const clickableInputs = document.querySelectorAll('.clickable-input');
    const modalOverlay = document.getElementById('dataModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalUnit = document.getElementById('modalUnit');
    const integerPicker = document.getElementById('integerPicker');
    const decimalPicker = document.getElementById('decimalPicker');
    const modalConfirmButton = document.getElementById('modalConfirmButton');
    const muscleRateOutput = document.getElementById('muscleRateOutput');
    const bodyFatRateOutput = document.getElementById('bodyFatRateOutput');

    // フォームと表示要素
    const weightInput = document.getElementById('weight');
    const heightInput = document.getElementById('height');
    const weightDisplay = document.getElementById('weightDisplay');
    const heightDisplay = document.getElementById('heightDisplay');

    // PHPから渡された年齢
    const age = userAge; 

    let currentTarget = null;
    const PICKER_ITEM_HEIGHT = 50; 
    
    // ピッカーの範囲設定
    const WEIGHT_MIN = 30;
    const WEIGHT_MAX = 150;
    const HEIGHT_MIN = 100;
    const HEIGHT_MAX = 220;
    const DECIMAL_MIN = 0;
    const DECIMAL_MAX = 9;


    // === ピッカー生成と操作ロジック (追加) ===
    
    /** ピッカーのアイテムを生成 */
    function generatePickerItems(pickerElement, min, max, initialValue) {
        pickerElement.innerHTML = ''; // 既存のアイテムをクリア
        
        // 値のリストを作成
        const values = [];
        for (let i = min; i <= max; i++) {
            values.push(i);
        }
        
        // 値をDOMに追加
        values.forEach(value => {
            const item = document.createElement('div');
            item.classList.add('picker-item');
            item.textContent = value;
            item.dataset.value = value;
            pickerElement.appendChild(item);
        });
        
        // 初期値にスクロール
        const index = values.indexOf(initialValue);
        if (index >= 0) {
            // 中心に表示されるように、インデックスにアイテム高さを掛けてスクロール
            // (padding: 50px 0; で上下に1個分のスペースがあるため、index*heightで中心に来る)
            pickerElement.scrollTop = index * PICKER_ITEM_HEIGHT;
        }
    }

    /** スクロール位置に基づいて中央のアイテムを選択状態にする */
    function updateSelectedValue(pickerElement) {
        // スクロール位置
        const scrollTop = pickerElement.scrollTop;
        
        // 中央のアイテムのインデックスを計算 (50で割って四捨五入)
        const selectedIndex = Math.round(scrollTop / PICKER_ITEM_HEIGHT);
        
        // 全てのアイテムから'selected'クラスを削除
        pickerElement.querySelectorAll('.picker-item').forEach(item => {
            item.classList.remove('selected');
            item.style.color = '#999';
        });

        // 中央のアイテムに'selected'クラスを追加し、色を濃くする
        const selectedItem = pickerElement.children[selectedIndex];
        if (selectedItem) {
            selectedItem.classList.add('selected');
            selectedItem.style.color = '#333';
            return parseInt(selectedItem.dataset.value);
        }
        return null;
    }

    /** スクロールイベントハンドラ */
    function scrollHandler(pickerElement) {
        // スクロールが停止したときに、強制的に最も近いアイテムの中心にスナップさせる
        // CSSの scroll-snap-type: y mandatory; が動作しないブラウザのためのフォールバック
        let scrollTimeout;
        pickerElement.addEventListener('scroll', () => {
            clearTimeout(scrollTimeout);
            
            // スクロール中は選択状態の更新はしない
            
            scrollTimeout = setTimeout(() => {
                // スクロール停止後、スナップ位置を再計算
                const scrollTop = pickerElement.scrollTop;
                const snapPosition = Math.round(scrollTop / PICKER_ITEM_HEIGHT) * PICKER_ITEM_HEIGHT;
                
                // スクロールを調整 (アニメーションなし)
                pickerElement.scrollTo({
                    top: snapPosition,
                    behavior: 'auto'
                });
                
                // 強制スナップ後、選択状態を更新
                updateSelectedValue(pickerElement);
            }, 100); // 100msの遅延
            
            // スクロール中の選択アイテムの視覚的な更新をスムーズにするため、
            // スクロール中でも中央に近いアイテムの色を変える処理も追加可能ですが、
            // 今回はパフォーマンスとシンプルさを優先し、スクロール停止後にのみ更新します。
            updateSelectedValue(pickerElement);
        });
    }

    // イベントリスナーの設定
    scrollHandler(integerPicker);
    scrollHandler(decimalPicker);


    // === モーダル操作 ===
    
    /** モーダルを開く */
    function openModal(type) {
        currentTarget = type;
        
        let initialValue;
        let min, max;

        if (type === 'height') {
            modalTitle.textContent = '身長';
            modalUnit.textContent = 'cm';
            initialValue = parseFloat(heightInput.value);
            min = HEIGHT_MIN;
            max = HEIGHT_MAX;
        } else if (type === 'weight') {
            modalTitle.textContent = '体重';
            modalUnit.textContent = 'kg';
            initialValue = parseFloat(weightInput.value);
            min = WEIGHT_MIN;
            max = WEIGHT_MAX;
        }
        
        // 初期値の整数部と小数部を分割
        const integerPart = Math.floor(initialValue);
        // 小数部は四捨五入を避けて取得
        const decimalPart = Math.round((initialValue - integerPart) * 10);
        
        // ピッカーの生成
        generatePickerItems(integerPicker, min, max, integerPart);
        generatePickerItems(decimalPicker, DECIMAL_MIN, DECIMAL_MAX, decimalPart);

        // 初期選択状態の更新
        updateSelectedValue(integerPicker);
        updateSelectedValue(decimalPicker);
        
        modalOverlay.classList.add('is-active');
        modalOverlay.style.display = 'flex';
    }

    /** モーダルを閉じる */
    function closeModal() {
        modalOverlay.classList.remove('is-active');
        setTimeout(() => {
            modalOverlay.style.display = 'none';
        }, 300);
        currentTarget = null;
    }

    // モーダル表示トリガー
    clickableInputs.forEach(item => {
        item.addEventListener('click', (e) => {
            const target = item.dataset.target;
            openModal(target);
        });
    });

    // 確認ボタンクリック時の処理 (ピッカーからの値取得ロジックを追加)
    modalConfirmButton.addEventListener('click', () => {
        const integerValue = updateSelectedValue(integerPicker);
        const decimalValue = updateSelectedValue(decimalPicker);
        
        if (integerValue === null || decimalValue === null || currentTarget === null) {
             // 値が取得できない場合は処理を中止
            closeModal();
            return;
        }
        
        // 新しい値を計算
        const newValue = integerValue + (decimalValue / 10);
        const formattedValue = newValue.toFixed(1);
        
        // 1. 表示を更新
        // 2. フォームの隠しフィールドの値を更新
        if (currentTarget === 'weight') {
            weightDisplay.textContent = formattedValue;
            weightInput.value = formattedValue;
        } else if (currentTarget === 'height') {
            heightDisplay.textContent = formattedValue;
            heightInput.value = formattedValue;
        }
        
        // 3. 筋肉率/体脂肪率を再計算
        updateCalculatedData();
        
        closeModal();
    });


    // === 計算ロジック (既存のものを流用) ===
    
    function calculateBodyData(weight_kg, height_cm, age) {
        if (!weight_kg || !height_cm || height_cm <= 0) {
            return { muscle_percentage: 0.0, body_fat_percentage: 0.0 };
        }

        const height_m = height_cm / 100;
        const bmi = weight_kg / (height_m * height_m);
        
        let body_fat_percentage;
        let muscle_percentage;

        // 簡易的な計算ロジック (デモ用)
        if (bmi < 18.5) {
            body_fat_percentage = 15.0 - (bmi * 0.1) + (age * 0.05);
        } else if (bmi < 25) {
            body_fat_percentage = 20.0 + (age * 0.05);
        } else {
            body_fat_percentage = 25.0 + (bmi * 0.5) + (age * 0.1);
        }

        // 筋肉率の計算（骨/その他を15%と仮定）
        muscle_percentage = 100 - body_fat_percentage - 15;
        
        // 最小値/最大値の制限
        body_fat_percentage = Math.max(5.0, Math.min(50.0, body_fat_percentage));
        muscle_percentage = Math.max(10.0, Math.min(60.0, muscle_percentage));

        return {
            muscle_percentage: Math.round(muscle_percentage * 10) / 10,
            body_fat_percentage: Math.round(body_fat_percentage * 10) / 10,
        };
    }

    /**
     * 表示されている身長・体重に基づいて計算結果を更新する関数
     */
    function updateCalculatedData() {
        const weight = parseFloat(weightInput.value);
        const height = parseFloat(heightInput.value);

        const result = calculateBodyData(weight, height, age);

        muscleRateOutput.textContent = result.muscle_percentage.toFixed(1);
        bodyFatRateOutput.textContent = result.body_fat_percentage.toFixed(1);
    }

    // 初回ロード時にも計算が実行されるようにする
    // updateCalculatedData();

    // モーダル外クリックで閉じる処理 (既存のものを流用)
    modalOverlay.addEventListener('click', (e) => {
        if (e.target === modalOverlay) {
            closeModal();
        }
    });
});