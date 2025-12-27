<?php
// ================================
// 初期処理
// ================================
session_start();
require_once '../../db_connect.php';

// 1種目目（縦三点リーダーから来た）
$first_training_id = filter_input(INPUT_GET, 'first_training_id', FILTER_VALIDATE_INT);
if (!$first_training_id) {
    exit('不正なアクセスです');
}

// 今回のトレーニング選択（training_select.php で作られている前提）
$selected_trainings = $_SESSION['workout_trainings'] ?? [];

if (!is_array($selected_trainings) || count($selected_trainings) < 2) {
    exit('スーパーセットは2種目以上選択している必要があります');
}

// 2種目目候補（選択済み − 1種目目）
$second_candidates = array_values(array_diff($selected_trainings, [$first_training_id]));
if (empty($second_candidates)) {
    exit('スーパーセット候補がありません');
}

// ================================
// DBから表示用データ取得
// ================================

// 1種目目
$stmt = $pdo->prepare("
    SELECT training_id, training_name
    FROM trainings
    WHERE training_id = ?
");
$stmt->execute([$first_training_id]);
$first_training = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$first_training) {
    exit('トレーニングが見つかりません');
}

// 2種目目候補
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
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>スーパーセット</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
/* ===== ベース ===== */
body {
    margin: 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    background: #f2f2f2;
}
.app {
    max-width: 420px;
    margin: 0 auto;
    background: #fff;
    min-height: 100vh;
}

/* ===== ヘッダー（2枚目っぽく） ===== */
.header {
    position: sticky;
    top: 0;
    background: #e6e6e6;
    padding: 14px 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #d0d0d0;
}
.header .left,
.header .right {
    width: 44px;
    text-align: center;
    font-size: 20px;
    cursor: pointer;
    user-select: none;
}
.header .title {
    font-weight: 700;
    font-size: 16px;
}

/* ===== リスト ===== */
.list {
    padding: 10px 0 88px; /* 下の固定ボタン分 */
    background: #f4f4f4;
    min-height: calc(100vh - 54px);
}
.row {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #d9d9d9;
    margin: 6px 10px;
    padding: 10px;
    border-radius: 6px;
}
.row input[type="checkbox"]{
    width: 18px;
    height: 18px;
    accent-color: #ff6e6e;
}
.row .thumb {
    width: 38px;
    height: 28px;
    border-radius: 4px;
    background: #bdbdbd; /* 画像が無い前提ならプレースホルダ */
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    color: #444;
}
.row .name {
    flex: 1;
    font-size: 14px;
    font-weight: 600;
    color: #111;
}
.row .badge {
    font-size: 12px;
    font-weight: 700;
    color: #666;
}

/* ===== 下の固定ボタン ===== */
.bottom {
    position: fixed;
    left: 50%;
    transform: translateX(-50%);
    bottom: 0;
    width: 100%;
    max-width: 420px;
    background: #fff;
    padding: 10px 12px 14px;
    border-top: 1px solid #e0e0e0;
}
.btn {
    width: 100%;
    border: none;
    border-radius: 8px;
    padding: 14px 12px;
    font-size: 15px;
    font-weight: 700;
    background: #ff6e6e;
    color: #fff;
    cursor: pointer;
}
.btn:disabled {
    background: #ffb3b3;
    cursor: not-allowed;
}

/* ===== 小さい説明 ===== */
.note {
    margin: 10px 12px 0;
    font-size: 12px;
    color: #666;
    line-height: 1.4;
}
.note strong { color:#111; }
</style>
</head>

<body>
<div class="app">

    <!-- ヘッダー -->
    <div class="header">
        <div class="left" onclick="history.back()">＜</div>
        <div class="title">スーパーセット</div>
        <div class="right" onclick="history.back()">×</div>
    </div>

    <div class="note">
        1種目目：<strong><?= htmlspecialchars($first_training['training_name']) ?></strong><br>
        2種目目をチェックして「トレーニングを組み合わせる」で確定
    </div>

    <!-- リスト -->
    <div class="list" id="supersetList">
        <?php
        // A/B 表示したいなら候補側は B にしておく（見た目だけ）
        foreach ($second_trainings as $t):
        ?>
            <label class="row">
                <input type="checkbox" name="second_ids[]" value="<?= (int)$t['training_id'] ?>">
                <div class="thumb">img</div>
                <div class="name"><?= htmlspecialchars($t['training_name']) ?></div>
                <div class="badge">B</div>
            </label>
        <?php endforeach; ?>
    </div>

    <!-- 下固定ボタン -->
    <div class="bottom">
        <button class="btn" id="combineBtn" disabled>トレーニングを組み合わせる</button>
    </div>

</div>

<script>
const firstTrainingId = <?= (int)$first_training_id ?>;

const checkboxes = Array.from(document.querySelectorAll('input[type="checkbox"][name="second_ids[]"]'));
const combineBtn = document.getElementById('combineBtn');

function updateButtonState() {
    const anyChecked = checkboxes.some(cb => cb.checked);
    combineBtn.disabled = !anyChecked;
}
checkboxes.forEach(cb => cb.addEventListener('change', updateButtonState));
updateButtonState();

/**
 * 仕様：2枚目みたいにチェックボックス式
 * - 1対1（親→子）にしたいなら「1個だけ選択」に制限するのが自然
 *   → 下のコードで “最後にチェックした1つだけ残す” 動きにしてる
 */
checkboxes.forEach(cb => {
    cb.addEventListener('change', () => {
        if (!cb.checked) return;
        // 1個だけに制限（必要なら消して複数選択にできる）
        checkboxes.forEach(other => {
            if (other !== cb) other.checked = false;
        });
        updateButtonState();
    });
});

combineBtn.addEventListener('click', async () => {
    const selected = checkboxes.filter(cb => cb.checked).map(cb => cb.value);

    if (selected.length === 0) {
        alert('2種目目を選択してください');
        return;
    }

    try {
        const res = await fetch('save_pending_superset.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                first_training_id: firstTrainingId,
                // 1対1想定なので1つだけ送る
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
</script>

</body>
</html>
