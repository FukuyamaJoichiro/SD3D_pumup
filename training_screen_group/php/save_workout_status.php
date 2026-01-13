<?php
session_start();
require_once('../../db_connect.php');

$user_id = $_SESSION['user_id'] ?? 1;

// ★修正：JSから送られてきた日付（date）を取得。なければ今日の日付にする。
$target_date = isset($_POST['date']) ? $_POST['date'] : date("Y-m-d");

header('Content-Type: application/json');

try {
    // 1. 指定された日付（$target_date）のセッションで行った「すべての部位」を取得
    $stmt = $pdo->prepare("
        SELECT DISTINCT tp.part_id 
        FROM workout_sets ws
        JOIN training_parts tp ON ws.training_id = tp.training_id
        WHERE ws.user_id = ? 
          AND ws.session_id = (SELECT session_id FROM workout_sessions WHERE user_id = ? AND date = ? LIMIT 1)
    ");
    // $today ではなく、$target_date（12日など）を使用してセッションを検索する
    $stmt->execute([$user_id, $user_id, $target_date]);
    $parts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($parts) {
        foreach ($parts as $row) {
            $part_id = $row['part_id'];
            
            // 指定された日付（$target_date）で calendar_activity に保存または更新
            // すでに「おやすみ」などが入っている場合を考慮し、WORKOUT で上書きするロジックが望ましい
            $sql = "INSERT INTO calendar_activity (user_id, activity_date, session_type, part_id) 
                    VALUES (:uid, :date, 'WORKOUT', :part_id)
                    ON DUPLICATE KEY UPDATE session_type = 'WORKOUT', part_id = :part_id_update";
            
            $stmt_save = $pdo->prepare($sql);
            $stmt_save->execute([
                ':uid' => $user_id,
                ':date' => $target_date,
                ':part_id' => $part_id,
                ':part_id_update' => $part_id
            ]);
        }
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}