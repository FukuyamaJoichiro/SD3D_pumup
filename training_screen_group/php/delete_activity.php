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

$activity_id = $data['activity_id'] ?? null;

if (!$activity_id) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid ID provided."]);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. 削除対象の日付を取得
    $stmt_date = $pdo->prepare("SELECT activity_date FROM calendar_activity WHERE id = :id AND user_id = :user_id");
    $stmt_date->execute([':id' => $activity_id, ':user_id' => $user_id]);
    $target = $stmt_date->fetch(PDO::FETCH_ASSOC);

    if ($target) {
        $target_date = $target['activity_date'];

        // 【STEP 1】 workout_sets (一番深い階層) を削除
        // スーパーセットに関連付けられていないセットも、関連付けられているセットも一気に消します
        $stmt_sets = $pdo->prepare("
            DELETE FROM workout_sets 
            WHERE session_id IN (
                SELECT session_id FROM workout_sessions 
                WHERE user_id = :user_id AND date = :date
            )
        ");
        $stmt_sets->execute([':user_id' => $user_id, ':date' => $target_date]);

        // 【STEP 2】 supersets (中間階層) を削除
        // セットが消えたので、スーパーセット枠を消せるようになります
        $stmt_supersets = $pdo->prepare("
            DELETE FROM supersets 
            WHERE session_id IN (
                SELECT session_id FROM workout_sessions 
                WHERE user_id = :user_id AND date = :date
            )
        ");
        $stmt_supersets->execute([':user_id' => $user_id, ':date' => $target_date]);

        // 【STEP 3】 workout_sessions (親) を削除
        $stmt_session = $pdo->prepare("
            DELETE FROM workout_sessions 
            WHERE user_id = :user_id AND date = :date
        ");
        $stmt_session->execute([':user_id' => $user_id, ':date' => $target_date]);
    }

    // 【STEP 4】 calendar_activity (表示用) を最後に削除
    $stmt_main = $pdo->prepare("
        DELETE FROM calendar_activity 
        WHERE id = :id AND user_id = :user_id
    ");
    $stmt_main->bindValue(":id", $activity_id, PDO::PARAM_INT);
    $stmt_main->bindValue(":user_id", $user_id, PDO::PARAM_INT);
    $stmt_main->execute();

    if ($stmt_main->rowCount() > 0) {
        $pdo->commit();
        echo json_encode(["status" => "success", "message" => "削除が完了しました。"]);
    } else {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "対象が見つかりません。"]);
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}