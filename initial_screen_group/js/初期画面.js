// 表示するコンテンツの配列
const contents = [
    {
        text: 'トレーニング記録を通して運動を<br>習慣化しましょう',
        image: "../images/Gorifit.初期画面1.png", // Image 1
        buttonText: '次へ'
    },
    {
        text: '目標に基づいてコンテンツと<br>トレーニングをおすすめします',
        image: '../images/Gorifit.初期画面2.png', // Image 2
        buttonText: '次へ'
    },
    {
        text: 'たくさんのメニューから<br>好きなメニューを選んで始めよう',
        image: '../images/Gorifit.初期画面3.png', // Image 3
        buttonText: '次へ'
    },
    {
        text: '記録で成果が見えるから<br>トレーニングがもっと楽しく続く',
        image: '../images/Gorifit.初期画面4.png', // Image 4
        buttonText: '次へ'
    },
    {
        text: 'さあ！<br>理想の自分へ踏み出そう！',
        image: '../images/Gorifit.初期画面5.png', // Image 5
        buttonText: 'トレーニングを始める'
    }
];

let currentIndex = 0;
let isSwiping = false; // スワイプ中かを判定するフラグ

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
        window.location.href = 'S3ログイン画面.html';
        return;
    }

    onboardingText.innerHTML = contents[currentIndex].text;
    onboardingImage.src = contents[currentIndex].image;
    nextButton.textContent = contents[currentIndex].buttonText;
    
    for (let i = 0; i < dots.length; i++) {
        dots[i].classList.remove('active');
    }
    dots[currentIndex].classList.add('active');
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

// 次へボタンがクリックされたときの処理
nextButton.addEventListener('click', () => {
    changeScreen('next');
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
        if (diffX > 0) {
            changeScreen('prev');
        } else {
            changeScreen('next');
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
            changeScreen('next');
        } else { // 左半分をクリック
            changeScreen('prev');
        }
    }
    isSwiping = false; // フラグをリセット
});

// 初期コンテンツの表示
updateContent();