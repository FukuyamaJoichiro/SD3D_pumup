<?php
session_start();
header('Content-Type: application/json');

if (isset($_POST['training_id'])) {
    $training_id = (int)$_POST['training_id'];
    
    if (isset($_SESSION['workout_trainings'])) {
        $key = array_search($training_id, $_SESSION['workout_trainings']);
        if ($key !== false) {
            unset($_SESSION['workout_trainings'][$key]);
            $_SESSION['workout_trainings'] = array_values($_SESSION['workout_trainings']);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'トレーニングが見つかりません']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'セッションが存在しません']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'トレーニングIDが指定されていません']);
}
?>