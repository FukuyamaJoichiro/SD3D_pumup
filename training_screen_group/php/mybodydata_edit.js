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

    // PHPから渡されたデータ (HTML側のscriptタグで定義されている前提)
    const age = typeof userAge !== 'undefined' ? userAge : 30; 
    const gender = typeof userGender !== 'undefined' ? userGender : 1; // 1:男性, 0:女性

    let currentTarget = null;
    const PICKER_ITEM_HEIGHT = 50; 
    
    // ピッカーの範囲設定
    const WEIGHT_MIN = 30;
    const WEIGHT_MAX = 150;
    const HEIGHT_MIN = 100;
    const HEIGHT_MAX = 220;
    const DECIMAL_MIN = 0;
    const DECIMAL_MAX = 9;

    // === ピッカー生成と操作ロジック ===
    
    function generatePickerItems(pickerElement, min, max, initialValue) {
        pickerElement.innerHTML = '';
        const values = [];
        for (let i = min; i <= max; i++) {
            values.push(i);
        }
        
        values.forEach(value => {
            const item = document.createElement('div');
            item.classList.add('picker-item');
            item.textContent = value;
            item.dataset.value = value;
            pickerElement.appendChild(item);
        });
        
        const index = values.indexOf(initialValue);
        if (index >= 0) {
            pickerElement.scrollTop = index * PICKER_ITEM_HEIGHT;
        }
    }

    function updateSelectedValue(pickerElement) {
        const scrollTop = pickerElement.scrollTop;
        const selectedIndex = Math.round(scrollTop / PICKER_ITEM_HEIGHT);
        
        pickerElement.querySelectorAll('.picker-item').forEach(item => {
            item.classList.remove('selected');
            item.style.color = '#999';
        });

        const selectedItem = pickerElement.children[selectedIndex];
        if (selectedItem) {
            selectedItem.classList.add('selected');
            selectedItem.style.color = '#333';
            return parseInt(selectedItem.dataset.value);
        }
        return null;
    }

    function scrollHandler(pickerElement) {
        let scrollTimeout;
        pickerElement.addEventListener('scroll', () => {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                const scrollTop = pickerElement.scrollTop;
                const snapPosition = Math.round(scrollTop / PICKER_ITEM_HEIGHT) * PICKER_ITEM_HEIGHT;
                pickerElement.scrollTo({ top: snapPosition, behavior: 'auto' });
                updateSelectedValue(pickerElement);
            }, 100);
            updateSelectedValue(pickerElement);
        });
    }

    scrollHandler(integerPicker);
    scrollHandler(decimalPicker);

    // === モーダル操作 ===
    
    function openModal(type) {
        currentTarget = type;
        let initialValue;
        let min, max;

        if (type === 'height') {
            modalTitle.textContent = '身長';
            modalUnit.textContent = 'cm';
            initialValue = parseFloat(heightInput.value) || 170.0;
            min = HEIGHT_MIN;
            max = HEIGHT_MAX;
        } else if (type === 'weight') {
            modalTitle.textContent = '体重';
            modalUnit.textContent = 'kg';
            initialValue = parseFloat(weightInput.value) || 60.0;
            min = WEIGHT_MIN;
            max = WEIGHT_MAX;
        }
        
        const integerPart = Math.floor(initialValue);
        const decimalPart = Math.round((initialValue - integerPart) * 10);
        
        generatePickerItems(integerPicker, min, max, integerPart);
        generatePickerItems(decimalPicker, DECIMAL_MIN, DECIMAL_MAX, decimalPart);

        updateSelectedValue(integerPicker);
        updateSelectedValue(decimalPicker);
        
        modalOverlay.classList.add('is-active');
        modalOverlay.style.display = 'flex';
    }

    function closeModal() {
        modalOverlay.classList.remove('is-active');
        setTimeout(() => { modalOverlay.style.display = 'none'; }, 300);
        currentTarget = null;
    }

    clickableInputs.forEach(item => {
        item.addEventListener('click', () => {
            openModal(item.dataset.target);
        });
    });

    modalConfirmButton.addEventListener('click', () => {
        const integerValue = updateSelectedValue(integerPicker);
        const decimalValue = updateSelectedValue(decimalPicker);
        
        if (integerValue === null || decimalValue === null || currentTarget === null) {
            closeModal();
            return;
        }
        
        const newValue = integerValue + (decimalValue / 10);
        const formattedValue = newValue.toFixed(1);
        
        if (currentTarget === 'weight') {
            weightDisplay.textContent = formattedValue;
            weightInput.value = formattedValue;
        } else if (currentTarget === 'height') {
            heightDisplay.textContent = formattedValue;
            heightInput.value = formattedValue;
        }
        
        updateCalculatedData();
        closeModal();
    });

    // === 計算ロジック (Deurenbergの推定式に更新) ===
    
    function calculateBodyData(weight_kg, height_cm, user_age) {
        if (!weight_kg || !height_cm || height_cm <= 0) {
            return { muscle_percentage: 0.0, body_fat_percentage: 0.0 };
        }

        const height_m = height_cm / 100;
        const bmi = weight_kg / (height_m * height_m);
        
        /**
         * 【Deurenbergの推定式】
         * PHP側と完全に一致させています
         */
        let body_fat = (1.20 * bmi) + (0.23 * user_age) - (10.8 * gender) - 5.4;

        // 範囲制限 (5%〜50%)
        body_fat = Math.max(5.0, Math.min(50.0, body_fat));

        /**
         * 【筋肉率の推定】
         * 100% - 体脂肪率 - 固定係数(18.0)
         */
        let muscle = 100 - body_fat - 18.0;
        
        // 範囲制限 (10%〜60%)
        muscle = Math.max(10.0, Math.min(60.0, muscle));

        return {
            muscle_percentage: Math.round(muscle * 10) / 10,
            body_fat_percentage: Math.round(body_fat * 10) / 10,
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

    // モーダル外クリックで閉じる処理
    modalOverlay.addEventListener('click', (e) => {
        if (e.target === modalOverlay) closeModal();
    });

    // 初回計算の実行
    updateCalculatedData();
});