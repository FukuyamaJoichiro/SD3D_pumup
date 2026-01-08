<?php
// training_add.php

session_start();

// データベース接続
require_once '../../db_connect.php';

// JSONレスポンスを返す
header('Content-Type: application/json');

// ログインユーザーID取得
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode([
        'success' => false,
        'message' => 'ログイン情報が取得できません'
    ]);
    exit;
}

try {
    // POSTデータを取得
    $training_name = $_POST['training_name'] ?? '';
    $part_id = $_POST['part_id'] ?? '';
    $tool_id = $_POST['tool_id'] ?? '';
    $type_id = $_POST['type_id'] ?? '';
    
    // バリデーション
    if (empty($training_name) || empty($part_id) || empty($tool_id) || empty($type_id)) {
        echo json_encode([
            'success' => false,
            'message' => '全ての項目を入力してください'
        ]);
        exit;
    }
    
    // トランザクション開始
    $pdo->beginTransaction();
    
    // trainingsテーブルに挿入（★ created_user_id を追加）
    $stmt = $pdo->prepare("
        INSERT INTO trainings (training_name, created_user_id)
        VALUES (?, ?)
    ");
    $stmt->execute([$training_name, $user_id]);
    $training_id = $pdo->lastInsertId();
    
    // training_parts
    $stmt = $pdo->prepare("
        INSERT INTO training_parts (training_id, part_id)
        VALUES (?, ?)
    ");
    $stmt->execute([$training_id, $part_id]);
    
    // training_tools
    $stmt = $pdo->prepare("
        INSERT INTO training_tools (training_id, tool_id)
        VALUES (?, ?)
    ");
    $stmt->execute([$training_id, $tool_id]);
    
    // training_types（複数）
    $type_ids = explode(',', $type_id);
    $stmt = $pdo->prepare("
        INSERT INTO training_types (training_id, type_id)
        VALUES (?, ?)
    ");
    foreach ($type_ids as $tid) {
        $stmt->execute([$training_id, trim($tid)]);
    }
    
    // コミット
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'トレーニングを追加しました',
        'training_id' => $training_id
    ]);
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'データベースエラー: ' . $e->getMessage()
    ]);
}
