<?php
require_once('../../db_connect.php');
session_start();
header('Content-Type: application/json; charset=UTF-8');

$user_id = $_SESSION['user_id'] ?? 1;

$first  = filter_input(INPUT_POST, 'first_training_id', FILTER_VALIDATE_INT);
$second = filter_input(INPUT_POST, 'second_training_id', FILTER_VALIDATE_INT);

if (!$first || !$second) {
    echo json_encode(['success' => false, 'message' => '不正な値です']);
    exit;
}
if ($first === $second) {
    echo json_encode(['success' => false, 'message' => '同じ種目は選べません']);
    exit;
}

// 事故防止：選択済みに含まれているか（training_select.php が同期している前提）
$selected = $_SESSION['workout_trainings'] ?? [];
if (!is_array($selected) || !in_array($first, $selected, true) || !in_array($second, $selected, true)) {
    echo json_encode(['success' => false, 'message' => '選択済みトレーニングではありません']);
    exit;
}

try {
    $today_date = date('Y-m-d');

    // 今日のセッション取得
    $stmt = $pdo->prepare("SELECT session_id FROM workout_sessions WHERE user_id = ? AND date = ?");
    $stmt->execute([$user_id, $today_date]);
    $session_id = (int)$stmt->fetchColumn();

    if (!$session_id) {
        echo json_encode(['success' => false, 'message' => '本日のセッションが見つかりません']);
        exit;
    }

    // workout_sets に両方のトレーニングが存在するか確認
    $stmt = $pdo->prepare("
        SELECT training_id, COUNT(*) AS cnt
        FROM workout_sets
        WHERE session_id = ?
          AND training_id IN (?, ?)
        GROUP BY training_id
    ");
    $stmt->execute([$session_id, $first, $second]);
    $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // [training_id => cnt]

    if (empty($rows[$first]) || empty($rows[$second])) {
        echo json_encode([
            'success' => false,
            'message' => 'DB上にトレーニングが登録されていません（先にトレーニング追加してください）'
        ]);
        exit;
    }

    $pdo->beginTransaction();

    // すでにどちらかが別supersetに入っていたら解除（事故防止）
    $stmt = $pdo->prepare("
        UPDATE workout_sets
        SET superset_id = NULL
        WHERE session_id = ?
          AND training_id IN (?, ?)
    ");
    $stmt->execute([$session_id, $first, $second]);

    // ✅ supersets にレコード作成（session_idだけでOK）
    // order_no は NULL のままでOK
    $stmt = $pdo->prepare("INSERT INTO supersets (session_id) VALUES (?)");
    $stmt->execute([$session_id]);
    $new_superset_id = (int)$pdo->lastInsertId();

    // ✅ workout_sets を更新（FKを満たす）
    $stmt = $pdo->prepare("
        UPDATE workout_sets
        SET superset_id = ?
        WHERE session_id = ?
          AND training_id IN (?, ?)
    ");
    $stmt->execute([$new_superset_id, $session_id, $first, $second]);

    // 2種目のどちらかのセットが更新されていればOK（rowCountは実データ次第でブレるので厳密チェックしない）
    $pdo->commit();

    // 互換：セッションにも保存（使ってるなら）
    if (!isset($_SESSION['supersets']) || !is_array($_SESSION['supersets'])) {
        $_SESSION['supersets'] = [];
    }
    $_SESSION['supersets'][(int)$first] = (int)$second;

    echo json_encode([
        'success' => true,
        'superset_id' => $new_superset_id
    ]);
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'DB更新に失敗しました: ' . $e->getMessage()
    ]);
    exit;
}
