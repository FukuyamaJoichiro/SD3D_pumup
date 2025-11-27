<?php
// データ処理部分をファイルの最上部に追加します
session_start();

// データベース接続ファイルの読み込み (★★★ パスを確認してください ★★★)
require_once '../../db_connect.php'; 

$user_id = $_SESSION['user_id'] ?? null;
$user_goal_id = null; // ユーザーの目標IDを格納する変数

// ---------------------------------------------------------------------
// 🎯 ステップ1 & 2: 目標IDの取得とマッピングの定義
// ---------------------------------------------------------------------

// 目標IDと表示内容のマッピング（対応表）を定義
$goals_map = [
    // goal ID => [タイトル, サブテキスト, アイコンCSSクラス]
    '1' => [
        'title' => '理想のマッスルボディへ', 
        'subtitle' => '気合で頑張るしかない。', 
        'icon' => 'icon-body'
    ],
    '2' => [
        'title' => 'ストレングス (筋力) をつける', 
        'subtitle' => '誰よりも強い力を。', 
        'icon' => 'icon-strength'
    ],
    '3' => [
        'title' => '身体能力を向上させる', 
        'subtitle' => '様々な運動をうまくこなせるよう。', 
        'icon' => 'icon-ability'
    ],
    '4' => [
        'title' => '体力をつける', 
        'subtitle' => '疲れないように体力を。', 
        'icon' => 'icon-stamina'
    ]
];

// ユーザーの goal ID をDBから取得し、表示する目標データを決定
if ($user_id) {
    $sql_fetch_goal = "SELECT goal FROM users WHERE user_id = :user_id";
    try {
        $stmt_fetch = $pdo->prepare($sql_fetch_goal);
        $stmt_fetch->execute([':user_id' => $user_id]);
        $result = $stmt_fetch->fetch(PDO::FETCH_ASSOC);
        
        // 取得した目標IDを変数に格納
        $user_goal_id = $result['goal'] ?? null;
        
    } catch (PDOException $e) {
        // エラーが発生した場合、ログに出力して処理を続行（表示はデフォルト目標）
        error_log("目標ID取得エラー: " . $e->getMessage()); 
    }
}

// ユーザーが選んだ目標のデータを取得。IDがない場合は '1' をデフォルトとして表示
$selected_goal_data = $goals_map[$user_goal_id] ?? $goals_map['1'];
$user_goal_id_display = $user_goal_id ?? '1'; // HTMLで使用するID

// ---------------------------------------------------------------------
// 💾 POST処理: 具体的な目標の保存 (ここは変更なし)
// ---------------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
$goal_detail = $_POST['customGoal'] ?? null; 

if (!$user_id) {
exit('エラー: ユーザー情報が見つかりません。最初からやり直してください。');
}

// 20文字以内のバリデーション (サーバーサイドチェック)
if (mb_strlen($goal_detail, 'UTF-8') > 20) {
exit('エラー: 具体的な目標は20文字以内で入力してください。');
}

// DBの goal_detail カラムを新しい値で上書き更新するSQL
$sql = "UPDATE users SET goal_detail = :goal_detail WHERE user_id = :user_id";

try {
$stmt = $pdo->prepare($sql);
// プレースホルダに値をバインドし実行
$stmt->execute([
':goal_detail' => $goal_detail, 
':user_id' => $user_id
]); 

// DB更新後、次の画面へリダイレクト
header('Location: training_count.php'); 
exit();

} catch (PDOException $e) {
error_log("DB Error: " . $e->getMessage()); 
exit('エラー: 目標の詳細を登録中に問題が発生しました。');
}
}
// POSTでない場合（直接アクセスされた場合）は、以下のHTMLが表示されます。
?>
<!DOCTYPE html>
<html lang="ja">
<head> 
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>目標詳細設定</title>
<link rel="stylesheet" href="goal_detail.css">
</head>
<body>
<div class="phone-screen">
<div class="logo-area">
<img src="../images/Gorifit.ロゴ.png" alt="GoriFit Logo" class="onboarding-logo">
</div>

<div class="header-content">
<a href="goal_setting.php" class="back-button">&lt;</a> 
<div class="progress-bar-container" style="width: 100%;">
    <div class="progress-fill" style="width: 60%;"></div>
 </div>
</div>

<div class="question-area">
 <h1>具体的に達成したい目標も<br>一緒に入力してください</h1>
</div>

<form id="goalDetailForm" class="goal-form" action="" method="post">

 <div class="option-container goal-options">
<button type="button" 
 class="goal-button goal-<?php echo htmlspecialchars($user_goal_id_display); ?> selected" 
data-goal="<?php echo htmlspecialchars($user_goal_id_display); ?>">
<span class="goal-icon <?php echo htmlspecialchars($selected_goal_data['icon']); ?>"></span> 
<div class="text-content">
 <h2><?php echo htmlspecialchars($selected_goal_data['title']); ?></h2>
 <p><?php echo htmlspecialchars($selected_goal_data['subtitle']); ?></p>
</div>
 </button>
<label for="custom-goal" class="custom-goal-label">私だけの目標 (選択)</label>
<div class="input-container">
<textarea 
        id="custom-goal" 
        name="customGoal" 
        class="custom-goal-input" 
        placeholder="具体的に達成したい目標を20文字以内で入力してください"
        maxlength="20"></textarea>
<p class="char-counter"><span id="char-count">0</span>/20</p>
</div>

</div>

<button type="submit" class="next-button">次へ</button>

</form>
</div>
<script src="goal_detail.js"></script>
</body>
</html>