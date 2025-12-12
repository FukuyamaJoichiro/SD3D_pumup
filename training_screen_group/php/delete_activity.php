<?php
// PHPエラー表示設定 (開発時のみ)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ヘッダー設定：CORS対応とJSONレスポンス
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// セッション開始
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 認証・DB接続ファイルの読み込み
require_once("../../auth.php"); 
require_once("../../db_connect.php"); 

// ==========================================================
// 🚨 APIとしての認証チェック (require_login() の代替) 🚨
// ログインしていない場合はリダイレクトせず、JSONエラーを返す
// ==========================================================
if (!isset($_SESSION['user_id'])) {
     http_response_code(401); // Unauthorized
    echo json_encode(["status" => "error", "message" => "Authentication required (User not logged in)"]);
    exit;
}
$user_id = $_SESSION['user_id']; 

// 【DB接続チェック】db_connect.phpが失敗した場合の確認
if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection failed (PDO not set)."]);
     exit;
}


// POSTリクエスト以外は拒否
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    exit;
}

// JSONデータを受け取る
$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

// 必要なデータの検証
$date = $data['date'] ?? null;
$type = $data['type'] ?? null; 

if (!$date || $type !== 'REST') {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid date or activity type provided"]);
    exit;
}

try {
    // DB削除クエリの実行
    $sql = "
        DELETE FROM calendar_activity 
        WHERE user_id = :user_id 
        AND activity_date = :activity_date 
        AND session_type = 'REST'
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->bindValue(":activity_date", $date);
    $stmt->execute();

    // 削除件数をチェック
    if ($stmt->rowCount() > 0) {
        // 成功レスポンス
         echo json_encode(["status" => "success", "message" => "Rest day removed successfully.", "date" => $date]);
     } else {
        // 該当するデータがなかった場合
        http_response_code(404);
         echo json_encode(["status" => "error", "message" => "No matching rest record found."]);
     }

} catch (PDOException $e) {
    // DBエラーが発生した場合
     http_response_code(500);
     echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}