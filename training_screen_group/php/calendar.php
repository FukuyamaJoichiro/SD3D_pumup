<?php
// PHPエラー表示設定 (開発時のみ有効にすることを推奨)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ==========================================================
// 1. 必要なファイルの読み込みと認証
// ==========================================================
// セッションがまだ開始されていない場合に開始
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// 🚨 auth.php と db_connect.php のパスが正しいことを前提
require_once("../../auth.php"); 
require_once("../../db_connect.php"); 

// ログイン必須チェックとユーザーIDの取得
// require_login()は auth.php で定義されていることを前提とする
require_login(); 
$user_id = $_SESSION['user_id']; 

// ----------------------------------------------------------
// 2. 日付関連の設定 (既存コードを流用)
// ----------------------------------------------------------
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$today = date('Y-m-d');

// 月初と月末
$start_date = "$year-$month-01";
$end_date = date("Y-m-t", strtotime($start_date));

// 祝日データの定義（※既存コードを流用）
$holidays = [
    "2025-11-03", // 文化の日
    "2025-11-23", // 勤労感謝の日
];

// ==========================================================
// 3. DBからアクティビティデータ（トレーニング/休息）を取得
// ==========================================================
$sql = "
    SELECT 
        activity_date AS date, 
        session_type,
        part_id 
    FROM calendar_activity
    WHERE user_id = :user_id
      AND activity_date BETWEEN :start AND :end
";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);
$stmt->bindValue(":start", $start_date);
$stmt->bindValue(":end", $end_date);
$stmt->execute();
$activity_data = $stmt->fetchAll(PDO::FETCH_ASSOC); 

// 日付をキーに変換し、アクティビティ情報を格納
$training_days = []; 
foreach ($activity_data as $row) {
    $training_days[$row['date']] = [
        'type' => $row['session_type'], 
        'part_id' => $row['part_id']
    ];
}

// ----------------------------------------------------------
// 4. 累計日数・継続日数の計算 (calendar_activity対応に修正)
// ----------------------------------------------------------
// 累計日数：WORKOUTの日のみをカウント
$total_sql = "SELECT COUNT(DISTINCT activity_date) AS total_days FROM calendar_activity WHERE user_id = :uid AND session_type = 'WORKOUT'";
$total_stmt = $pdo->prepare($total_sql);
$total_stmt->bindValue(":uid", $user_id);
$total_stmt->execute();
$total = $total_stmt->fetch(PDO::FETCH_ASSOC)['total_days'];

// 月間日数：training_daysからWORKOUTの日のみをカウント
$monthly_count = 0;
foreach($training_days as $data) {
    if ($data['type'] === 'WORKOUT') {
        $monthly_count++;
    }
}

// 継続日数：🚨 継続日数ロジックは'REST'を考慮すると複雑になるため、既存のコードは削除し、
// 一旦 0 のまま維持します。正しいロジックは別途実装が必要です。
$streak = 0; // 0 のまま維持
// ... (既存の継続日数計算コードはここでは使用しない) ...

?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GoriFit カレンダー</title>
<link rel="stylesheet" href="calendar_style.css">
</head>
<body>
    <div class="app-container">
        <div class="app-header">
        <p><?= $year ?>年<?= $month ?>月</p>
        <h2>Total : <?= $total ?>days!</h2>
        <p>Monthly Archive. <strong><?= $monthly_count ?>day</strong></p>
    </div>

    <div class="calendar-warpper">
        <table class="calender">
            <thead>
        <tr>
            <th>日</th><th>月</th><th>火</th><th>水</th><th>木</th><th>金</th><th>土</th>
        </tr>
            </thead>
        <tbody>
        <?php
        $first_day_week = date('w', strtotime($start_date));
        $days_in_month = date('t', strtotime($start_date));

        $day = 1;
        echo "<tr>";
        for ($i=0; $i<$first_day_week; $i++) echo "<td></td>";

        while ($day <= $days_in_month) {
            $current_date = sprintf("%04d-%02d-%02d", $year, $month, $day);
            $weekday = date('w', strtotime($current_date)); // 0(日)から6(土)
            $is_today = ($current_date == $today);
            $is_holiday = in_array($current_date, $holidays); // 祝日判定

            // CSSクラスの構築
            $class_list = [];
            if ($is_today) $class_list[] = "today";
            
            if ($weekday == 0 || $is_holiday) {
                $class_list[] = "holiday";
            } 
            elseif ($weekday == 6) {
                $class_list[] = "saturday";
            }
            
            // === アクティビティデータのチェックとクラスの追加 (修正箇所) ===
            if (isset($training_days[$current_date])) {
                $activity = $training_days[$current_date];
                if ($activity['type'] === 'WORKOUT') {
                    $class_list[] = "trained"; 
                } elseif ($activity['type'] === 'REST') {
                    $class_list[] = "rest-day"; 
                }
            }
            // === 修正箇所ここまで ===

            echo "<td class='" . implode(' ', $class_list) . "'>";

            // 【JSによるクリック処理】日付データをdata属性に格納し、JS関数を呼び出す
            echo "<div class='date-clickable-wrapper' data-date='$current_date' onclick='handleDateClick(this)'>";
            
            echo "<div class='day-num'>$day</div>";

            // === アイコン表示ロジック (修正箇所) ===
            if (isset($training_days[$current_date])) {
                $activity = $training_days[$current_date];
                
                // 1. 休息日（REST）の場合
                if ($activity['type'] === 'REST') {
                    echo "<div class='rest-content'>";
                    echo "  <div class='rest-bottom-row'>";
                    echo "<div class='rest-icon'>😴</div>"; 
                    echo "    <div class='rest-button'>おやすみ</div>";
                    echo "  </div>";
                    echo "</div>";
                    
                } 
                // 2. トレーニング日（WORKOUT）の場合
                elseif ($activity['type'] === 'WORKOUT') {
                    
                    $part_id = $activity['part_id'];
                    
                    // part_idに応じたアイコンを切り替え
                    $icon = match ((int)$part_id) {
                        1 => '💪', // 胸
                        2 => '🦁', // 背中
                        3 => '🔺', // 肩
                        4 => '🦵', // 脚
                        5 => '🔥', // 腹
                        6 => '🛡️', // 腕 (仮)
                        default => '🏋️' 
                    };
                    echo "<div class='activity-icon part-icon' data-part='$part_id'>$icon</div>";
                }
            } 
            // === アイコン表示ロジックここまで ===
            
            echo "</div>"; // date-clickable-wrapperを閉じる
            
            echo "</td>";

            if ($weekday == 6) echo "</tr><tr>";
            $day++;
        }

        $last_weekday = date('w', strtotime("$year-$month-$days_in_month"));
        for ($i=$last_weekday; $i<6; $i++) echo "<td></td>";
        echo "</tr>";
        ?>
        </tbody>
    </table>

    <div class="calendar-footer">
        <p><?= date('n月j日 D', strtotime($today)) ?>（<?= $streak ?>日継続中！）</p>
        <button onclick="location.href='training_record.php'">今日のトレーニングプランを立てる</button>
    </div>

    <nav class="app-nav">
        <a href="../../home_screen_group/php/home.php" class="nav-item">
            <span class="nav-item-icon">🏠</span> ホーム
        </a>
        <a href="calendar.php" class="nav-item activ">
            <span class="nav-item-icon">💪</span> カレンダー
        </a>
        <a href="../../home_screen_group/php/mypage.php" class="nav-item">
            <span class="nav-item-icon">👤</span> マイページ
        </a>
    </nav>
</div>

<script src="calendar.js"></script>

</body>
</html>