<?php
session_start();

require_once '../../db_connect.php';

$weight = $_POST['weight'] ?? null;
$height = $_POST['height'] ?? null;
$birthday = $_POST['birthday'] ?? null;
$gender = $_POST['gender'] ?? null;

$user_name = null; // フォームにないため
$weight_musc = null; // フォームにないため
$bodyfat = null; // フォームにないため
$goal_detail = null; // フォームにないため

if (empty($weight) || empty($height) || empty($birthday) || empty($gender)) {
    exit('必須項目（体重、身長、生年月日、性別）が入力されていません。');
}

$sql = "INSERT INTO users (
            user_name, birthday, gender, height, weight, muscle_ptc, bodyfat_ptc, goal_detail
        ) VALUES (
            :user_name, :birthday, :gender, :height, :weight, :muscle_ptc, :bodyfat_ptc, :goal_detail
        )";

        try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_name' => $user_name,
        ':birthday' => $birthday,
        ':gender' => $gender,
        ':height' => $height,
        ':weight' => $weight,
        ':muscle_ptc' => $muscle_ptc,
        ':bodyfat_ptc' => $bodyfat_ptc,
        ':goal_detail' => $goal_detail,
    ]);
    header('Location: ../html/training_experience.html');
    exit();
    } catch (PDOException $e) {
    // SQL実行失敗時の処理
    exit('データ登録中にエラーが発生しました: ' . $e->getMessage());
}
?>