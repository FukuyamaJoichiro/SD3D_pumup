<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

$first = filter_input(INPUT_POST, 'first_training_id', FILTER_VALIDATE_INT);
$second = filter_input(INPUT_POST, 'second_training_id', FILTER_VALIDATE_INT);

if (!$first || !$second) {
    echo json_encode(['success' => false, 'message' => '不正な値です']);
    exit;
}

// workout_trainings に含まれてるかチェック（事故防止）
$selected = $_SESSION['workout_trainings'] ?? [];
if (!is_array($selected) || !in_array($first, $selected, true) || !in_array($second, $selected, true)) {
    echo json_encode(['success' => false, 'message' => '選択済みトレーニングではありません']);
    exit;
}

// 同一はダメ
if ($first === $second) {
    echo json_encode(['success' => false, 'message' => '同じ種目は選べません']);
    exit;
}

// supersets（parent => child）を保存
if (!isset($_SESSION['supersets']) || !is_array($_SESSION['supersets'])) {
    $_SESSION['supersets'] = [];
}
$_SESSION['supersets'][$first] = $second;

echo json_encode(['success' => true]);
