<?php
// bookmark_toggle.php
// データベース接続
require_once ('../../db_connect.php');

// JSONレスポンスを返す
header('Content-Type: application/json');

session_start();

try {
    // POSTデータを取得
    $training_id = $_POST['training_id'] ?? '';
    $user_id = $_SESSION['user_id'] ?? 1; // セッションからユーザーIDを取得（仮で1）
    
    // バリデーション
    if (empty($training_id)) {
        echo json_encode(['success' => false, 'message' => 'トレーニングIDが指定されていません']);
        exit;
    }
    
    // 既にブックマークされているか確認
    $stmt = $pdo->prepare("SELECT bookmark_id FROM bookmarks WHERE user_id = ? AND training_id = ?");
    $stmt->execute([$user_id, $training_id]);
    $bookmark = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($bookmark) {
        // ブックマークを削除
        $stmt = $pdo->prepare("DELETE FROM bookmarks WHERE bookmark_id = ?");
        $stmt->execute([$bookmark['bookmark_id']]);
        
        echo json_encode([
            'success' => true,
            'action' => 'removed',
            'message' => 'ブックマークを解除しました'
        ]);
    } else {
        // ブックマークを追加
        $stmt = $pdo->prepare("INSERT INTO bookmarks (user_id, training_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $training_id]);
        
        echo json_encode([
            'success' => true,
            'action' => 'added',
            'message' => 'ブックマークに追加しました'
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'データベースエラー: ' . $e->getMessage()
    ]);
}
?>