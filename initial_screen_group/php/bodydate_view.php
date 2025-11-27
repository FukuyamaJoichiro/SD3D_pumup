<?php
// ファイル名: bodydate_view.php
session_start();
require_once '../../db_connect.php'; // DB接続ファイルへのパスを確認

$user_id = $_SESSION['user_id'] ?? null;
$user_data = [];
$frequency = $_SESSION['training_frequency'] ?? '未設定'; // セッションから頻度を取得
$level = $_SESSION['training_level'] ?? '未設定'; // セッションからレベルを取得

$original_password_length = $_SESSION['original_password_length'] ?? null;

// レベルIDとテキストのマッピングを定義
$level_mapping = [
    '1' => ['badge_text' => 'Lv.1', 'text' => '初めてです。'],
    '2' => ['badge_text' => 'Lv.2', 'text' => 'トレーニングの経験はあるが、<br>まだ初心者だ。'],
    '3' => ['badge_text' => 'Lv.3', 'text' => '少しずつ慣れてきている。'],
    '4' => ['badge_text' => 'Lv.4', 'text' => 'どんなトレーニングでも自信がある。'],
];

// ★★★ エラー対策とレベル説明文の取得（修正箇所） ★★★
$selected_level_info = $level_mapping[$level] ?? null;

$display_badge_text = $selected_level_info['badge_text'] ?? 'Lv. ?';

if ($selected_level_info) {
    // レベル情報が取得できた場合、説明文をセット
    $display_level_description = $selected_level_info['text']; 
} else {
    // レベル情報がない、または $level が未設定の場合、エラー回避のため初期値を設定
    $display_level_description = '情報なし'; 
    $level = '1'; // CSSのために$levelもデフォルト値に設定（任意）
}
// ★★★ --------------------------------------- ★★★

// 目標IDとアイコンクラスのマッピングを定義
$goal_data_mapping = [
    '1' => ['title' => '理想のマッスルボディへ', 'icon_class' => 'icon-body', 'subtitle' => '気合で頑張るしかない。'],
    '2' => ['title' => 'ストレングス (筋力) をつける', 'icon_class' => 'icon-strength', 'subtitle' => '誰よりも強い力を。'], 
    '3' => ['title' => '身体能力を向上させる', 'icon_class' => 'icon-ability', 'subtitle' => '様々な運動をうまくこなせるよう。'], 
    '4' => ['title' => '体力をつける', 'icon_class' => 'icon-stamina', 'subtitle' => '疲れないように体力を。'],
];

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
    
    // 目標IDに対応するデータを取得
    $goal_id = $user_data['goal'] ?? '1'; // DBから取得したゴールID
    $goal_info = $goal_data_mapping[$goal_id] ?? $goal_data_mapping['1'];
    
    $display_goal_title = $goal_info['title'];
    $display_goal_icon_class = $goal_info['icon_class'];
    $display_goal_subtitle = $goal_info['subtitle']; // サブタイトルも取得

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
    $user_data['email'] = $user_data['email'] ?? '';
    
} catch (PDOException $e) {
    error_log("DB Error: " . $e->getMessage()); 
    exit('エラー: データ取得中に問題が発生しました。' . $e->getMessage()); 
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
        
                        <span class="level-badge-combined">
                            <?php echo htmlspecialchars($display_badge_text); ?>
                        </span>
        
                        <p class="level-description-text"><?php echo $display_level_description; ?></p>
                    </div>
                </div>

                <label class="selection-header">あなたの選んだ目標</label>
                <div class="goal-card-wrapper">
                                <div class="goal-button-display">
                 <span class="goal-icon <?php echo htmlspecialchars($display_goal_icon_class); ?>"></span> 
                 <div class="text-content">
                 <h2><?php echo htmlspecialchars($display_goal_title); ?></h2>
                <p><?php echo htmlspecialchars($display_goal_subtitle); ?></p>
                        </div>
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