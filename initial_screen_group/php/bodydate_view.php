<?php
// ファイル名: bodydate_view.php (htmlフォルダ内を想定)
session_start();
require_once '../../db_connect.php'; // DB接続ファイルへのパスを確認

$user_id = $_SESSION['user_id'] ?? null;
$user_data = [];
$frequency = $_SESSION['training_frequency'] ?? '未設定'; // セッションから頻度を取得
$level = $_SESSION['training_level'] ?? '未設定';      // セッションからレベルを取得

$original_password_length = $_SESSION['original_password_length'] ?? null;

if (!$user_id) {
    exit('エラー: ユーザー情報が見つかりません。');
}

// user_id に基づいて必要な全データを取得
$sql = "SELECT birthday, gender, height, weight, goal, goal_detail, email FROM users WHERE user_id = :user_id";
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user_data) {
        exit('エラー: DBにユーザーデータが見つかりません。');
    }

    // goal (目標ID: 1, 2, 3, 4) を表示用のテキストに変換
    $goal_text_mapping = [
        '1' => '理想のマッスルボディへ', '2' => 'ストレングス (筋力) をつける', 
        '3' => '身体能力を向上させる', '4' => '体力をつける',
    ];
    $display_goal_title = $goal_text_mapping[$user_data['goal']] ?? '目標情報なし';
    
    // gender の表示変換
    $display_gender = ($user_data['gender'] === '男性') ? '男性' : '女性';

    if ($original_password_length !== null) {
        $display_password_dots = str_repeat('●', $original_password_length);
    } else {
        $display_password_dots = 'パスワード情報なし';
    }

    $user_data['weight'] = $user_data['weight'] ?? '';
    $user_data['height'] = $user_data['height'] ?? '';
    $user_data['birthday'] = $user_data['birthday'] ?? '';
    // $user_data['goal_detail'] はHTML側で ?? で対応済み
    $user_data['email'] = $user_data['email'] ?? '';
    
} catch (PDOException $e) {
    error_log("DB Error: " . $e->getMessage()); // エラーログに出力
    exit('エラー: データ取得中に問題が発生しました。' . $e->getMessage()); // デバッグ用にメッセージ表示
}
?>

<!DOCTYPE html>
<html lang="ja">
<head> 
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登録内容確認</title>
    <link rel="stylesheet" href="bodydate_view.css">
</head>
<body>
    <div class="phone-screen">
        <div class="logo-area">
            <img src="../images/Gorifit.ロゴ.png" alt="GoriFit Logo" class="onboarding-logo">
        </div>

        <div class="header-content">
            <a href="training_count.php" class="back-button">&lt;</a>
            <div class="progress-bar-container" style="width: 100%;">
                <div class="progress-fill" style="width: 99%;"></div>
            </div>
        </div>

        <div class="question-area">
            <h1>もう少しです。</h1>
            <p class="sub-header-text">入力した内容に間違いはございませんか？</p>
        </div>

        <div id="registrationSummary" class="goal-form"> 
            
            <label>体重<span class="required">●</span></label>
            <p class="display-value"><?php echo htmlspecialchars($user_data['weight']) . ' kg'; ?></p>
            
            <label>身長<span class="required">●</span></label>
            <p class="display-value"><?php echo htmlspecialchars($user_data['height']) . ' cm'; ?></p>
            
            <label>生年月日<span class="required">●</span></label>
            <p class="display-value"><?php echo htmlspecialchars($user_data['birthday']); ?></p>
            
            <label>性別<span class="required">●</span></label>
            <p class="display-value"><?php echo htmlspecialchars($display_gender); ?></p>

            <label>メールアドレス<span class="required">●</span></label>
            <p class="display-value"><?php echo htmlspecialchars($user_data['email']); ?></p>

            <label>パスワード<span class="required">●</span></label>
            <p class="display-value"><?php echo htmlspecialchars($display_password_dots); ?></p>
            
            <div class="option-container">
                <label class="selection-header">あなたの選んだレベル</label>
                <div class="level-card-wrapper">
                    <div class="level-button-display level-<?php echo htmlspecialchars($level); ?>">
                    <span class="level-badge">Lv.</span>
                    <p class="level-text-display"><?php echo htmlspecialchars($level); ?></p>
                        </div>
                </div>

                <label class="selection-header">あなたの選んだ目標</label>
                <div class="goal-card-wrapper">
                    <div class="goal-summary-card">
                    <h2><?php echo htmlspecialchars($display_goal_title); ?></h2>
                    <p><?php echo htmlspecialchars($user_data['goal_detail'] ?? '具体的な目標の入力なし'); ?></p>
                        </div>
                </div>
                
                <label class="selection-header">あなたの選んだトレーニング頻度</label>
                <p class="display-selection-value">週<?php echo htmlspecialchars($frequency); ?>回</p>
            </div>

            <button type="button" class="next-button" onclick="location.href='../../home_screen_group/php/home.php'">登録完了</button> 
        </div>
    </div>
</body>
</html>