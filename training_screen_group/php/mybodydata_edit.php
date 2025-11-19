<?php
// パスを修正: 認証とDB接続ファイルの読み込み
require_once '../../auth.php';
require_login(); 

global $pdo; 

// 現在ログイン中のユーザーIDを取得
$logged_in_user_id = $_SESSION['user_id']; 

try {
    // 必要なデータ (身長、体重、生年月日) をDBから取得
    $stmt = $pdo->prepare("SELECT height, weight, birthday FROM users WHERE user_id = :id");
    $stmt->bindParam(':id', $logged_in_user_id, PDO::PARAM_INT);
    $stmt->execute();
    $db_data = $stmt->fetch();

    if (!$db_data) {
        logout();
        header("Location: /pumpup/SD3D_pumup/initial_screen_group/php/login.php");
        exit;
    }

    // データの整形と年齢計算
    $weight = (float)$db_data['weight'];
    $height = (float)$db_data['height'];
    
    // 生年月日 ('date'型) から年齢を計算
    $birthday = new DateTime($db_data['birthday']);
    $today = new DateTime('today');
    $age = $birthday->diff($today)->y;

} catch (Exception $e) {
    error_log("データ取得エラー: " . $e->getMessage());
    exit("システムエラーが発生しました。時間を置いて再度お試しください。");
}

$height_m = $height / 100;
$bmi = ($height_m > 0) ? $weight / ($height_m * $height_m) : 0; 

$body_fat_percentage = 0;
if ($bmi < 18.5) {
    $body_fat_percentage = 15.0 - ($bmi * 0.1) + ($age * 0.05); 
} elseif ($bmi < 25) {
    $body_fat_percentage = 20.0 + ($age * 0.05); 
} else {
    $body_fat_percentage = 25.0 + ($bmi * 0.5) + ($age * 0.1); 
}
$body_fat_percentage = max(5.0, min(50.0, round($body_fat_percentage, 1)));

$muscle_percentage = 100 - $body_fat_percentage - 15; // 仮に15%を骨/その他とする
$muscle_percentage = max(10.0, min(60.0, round($muscle_percentage, 1)));

?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MYボディデータ</title>
    <link rel="stylesheet" href="mybodydata_edit.css">
</head>
<body>
    <div class="header">
        <a href="bodydata.php" class="back-link">&lt;</a>
        <h1>ボディデータ</h1>
    </div>

    <div class="container">
        <h2>MYボディデータ</h2>
        <form id="bodyDataForm" action="mybodydata_result.php" method="POST">
            
            <div class="data-item clickable-input" data-target="weight">
                <div class="icon-label">
                    <span class="icon">⚖️</span>
                    <label>体重</label>
                </div>
                <div class="input-group">
                    <span id="weightDisplay" class="display-value"><?= number_format($weight, 1) ?></span>
                    <span class="unit">kg</span>
                </div>
                <input type="hidden" id="weight" name="weight" value="<?= number_format($weight, 1) ?>">
            </div>

            <div class="data-item clickable-input" data-target="height">
                <div class="icon-label">
                    <span class="icon">🧍</span>
                    <label>身長</label>
                </div>
                <div class="input-group">
                    <span id="heightDisplay" class="display-value"><?= number_format($height, 1) ?></span>
                    <span class="unit">cm</span>
                </div>
                <input type="hidden" id="height" name="height" value="<?= number_format($height, 1) ?>">
            </div>

            <hr>

            <div class="data-item calculated-data">
                <div class="icon-label">
                    <span class="icon">💪</span>
                    <label>筋肉率</label>
                </div>
                <div class="output-group">
                    <span id="muscleRateOutput"><?= number_format($muscle_percentage, 1) ?></span>
                    <span class="unit">%</span>
                </div>
            </div>

            <div class="data-item calculated-data">
                <div class="icon-label">
                    <span class="icon">🐷</span>
                    <label>体脂肪率</label>
                </div>
                <div class="output-group">
                    <span id="bodyFatRateOutput"><?= number_format($body_fat_percentage, 1) ?></span>
                    <span class="unit">%</span>
                </div>
            </div>

            <button type="submit" class="submit-button">データを保存</button>
        </form>
    </div>

    <div id="dataModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-handle"></div>
            <h3 id="modalTitle"></h3>
            
            <div class="picker-container">
                <div class="picker" id="integerPicker"></div>
                <span class="decimal-separator">.</span>
                <div class="picker" id="decimalPicker"></div>
                <span class="unit-label" id="modalUnit"></span>
            </div>

            <button id="modalConfirmButton" class="confirm-button">確認</button>
        </div>
    </div>

    <script>
        // JSで利用するための年齢データ
        const userAge = <?= $age ?>;
    </script>
    <script src="mybodydata_edit.js"></script>
</body>
</html>