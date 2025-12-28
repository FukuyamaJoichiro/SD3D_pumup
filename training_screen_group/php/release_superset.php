<?php
require_once('../../db_connect.php');
session_start();
header('Content-Type: application/json; charset=UTF-8');

$user_id = $_SESSION['user_id'] ?? null;
$training_id = filter_input(INPUT_POST, 'training_id', FILTER_VALIDATE_INT);

if (!$user_id || !$training_id) {
    echo json_encode(['success' => false, 'message' => '不正なリクエスト']);
    exit;
}

try {
    // このトレーニングの superset_id を取得
    $stmt = $pdo->prepare("
        SELECT superset_id
        FROM workout_sets
        WHERE training_id = ?
        AND user_id = ?
        AND superset_id IS NOT NULL
        LIMIT 1
    ");
    $stmt->execute([$training_id, $user_id]);
    $superset_id = $stmt->fetchColumn();

    if (!$superset_id) {
        echo json_encode(['success' => false, 'message' => 'スーパーセットではありません']);
        exit;
    }

    // 同じ superset_id を持つ全セットを解除
    $stmt = $pdo->prepare("
        UPDATE workout_sets
        SET superset_id = NULL
        WHERE superset_id = ?
        AND user_id = ?
    ");
    $stmt->execute([$superset_id, $user_id]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
