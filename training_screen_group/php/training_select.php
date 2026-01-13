<?php
require_once('../../db_connect.php');
session_start();

$user_id = $_SESSION['user_id'] ?? 1;

<<<<<<< Updated upstream
if (!isset($_SESSION['workout_trainings'])) {
    $_SESSION['workout_trainings'] = [];
=======
try {
    // 1) 日付の取得（URLから取得し、なければ今日にする）
    $selected_date = isset($_GET['date']) ? $_GET['date'] : date("Y-m-d");
    $today_date = $selected_date; 
    $current_session_id = null;

    // 2) セッションID取得/作成
    $stmt_session = $pdo->prepare("SELECT session_id FROM workout_sessions WHERE user_id = ? AND date = ?");
    $stmt_session->execute([$user_id, $today_date]);
    $session_data = $stmt_session->fetch(PDO::FETCH_ASSOC);

    if ($session_data) {
        $current_session_id = (int)$session_data['session_id'];
    } else {
        $stmt_insert_session = $pdo->prepare("INSERT INTO workout_sessions (user_id, date) VALUES (?, ?)");
        $stmt_insert_session->execute([$user_id, $today_date]);
        $current_session_id = (int)$pdo->lastInsertId();
    }

    // 3) POSTでトレーニング追加が来た場合の登録処理
    if ($current_session_id && isset($_POST['training']) && is_array($_POST['training'])) {
        $new_training_ids = $_POST['training'];

        $stmt_max_order = $pdo->prepare("SELECT IFNULL(MAX(order_on), 0) FROM workout_sets WHERE session_id = ?");
        $stmt_max_order->execute([$current_session_id]);
        $max_order = (int)$stmt_max_order->fetchColumn();
        $current_order = $max_order;

        $pdo->beginTransaction();
        try {
            $stmt_insert = $pdo->prepare("
                INSERT INTO workout_sets
                (session_id, training_id, user_id, order_on, weight, reps, duration, set_memo, superset_id)
                VALUES (?, ?, ?, ?, 0, 0, 0, NULL, NULL)
            ");

            foreach ($new_training_ids as $training_id) {
                $training_id = (int)$training_id;
                $stmt_check = $pdo->prepare("SELECT 1 FROM workout_sets WHERE session_id = ? AND training_id = ? LIMIT 1");
                $stmt_check->execute([$current_session_id, $training_id]);

                if ($stmt_check->rowCount() == 0) {
                    $current_order++;
                    $stmt_insert->execute([$current_session_id, $training_id, $user_id, $current_order]);
                }
            }
            $pdo->commit();
        } catch (\PDOException $e) {
            $pdo->rollBack();
            exit("データ登録エラー: " . $e->getMessage());
        }

        header("Location: " . $_SERVER['PHP_SELF'] . "?date=" . $today_date);
        exit;
    }

    // 4) 登録されているデータの取得
    $selected_training_ids = [];
    if ($current_session_id) {
        $stmt_select_menu = $pdo->prepare("
            SELECT training_id
            FROM workout_sets
            WHERE session_id = ?
            GROUP BY training_id
            ORDER BY MIN(order_on) ASC
        ");
        $stmt_select_menu->execute([$current_session_id]);
        $selected_training_ids = $stmt_select_menu->fetchAll(PDO::FETCH_COLUMN);
    }
    $_SESSION['workout_trainings'] = array_map('intval', $selected_training_ids);

} catch(PDOException $e) {
    exit("全体処理エラー: " . $e->getMessage());
>>>>>>> Stashed changes
}

if (isset($_POST['training']) && is_array($_POST['training'])) {
    foreach ($_POST['training'] as $training_id) {
        $training_id = (int)$training_id;
        if (!in_array($training_id, $_SESSION['workout_trainings'], true)) {
            $_SESSION['workout_trainings'][] = $training_id;
        }
    }
}

$selected_training_ids = $_SESSION['workout_trainings'];

// 日付（必要なら GET で受け取れるようにしておく）
$selected_date = $_GET['date'] ?? date('Y-m-d');

// ※この旧版には workout_sessions が無いので session_id は null にする
// （あなたの新しい版では CURRENT_SESSION_ID をちゃんと取れてるのでそちらを使ってOK）
$current_session_id = null;

// 現在の日付情報（表示用）
$current_date = new DateTime($selected_date);
$current_month = $current_date->format('n');
$current_day = $current_date->format('j');
$current_year = $current_date->format('Y');

$week_dates = [];
$day_of_week = (int)$current_date->format('w');

for ($i = 0; $i < 7; $i++) {
    $date = clone $current_date;
    $date->modify('-' . $day_of_week . ' days');
    $date->modify('+' . $i . ' days');

    $week_dates[] = [
        'day_label' => ['日', '月', '火', '水', '木', '金', '土'][$i],
        'day_num' => $date->format('j'),
        'is_today' => ($date->format('Y-m-d') === $selected_date)
    ];
}

<<<<<<< Updated upstream
=======
// トレーニング情報の取得
>>>>>>> Stashed changes
$trainings = [];
if (!empty($selected_training_ids)) {
    $placeholders = implode(',', array_fill(0, count($selected_training_ids), '?'));
    $stmt = $pdo->prepare("
        SELECT
            t.training_id,
            t.training_name,
            GROUP_CONCAT(DISTINCT tt.type_id) as type_ids
        FROM trainings t
        LEFT JOIN training_types tt ON t.training_id = tt.training_id
        WHERE t.training_id IN ($placeholders)
        GROUP BY t.training_id
        ORDER BY FIELD(t.training_id, $placeholders)
    ");
    $stmt->execute(array_merge($selected_training_ids, $selected_training_ids));
    $trainings = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>トレーニング記録</title>
  <link rel="stylesheet" href="training_select.css">
</head>
<body>
<div class="container">
  <div class="header">
    <button class="back-btn" onclick="history.back()">＜</button>
    <div class="date-selector">
      <span class="month"><?php echo $current_month; ?>月</span>
    </div>
  </div>

  <div class="week-calendar">
    <?php foreach ($week_dates as $date): ?>
      <div class="day-item<?php echo $date['is_today'] ? ' active' : ''; ?>">
        <span class="day-label"><?php echo $date['day_label']; ?></span>
        <span class="day-num"><?php echo $date['day_num']; ?></span>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="tabs">
    <button class="tab active">トレーニング記録</button>
    <button class="tab">ボディデータ</button>
  </div>

  <div class="interval-timer">
    <span>⏱ Interval Timer ⏱</span>
  </div>

  <div class="training-list">
    <?php if (empty($trainings)): ?>
      <div class="empty-message">
        <p>トレーニングが選択されていません</p>
        <button onclick="location.href='training_list.php?date=<?= htmlspecialchars($selected_date) ?>'" class="select-btn">
          トレーニングを選択
        </button>
      </div>
    <?php else: ?>
      <?php foreach ($trainings as $index => $training): ?>
        <div class="training-card"
             data-training-id="<?php echo (int)$training['training_id']; ?>"
             data-type-ids="<?php echo htmlspecialchars($training['type_ids'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
          <div class="training-header">
            <span class="training-number"><?php echo $index + 1; ?>種</span>
            <span class="training-name"><?php echo htmlspecialchars($training['training_name'], ENT_QUOTES, 'UTF-8'); ?></span>
            <button class="info-btn" type="button">ⓘ</button>
            <button class="menu-btn" type="button">⋮</button>
          </div>

          <div class="sets-container"></div>

          <div class="set-actions">
            <button class="delete-set-btn" type="button">－セット削除</button>
            <button class="add-set-btn" type="button">＋セット追加</button>
          </div>
        </div>
      <?php endforeach; ?>

      <button class="add-training-btn" onclick="location.href='training_list.php?date=<?= htmlspecialchars($selected_date) ?>'">
        トレーニング追加
      </button>

      <form method="post" action="clear_session.php" style="margin-top: 8px;">
        <button type="submit" class="clear-session-btn">全てリセット</button>
      </form>

      <div class="memo-section">
        <label>メモ</label>
        <textarea class="memo-input" placeholder="メモを入力..."></textarea>
      </div>
    <?php endif; ?>
  </div>

  <?php if (!empty($trainings)): ?>
    <div class="footer-actions">
      <div class="timer-section">
        <div class="timer-item">
          <span class="timer-label">休憩タイマー</span>
          <span class="timer-value" id="rest-timer">0:00</span>
        </div>
        <div class="timer-item">
          <span class="timer-label">通算時間</span>
          <span class="timer-value" id="total-timer">00:00</span>
        </div>
      </div>
      <button class="start-btn" type="button">トレーニングスタート</button>
    </div>
  <?php endif; ?>
</div>

<<<<<<< Updated upstream
<!-- ===== 3点メニュー（既存）+ 解除を追加 ===== -->
<div class="training-menu-overlay" id="training-menu-overlay">
  <div class="training-menu-content">
    <div class="menu-header">
      <span class="menu-training-name" id="menu-training-name">種</span>
      <button class="menu-close-btn" id="menu-close-btn" type="button">✕</button>
    </div>

    <div class="menu-items">
      <button class="menu-item" id="menu-exchange" type="button">
        <span class="menu-item-icon">⇄</span>
        <span class="menu-item-text">トレーニング交換</span>
        <span class="menu-item-arrow">▷</span>
      </button>

      <button class="menu-item" id="menu-superset" type="button">
        <span class="menu-item-icon">🔗</span>
        <span class="menu-item-text">スーパーセット</span>
        <span class="menu-item-arrow">▷</span>
      </button>

      <!-- ★追加：解除 -->
      <button class="menu-item" id="menu-unsuperset" type="button">
        <span class="menu-item-icon">🔓</span>
        <span class="menu-item-text">スーパーセット解除</span>
        <span class="menu-item-arrow">▷</span>
      </button>

      <button class="menu-item delete" id="menu-delete" type="button">
        <span class="menu-item-text">削除</span>
      </button>
    </div>
  </div>
</div>

<!-- ===== スーパーセット選択（チェックUI） ===== -->
<?php if (!empty($trainings)): ?>
<div class="training-menu-overlay" id="superset-overlay">
  <div class="training-menu-content">
    <div class="menu-header">
      <button class="menu-close-btn" id="superset-back" type="button">＜</button>
      <span class="menu-training-name">スーパーセット</span>
      <button class="menu-close-btn" id="superset-close" type="button">✕</button>
    </div>

    <div class="menu-items" style="padding-bottom: 84px;">
      <?php foreach ($trainings as $t): ?>
        <label class="menu-item" style="gap:12px;">
          <input type="checkbox" class="superset-cb" value="<?= (int)$t['training_id'] ?>" style="width:18px;height:18px;accent-color:#ff6b6b;">
          <span class="menu-item-text"><?= htmlspecialchars($t['training_name'], ENT_QUOTES, 'UTF-8') ?></span>
          <span class="superset-tag" style="margin-left:auto;font-weight:800;color:#333;"></span>
        </label>
      <?php endforeach; ?>
    </div>

    <div style="position:absolute;left:0;right:0;bottom:0;padding:12px 16px;background:#fff;border-top:1px solid #eee;">
      <button type="button" class="start-btn" id="superset-confirm">トレーニングを組み合わせる</button>
    </div>
  </div>
</div>
<?php endif; ?>
=======
<div class="training-menu-overlay" id="training-menu-overlay">
    <div class="training-menu-content">
        <div class="menu-header">
            <span class="menu-training-name" id="menu-training-name">種目名</span>
            <button class="menu-close-btn" id="menu-close-btn">✕</button>
        </div>
        <div class="menu-items">
            <button class="menu-item" id="menu-exchange">
                <span class="menu-item-icon">⇄</span>
                <span class="menu-item-text">トレーニング交換</span>
                <span class="menu-item-arrow">▷</span>
            </button>
            <button class="menu-item" id="menu-superset">
                <span class="menu-item-icon">🔗</span>
                <span class="menu-item-text">スーパーセット</span>
                <span class="menu-item-arrow">▷</span>
            </button>
            <button class="menu-item" id="menu-unsuperset">
                <span class="menu-item-icon">⛓️</span>
                <span class="menu-item-text">スーパーセット解除</span>
                <span class="menu-item-arrow">▷</span>
            </button>
            <button class="menu-item delete" id="menu-delete">
                <span class="menu-item-text">削除</span>
            </button>
        </div>
    </div>
</div>

<div id="detail-modal-overlay" class="modal-overlay" style="z-index: 2000; display: none;">
    <div id="detail-modal-content" class="modal-content detail-modal-box"></div>
</div>
>>>>>>> Stashed changes

<script>
  const CURRENT_SESSION_ID = <?php echo json_encode($current_session_id); ?>;
  const SELECTED_DATE = <?php echo json_encode($selected_date); ?>;
</script>
<script src="training_select.js"></script>
</body>
</html>
