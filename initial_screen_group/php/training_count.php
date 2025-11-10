<?php
// ファイル名: training_count.php (phpフォルダ内を想定)
session_start();

// HTMLフォームの name="training_count" から選択された値を取得
// 値は 1 から 7 の数字
$training_count = $_POST['training_count'] ?? null; 

if (empty($training_count)) {
    exit('エラー: トレーニング頻度が選択されていません。');
}

// 選択されたトレーニング頻度をセッション変数に保持
$_SESSION['training_frequency'] = $training_count;

// ★★★ 全ての入力が完了したと仮定し、登録内容確認画面へリダイレクト ★★★
// ※ この画面で初めてユーザーデータをDBにまとめて保存することも可能です。
header('Location: ../html/bodydate_view.php'); // 次の画面へのパスを調整してください
exit();

?>