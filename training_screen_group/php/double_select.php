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

// 選択済み（training_select.php側で作る前提）
$selected_trainings = $_SESSION['workout_trainings'] ?? [];
$selected_count = is_array($selected_trainings) ? count($selected_trainings) : 0;

// 1種目目
$stmt = $pdo->prepare("SELECT training_id, training_name FROM trainings WHERE training_id = ?");
$stmt->execute([$first_training_id]);
$first_training = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$first_training) {
    exit('トレーニングが見つかりません');
}

// 2種目目候補（2件以上ある時だけ作る）
$second_candidates = [];
if ($selected_count >= 2) {
    $second_candidates = array_values(array_diff($selected_trainings, [$first_training_id]));
}

// 2種目目候補の表示データ
$second_trainings = [];
if (!empty($second_candidates)) {
    $placeholders = implode(',', array_fill(0, count($second_candidates), '?'));
    $sql = "SELECT training_id, training_name
            FROM trainings
            WHERE training_id IN ($placeholders)
            ORDER BY training_id";
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
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<style>
/* ===== 背景 ===== */
body{
  margin:0;
  font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
  background:#ededed;
}

/* ===== 画面全体 ===== */
.page{
  max-width:420px;
  margin:0 auto;
  min-height:100vh;
  display:flex;
  align-items:center;
  justify-content:center;
  padding:16px 10px;
}

/* ===== モーダル箱（画像の角丸） ===== */
.modal{
  width:100%;
  border-radius:18px;
  overflow:hidden;
  background:#d9d9d9;
  box-shadow:0 10px 30px rgba(0,0,0,.12);
}

/* ===== ヘッダー ===== */
.modal-header{
  background:#cfcfcf;
  display:flex;
  align-items:center;
  justify-content:space-between;
  padding:14px 14px;
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
}
.modal-title{
  font-weight:800;
  font-size:16px;
  color:#111;
}

/* ===== リスト ===== */
.list{
  background:#d9d9d9;
  padding:10px 0 0;
}
.item{
  background:#bfbfbf;
  margin:8px 10px;
  border-radius:4px;
  display:flex;
  align-items:center;
  padding:10px 10px;
  gap:10px;
}

/* チェックボックス：左の赤い枠っぽく */
.item input[type="checkbox"]{
  width:18px;
  height:18px;
  accent-color:#d66b6b; /* 赤系 */
}

/* サムネ（画像枠） */
.thumb{
  width:38px;
  height:28px;
  border-radius:3px;
  background:#e9e9e9;
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:10px;
  color:#666;
}

/* 種目名 */
.name{
  flex:1;
  font-size:14px;
  font-weight:700;
  color:#111;
  overflow:hidden;
  white-space:nowrap;
  text-overflow:ellipsis;
}

/* A/B */
.ab{
  width:18px;
  text-align:right;
  font-weight:900;
  color:#666;
  font-size:13px;
}

/* ===== 下部ボタン（画像の大きい赤ボタン） ===== */
.footer{
  background:#d9d9d9;
  padding:16px 12px 16px;
}
.combine-btn{
  width:100%;
  border:none;
  border-radius:10px;
  padding:14px 12px;
  font-size:15px;
  font-weight:800;
  cursor:pointer;
  background:#ff6e6e;
  color:#fff;
}
.combine-btn:disabled{
  background:#ffb4b4;
  cursor:not-allowed;
}

/* ===== 案内メッセージ ===== */
.notice{
  margin:8px 10px;
  background:#f7f7f7;
  border-radius:10px;
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
</style>
</head>

<body>
<div class="page">
  <div class="modal">
    <div class="modal-header">
      <button class="icon-btn" onclick="history.back()">＜</button>
      <div class="modal-title">スーパーセット</div>
      <button class="icon-btn" onclick="history.back()">×</button>
    </div>

    <div class="list" id="supersetList">
      <!-- A：1種目目（固定表示） -->
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

    <div class="footer">
      <button class="combine-btn" id="combineBtn" disabled>トレーニングを組み合わせる</button>
    </div>
  </div>
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
