<?php
// ================================
// 初期処理
// ================================
session_start();
require_once '../../db_connect.php';

// GET: first_training_id
$first_training_id = filter_input(INPUT_GET, 'first_training_id', FILTER_VALIDATE_INT);
if (!$first_training_id) {
    exit('不正なアクセスです');
}

// 選択済み（training_select.php で DB と同期済み前提）
$selected_trainings = $_SESSION['workout_trainings'] ?? [];
$selected_count = is_array($selected_trainings) ? count($selected_trainings) : 0;

// 1種目目
$stmt = $pdo->prepare("SELECT training_id, training_name FROM trainings WHERE training_id = ?");
$stmt->execute([$first_training_id]);
$first_training = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$first_training) {
    exit('トレーニングが見つかりません');
}

// 2種目目候補
$second_candidates = [];
if ($selected_count >= 2) {
    $second_candidates = array_values(array_diff($selected_trainings, [$first_training_id]));
}

// 2種目目候補データ
$second_trainings = [];
if (!empty($second_candidates)) {
    $placeholders = implode(',', array_fill(0, count($second_candidates), '?'));
    $sql = "
        SELECT training_id, training_name
        FROM trainings
        WHERE training_id IN ($placeholders)
        ORDER BY training_id
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($second_candidates);
    $second_trainings = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8" />
<title>スーパーセット</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
<style>
/* =========================
   training_select と同じ見え方
   外側(PC余白)=グレー / スマホ枠=白
========================= */
:root{
  --app-max: 390px;          /* スマホ枠の最大幅 */
  --outer-bg: #eaeaea;       /* 外側グレー */
  --surface: #ffffff;        /* 枠の白 */
  --header: #ffffff;
  --row: #f1f1f1;
  --border: #e0e0e0;
  --accent: #ff6e6e;
  --accent-soft: #ffb4b4;
  --shadow: 0 10px 30px rgba(0,0,0,.12);
  --radius: 18px;
}

/* 外側 */
html, body { height: 100%; }

body{
  margin:0;
  font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
  background: var(--outer-bg);     /* ←外側グレー */
  display:flex;
  justify-content:center;
}

/* 中央のスマホ枠 */
.page{
  width: 100%;
  min-height: 100vh;
  display:flex;
  justify-content:center;
  padding: 14px 10px;
  box-sizing: border-box;
}

.app{
  width: 100%;
  max-width: var(--app-max);
  background: var(--surface);      /* ←スマホ枠は白 */
}

/* モーダル本体 */
.modal{
  width:100%;
  background: var(--surface);      /* ←白 */
  border-radius: var(--radius);
  overflow:hidden;
  box-shadow: var(--shadow);
  position: relative;
}

/* =========================
   ヘッダー
========================= */
.modal-header{
  background: var(--header);
  display:flex;
  align-items:center;
  justify-content:space-between;
  padding:14px 14px;
  position: sticky;
  top: 0;
  z-index: 10;
  border-bottom: 1px solid var(--border);
}

.icon-btn{
  width:34px;
  height:34px;
  border:none;
  background:transparent;
  font-size:22px;
  cursor:pointer;
  line-height:34px;
  text-align:center;
  user-select:none;
}

.modal-title{
  font-weight:800;
  font-size:16px;
  color:#111;
}

/* =========================
   リスト
========================= */
.list{
  padding: 10px 0 calc(96px + env(safe-area-inset-bottom));
  background: var(--surface);
}

.item{
  background: var(--row);
  margin: 8px 10px;
  border-radius: 10px;
  display:flex;
  align-items:center;
  padding: 10px 10px;
  gap: 10px;
}

.item input[type="checkbox"]{
  width:18px;
  height:18px;
  accent-color: var(--accent);
}

.thumb{
  width:38px;
  height:28px;
  border-radius:4px;
  background:#ddd;
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:10px;
  color:#666;
}

.name{
  flex:1;
  font-size:14px;
  font-weight:700;
  color:#111;
  overflow:hidden;
  white-space:nowrap;
  text-overflow:ellipsis;
}

.ab{
  width:18px;
  text-align:right;
  font-weight:900;
  color:#666;
  font-size:13px;
}

/* =========================
   案内
========================= */
.notice{
  margin: 8px 10px;
  background:#f8f8f8;
  border: 1px solid #f0f0f0;
  border-radius:12px;
  padding:12px;
  font-size:13px;
  color:#444;
}
.notice strong{ color:#111; }
.notice .small{
  font-size:12px;
  color:#666;
  margin-top:6px;
  line-height:1.4;
}

/* =========================
   下部固定ボタン
========================= */
.footer{
  position: fixed;
  left: 50%;
  transform: translateX(-50%);
  bottom: 0;
  width: 100%;
  max-width: var(--app-max);
  background: rgba(255,255,255,.96);
  backdrop-filter: blur(6px);
  padding: 12px 12px calc(12px + env(safe-area-inset-bottom));
  box-sizing: border-box;
  border-top: 1px solid var(--border);
  z-index: 20;
}

.combine-btn{
  width:100%;
  border:none;
  border-radius:14px;
  padding:14px 12px;
  font-size:15px;
  font-weight:800;
  cursor:pointer;
  background: var(--accent);
  color:#fff;
}

.combine-btn:disabled{
  background: var(--accent-soft);
  cursor:not-allowed;
}

/* =========================
   スマホ実機は全面白にする（外側グレー不要）
========================= */
@media (max-width: 480px){
  body{
    background: #ffffff;           /* ←スマホは外側も白 */
  }
  .page{
    padding: 0;
  }
  .app{
    max-width: 100%;
  }
  .modal{
    border-radius: 0;
    box-shadow: none;
    min-height: 100vh;
  }
  .footer{
    max-width: 100%;
  }
}
</style>
</head>

<body>
<div class="page">
  <div class="app">
    <div class="modal">
      <div class="modal-header">
        <button class="icon-btn" onclick="history.back()">＜</button>
        <div class="modal-title">スーパーセット</div>
        <button class="icon-btn" onclick="history.back()">×</button>
      </div>

      <div class="list" id="supersetList">
        <!-- A：1種目目（固定） -->
        <label class="item">
          <input type="checkbox" checked disabled>
          <div class="thumb">img</div>
          <div class="name"><?= htmlspecialchars($first_training['training_name']) ?></div>
          <div class="ab">A</div>
        </label>

        <?php if ($selected_count < 2): ?>
          <div class="notice">
            <strong>スーパーセットを組むには、もう1種目追加してください</strong>
            <div class="small">
              追加済みトレーニングが2種目以上になると、ここに候補（B）が表示されます。
            </div>
          </div>

        <?php elseif (empty($second_trainings)): ?>
          <div class="notice">
            <strong>スーパーセット候補がありません</strong>
            <div class="small">
              1種目目以外の追加済みトレーニングがありません。
            </div>
          </div>

        <?php else: ?>
          <!-- B：候補 -->
          <?php foreach ($second_trainings as $t): ?>
            <label class="item">
              <input type="checkbox" name="second_ids[]" value="<?= (int)$t['training_id'] ?>">
              <div class="thumb">img</div>
              <div class="name"><?= htmlspecialchars($t['training_name']) ?></div>
              <div class="ab">B</div>
            </label>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- 下固定ボタン -->
<div class="footer">
  <button class="combine-btn" id="combineBtn" disabled>トレーニングを組み合わせる</button>
</div>

<script>
const firstTrainingId = <?= (int)$first_training_id ?>;
const checkboxes = Array.from(document.querySelectorAll('input[type="checkbox"][name="second_ids[]"]'));
const combineBtn = document.getElementById('combineBtn');

function updateBtn(){
  combineBtn.disabled = !checkboxes.some(cb => cb.checked);
}
checkboxes.forEach(cb => cb.addEventListener('change', updateBtn));

// 1対1仕様：Bは1つだけ選べる
checkboxes.forEach(cb => {
  cb.addEventListener('change', () => {
    if (!cb.checked) return;
    checkboxes.forEach(other => { if (other !== cb) other.checked = false; });
    updateBtn();
  });
});

combineBtn.addEventListener('click', async () => {
  const selected = checkboxes.filter(cb => cb.checked).map(cb => cb.value);
  if (selected.length === 0) return;

  try {
    const res = await fetch('save_pending_superset.php', {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: new URLSearchParams({
        first_training_id: firstTrainingId,
        second_training_id: selected[0]
      })
    });

    const data = await res.json().catch(() => null);
    if (!res.ok || (data && data.success === false)) {
      alert((data && data.message) ? data.message : '保存に失敗しました');
      return;
    }

    location.href = 'training_select.php';
  } catch (e) {
    console.error(e);
    alert('通信エラーが発生しました');
  }
});

updateBtn();
</script>
</body>
</html>
