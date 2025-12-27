<?php
session_start();
require_once('../../db_connect.php');

$user_id = $_SESSION['user_id'] ?? 1;
$today = date("Y-m-d");

header('Content-Type: application/json');

try {
    // 1. 今日のセッションで行った「すべての部位」を取得（重複なし）
    $stmt = $pdo->prepare("
        SELECT DISTINCT tp.part_id 
        FROM workout_sets ws
        JOIN training_parts tp ON ws.training_id = tp.training_id
        WHERE ws.user_id = ? 
          AND ws.session_id = (SELECT session_id FROM workout_sessions WHERE user_id = ? AND date = ? LIMIT 1)
    ");
    $stmt->execute([$user_id, $user_id, $today]);
    $parts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($parts) {
        foreach ($parts as $row) {
            $part_id = $row['part_id'];
            
            // ★ここを修正：ON DUPLICATE KEY UPDATE をやめて INSERT IGNORE にする
            // これで「同じ日の同じ部位」でなければ、新しい行として追加されます
            $sql = "INSERT IGNORE INTO calendar_activity (user_id, activity_date, session_type, part_id) 
                    VALUES (:uid, :date, 'WORKOUT', :part_id)";
            
            $stmt_save = $pdo->prepare($sql);
            $stmt_save->execute([
                ':uid' => $user_id,
                ':date' => $today,
                ':part_id' => $part_id
            ]);
        }
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // ... エラー処理 ...
}