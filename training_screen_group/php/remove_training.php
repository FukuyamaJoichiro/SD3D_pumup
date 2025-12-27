<?php
ob_start(); 
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
        throw new Exception("データ不足");
    }

    $pdo->beginTransaction();

    // 1. 指定された種目を削除
    $stmt = $pdo->prepare("DELETE FROM workout_sets WHERE session_id = ? AND training_id = ? AND user_id = ?");
    $stmt->execute([(int)$session_id, (int)$training_id, (int)$user_id]);

    // 2. 【重要】もしその日の種目が0件になったら、カレンダーの「WORKOUT」記録も消す
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM workout_sets WHERE session_id = ?");
    $stmt_check->execute([(int)$session_id]);
    $count = $stmt_check->fetchColumn();

    if ($count == 0) {
        // セッションから日付を取得して calendar_activity を掃除
        $stmt_date = $pdo->prepare("SELECT date FROM workout_sessions WHERE session_id = ?");
        $stmt_date->execute([(int)$session_id]);
        $date = $stmt_date->fetchColumn();

        if ($date) {
            $stmt_del_cal = $pdo->prepare("DELETE FROM calendar_activity WHERE user_id = ? AND activity_date = ? AND session_type = 'WORKOUT'");
            $stmt_del_cal->execute([$user_id, $date]);
        }
    }

    $pdo->commit();
    $response['success'] = true;

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $response['message'] = $e->getMessage(); 
}

ob_end_clean();
echo json_encode($response);
exit;