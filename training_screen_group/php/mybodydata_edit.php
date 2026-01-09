<?php
// パスを修正: 認証とDB接続ファイルの読み込み
require_once '../../auth.php';
require_login(); 

global $pdo; 

// 現在ログイン中のユーザーIDを取得
$logged_in_user_id = $_SESSION['user_id']; 

try {
    // 必要なデータ (身長、体重、生年月日、性別) をDBから取得
    // ★genderを追加しました
    $stmt = $pdo->prepare("SELECT height, weight, birthday, gender FROM users WHERE user_id = :id");
    $stmt->bindParam(':id', $logged_in_user_id, PDO::PARAM_INT);
    $stmt->execute();
    $db_data = $stmt->fetch();

    if (!$db_data) {
        logout();
        header("Location: /pumpup/SD3D_pumup/initial_screen_group/php/login.php");
        exit;
    }

    // データの整形
    $weight = (float)$db_data['weight'];
    $height = (float)$db_data['height'];
    
    // --- 性別文字列を計算用の数値に変換 ---
    // 男性=1, 女性=0 として計算式に代入する
    $gender_str = $db_data['gender'] ?? '男性';
    $gender_value = ($gender_str === '男性') ? 1 : 0;

    // 生年月日 ('date'型) から年齢を計算
    $birthday = new DateTime($db_data['birthday']);
    $today = new DateTime('today');
    $age = $birthday->diff($today)->y;

} catch (Exception $e) {
    error_log("データ取得エラー: " . $e->getMessage());
    exit("システムエラーが発生しました。時間を置いて再度お試しください。");
}

// --- 体組成の再計算ロジック (Deurenbergの推定式) ---

$height_m = $height / 100;
$bmi = ($height_m > 0) ? $weight / ($height_m * $height_m) : 0; 

$body_fat_percentage = 0;
$muscle_percentage = 0;

if ($bmi > 0) {
    /**
     * 1. 体脂肪率の推定（Deurenbergの式）
     */
    $calc_fat = (1.20 * $bmi) + (0.23 * $age) - (10.8 * $gender_value) - 5.4;
    $body_fat_percentage = max(5.0, min(50.0, round($calc_fat, 1)));

    /**
     * 2. 筋肉率の推定
     * 100%から体脂肪率を引いた「除脂肪率」に、筋肉の比率係数を掛けます。
     * 一般的に除脂肪量の約60%〜90%が骨格筋などの筋肉組織と言われています。
     * ここでは、より変化が出やすく、かつ自然な数値になる「0.8」を係数として採用します。
     */
    // 除脂肪率 = 100 - 体脂肪率
    $lean_body_mass_percent = 100 - $body_fat_percentage;
    
    // 筋肉率 = 除脂肪率 × 筋肉係数(0.8)
    // これにより、体脂肪が減るほど筋肉率がスムーズに上昇し、かつ上限に張り付きにくくなります。
    $calc_muscle = $lean_body_mass_percent * 0.5;

    // 異常値の制限 (範囲を少し広めに設定)
    $muscle_percentage = max(10.0, min(70.0, round($calc_muscle, 1)));
}

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
    <header class="header">
        <div class="back-btn" onclick="location.href='bodydata.php'">&#x2039;</div>
        <h1>ボディデータ</h1>
    </header>

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