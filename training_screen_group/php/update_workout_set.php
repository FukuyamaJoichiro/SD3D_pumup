<?php
session_start();
require_once('../../db_connect.php');

header('Content-Type: application/json');

// セッションから取得、なければDBにある45番を一旦デフォルトにする
$user_id     = $_SESSION['user_id'] ?? 45; 
$training_id = $_POST['training_id'] ?? null;
$weight      = $_POST['weight'] ?? 0;
$reps        = $_POST['reps'] ?? 0;
$session_id  = $_POST['session_id'] ?? null;
$set_index   = isset($_POST['set_index']) ? (int)$_POST['set_index'] : 0;

if (!$training_id || !$session_id) {
    echo json_encode(['success' => false, 'message' => 'IDが不足しています']);
    exit;
}

try {
    // ユーザーIDの条件をあえて外して、セッションと種目だけで探してみる（テスト用）
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
            'val' => "{$weight}kg / {$reps}回"
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => '該当セットなし',
            'debug' => ['sid' => $session_id, 'tid' => $training_id, 'count' => count($ids)]
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}