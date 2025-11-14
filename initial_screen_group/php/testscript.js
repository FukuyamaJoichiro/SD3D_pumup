// 3秒後に次のページに自動的に遷移するタイマーを設定
const timer = setTimeout(() => {
    navigateToNextPage();
}, 3000); // 3000ミリ秒 = 3秒

// 次のページに遷移する関数
function navigateToNextPage() {
    // 既に遷移済みかチェック（複数回の遷移を防ぐため）
    if (document.body.classList.contains('navigated')) {
        return;
    }
    document.body.classList.add('navigated');

    // タイマーがまだ残っている場合はクリア
    clearTimeout(timer);
      // 画面全体を取得
    const screen = document.querySelector('.phone-screen');
      // 画面の透明度を0にして、フェードアウトを開始
    screen.style.opacity = '0';
    // フェードアウトが完了した後に次のページに遷移
    setTimeout(() =>{
    window.location.href = 'intro.php'; // パスは修正済み
    }, 500);
    // 画面クリックで遷移
    document.querySelector('.phone-screen').addEventListener('click', navigateToNextPage);
}