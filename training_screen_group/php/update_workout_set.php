<?php
session_start();
require_once('../../db_connect.php');

header('Content-Type: application/json');

// セッションからユーザーID取得
$user_id     = $_SESSION['user_id'] ?? 45; 
$training_id = $_POST['training_id'] ?? null;
$weight      = $_POST['weight'] ?? 0;
$reps        = $_POST['reps'] ?? 0;
$set_index   = isset($_POST['set_index']) ? (int)$_POST['set_index'] : 0;
// ★追加：JSから送られてきた日付
$target_date = $_POST['date'] ?? date("Y-m-d");

try {
    // ★重要：送られてきた日付に対応する session_id を取得し直す
    $stmt_sid = $pdo->prepare("SELECT session_id FROM workout_sessions WHERE user_id = ? AND date = ?");
    $stmt_sid->execute([$user_id, $target_date]);
    $session_id = $stmt_sid->fetchColumn();

    if (!$session_id) {
        echo json_encode(['success' => false, 'message' => '該当するセッションが見つかりません']);
        exit;
    }

    // 正しい session_id を使ってセットを探す
    $stmt = $pdo->prepare("
        SELECT set_id FROM workout_sets 
        WHERE session_id = ? AND training_id = ?
        ORDER BY set_id ASC
    ");
    $stmt->execute([$session_id, $training_id]);
    $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (isset($ids[$set_index])) {
        $target_id = $ids[$set_index];
        $update = $pdo->prepare("UPDATE workout_sets SET weight = ?, reps = ? WHERE set_id = ?");
        $update->execute([$weight, $reps, $target_id]);
        
        echo json_encode([
            'success' => true, 
            'updated_id' => $target_id,
            'val' => "{$weight}kg / {$reps}回",
            'date' => $target_date // デバッグ用
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => '該当セットなし',
            'debug' => ['sid' => $session_id, 'tid' => $training_id, 'count' => count($ids), 'date' => $target_date]
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}