<?php
date_default_timezone_set('Asia/Tokyo');
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once("../../auth.php"); 
require_once("../../db_connect.php"); 

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Authentication required"]);
    exit;
}
$user_id = $_SESSION['user_id']; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    exit;
}

$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

// 💡 以前の修正に合わせ、activity_id を優先して取得します
$activity_id = $data['activity_id'] ?? null;

if (!$activity_id) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid ID provided. JSからの送信データを確認してください。"]);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. workout_sets (子) を削除：セッションID経由で特定
    $stmt_sets = $pdo->prepare("
        DELETE FROM workout_sets 
        WHERE session_id IN (
            SELECT session_id FROM workout_sessions 
            WHERE user_id = :user_id 
            AND date = (SELECT activity_date FROM calendar_activity WHERE id = :id)
        )
    ");
    $stmt_sets->bindValue(":user_id", $user_id, PDO::PARAM_INT);
    $stmt_sets->bindValue(":id", $activity_id, PDO::PARAM_INT);
    $stmt_sets->execute();

    // 2. workout_sessions (親) を削除
    $stmt_session = $pdo->prepare("
        DELETE FROM workout_sessions 
        WHERE user_id = :user_id 
        AND date = (SELECT activity_date FROM calendar_activity WHERE id = :id)
    ");
    $stmt_session->bindValue(":user_id", $user_id, PDO::PARAM_INT);
    $stmt_session->bindValue(":id", $activity_id, PDO::PARAM_INT);
    $stmt_session->execute();

    // 3. calendar_activity (カレンダー) を削除
    $stmt_main = $pdo->prepare("
        DELETE FROM calendar_activity 
        WHERE id = :id AND user_id = :user_id
    ");
    $stmt_main->bindValue(":id", $activity_id, PDO::PARAM_INT);
    $stmt_main->bindValue(":user_id", $user_id, PDO::PARAM_INT);
    $stmt_main->execute();

    if ($stmt_main->rowCount() > 0) {
        $pdo->commit();
        echo json_encode(["status" => "success", "message" => "Removed successfully."]);
    } else {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "No matching record found."]);
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}