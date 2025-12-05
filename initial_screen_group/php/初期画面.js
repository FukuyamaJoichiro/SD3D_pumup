// 表示するコンテンツの配列
const contents = [
    {
        text: 'トレーニング記録を通して運動を<br>習慣化しましょう',
        image: "../images/Gorifit.初期画面1.png", // Image 1
        buttonText: 'トレーニングを始める'
    },
    {
        text: '目標に基づいてコンテンツと<br>トレーニングをおすすめします',
        image: '../images/Gorifit.初期画面2.png', // Image 2
        buttonText: 'トレーニングを始める'
    },
    {
        text: 'たくさんのメニューから<br>好きなメニューを選んで始めよう',
        image: '../images/Gorifit.初期画面3.png', // Image 3
        buttonText: 'トレーニングを始める'
    },
    {
        text: '記録で成果が見えるから<br>トレーニングがもっと楽しく続く',
        image: '../images/Gorifit.初期画面4.png', // Image 4
        buttonText: 'トレーニングを始める'
    },
    {
        text: 'さあ！<br>理想の自分へ踏み出そう！',
        image: '../images/Gorifit.初期画面5.png', // Image 5
        buttonText: 'トレーニングを始める'
    }
];

let currentIndex = 0;
let isSwiping = false; // スワイプ中かを判定するフラグ
// 最後のコンテンツのインデックス
const lastIndex = contents.length - 1;

// HTML要素の取得
const onboardingText = document.getElementById('onboarding-text');
const onboardingImage = document.getElementById('onboarding-image');
const nextButton = document.getElementById('next-button');
const dotsContainer = document.getElementById('dots-container');
const dots = dotsContainer.getElementsByClassName('dot');
const imageArea = document.getElementById('image-area');

// コンテンツを更新する関数
function updateContent() {
    if (currentIndex < 0) {
        currentIndex = 0;
    } else if (currentIndex >= contents.length) {
        // スワイプ/タップによる画面外への移動を防いでも、
        // 万が一画面外へ進んだ場合のフォールバックとして残しておきます。
        window.location.href = '/pumpup/SD3D_pumup/initial_screen_group/php/login.php';
        return;
    }

    onboardingText.innerHTML = contents[currentIndex].text;
    onboardingImage.src = contents[currentIndex].image;
    nextButton.textContent = contents[currentIndex].buttonText;
    
    // ドットの更新
    for (let i = 0; i < dots.length; i++) {
        dots[i].classList.remove('active');
    }
    // currentIndexがcontents.length未満であることを確認してからクラスを追加
    if (currentIndex < dots.length) {
        dots[currentIndex].classList.add('active');
    }
}

// 画面を切り替える関数
function changeScreen(direction) {
    if (direction === 'next') {
        currentIndex++;
    } else if (direction === 'prev') {
        currentIndex--;
    }
    updateContent();
}

// ✅ 修正済み: 次へボタンがクリックされたときの処理
// どの画面でもボタンを押したらログインページへ遷移
nextButton.addEventListener('click', () => {
    window.location.href = '/pumpup/SD3D_pumup/initial_screen_group/php/login.php';
});

// スワイプイベントの統合処理
let startX = 0;
imageArea.addEventListener('touchstart', handleStart);
imageArea.addEventListener('mousedown', handleStart);
imageArea.addEventListener('touchend', handleEnd);
imageArea.addEventListener('mouseup', handleEnd);

function handleStart(e) {
    isSwiping = false;
    startX = (e.touches ? e.touches[0].clientX : e.clientX);
}

function handleEnd(e) {
    const endX = (e.changedTouches ? e.changedTouches[0].clientX : e.clientX);
    const diffX = endX - startX;
    
    // スワイプ判定
    if (Math.abs(diffX) > 50) {
        if (diffX > 0) { // 右へスワイプ (前へ戻る)
            changeScreen('prev');
        } else { // 左へスワイプ (次へ進む)
            // 🎯 修正1: 最後の画面で「次へ」の操作を無効化
            // 現在のインデックスが最後のインデックス未満の場合のみ、次に進む
            if (currentIndex < lastIndex) {
                changeScreen('next');
            }
        }
        isSwiping = true;
    }
}

// タップ（クリック）イベントの処理
imageArea.addEventListener('click', (e) => {
    // スワイプ操作でない場合にのみ実行
    if (!isSwiping) {
        const rect = imageArea.getBoundingClientRect();
        const clickX = e.clientX - rect.left;
        
        if (clickX > rect.width / 2) { // 右半分をクリック
            // 🎯 修正2: 最後の画面で右半分クリックの「次へ」の操作を無効化
            // 現在のインデックスが最後のインデックス未満の場合のみ、次に進む
            if (currentIndex < lastIndex) {
                changeScreen('next');
            }
        } else { // 左半分をクリック
            changeScreen('prev');
        }
    }
    isSwiping = false; // フラグをリセット
});

// 初期コンテンツの表示
updateContent();