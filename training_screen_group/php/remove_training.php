<?php
ob_start(); // 余計な出力を防止
require_once('../../db_connect.php');
session_start();

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

try {
    $user_id = $_SESSION['user_id'] ?? null; 
    $training_id = $_POST['training_id'] ?? null;
    $session_id = $_POST['session_id'] ?? null; 

    if (!$user_id) throw new Exception("ログインが必要です。");
    if (empty($training_id) || empty($session_id)) {
        throw new Exception("データ不足(TID:$training_id, SID:$session_id)");
    }

    // 削除実行
    $stmt = $pdo->prepare("DELETE FROM workout_sets WHERE session_id = ? AND training_id = ? AND user_id = ?");
    $stmt->execute([(int)$session_id, (int)$training_id, (int)$user_id]);

    $response['success'] = true; // 0件でも成功とみなす設定を維持
} catch (Exception $e) {
    $response['message'] = $e->getMessage(); 
}

ob_end_clean(); // バッファを空にする
echo json_encode($response);
exit;