
<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// =============================
// トレーニング削除（セッション）
// =============================

// DB接続（今回は使わなくてもOKだが統一）
require_once('../../db_connect.php');

session_start();

// JSON を返す
header('Content-Type: application/json');

// POSTで受け取る
$training_id = $_POST['training_id'] ?? null;

// validation
if (!$training_id) {
    echo json_encode([
        'success' => false,
        'message' => 'training_id が送信されていません'
    ]);
    exit;
}

// セッションにトレーニングが無ければエラー
if (!isset($_SESSION['workout_trainings']) || !is_array($_SESSION['workout_trainings'])) {
    echo json_encode([
        'success' => false,
        'message' => '削除対象が存在しません'
    ]);
    exit;
}

// セッションから削除
$_SESSION['workout_trainings'] = array_values(
    array_filter(
        $_SESSION['workout_trainings'],
        fn($id) => (int)$id !== (int)$training_id
    )
);

// 成功レスポンス
echo json_encode([
    'success' => true
]);
exit;
