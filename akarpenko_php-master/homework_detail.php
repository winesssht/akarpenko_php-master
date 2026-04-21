<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/layout.php';

requireLogin();
$user = currentUser();
$db = getDB();
$isTeacher = $user['role'] === 'teacher';

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: homework.php'); exit; }

$stmt = $db->prepare("SELECT h.*, u.full_name as teacher_name FROM homework h JOIN users u ON u.id=h.teacher_id WHERE h.id=?");
$stmt->execute([$id]);
$hw = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$hw) { header('Location: homework.php'); exit; }

$error = ''; $successMsg = '';

// Handle student submission
if (!$isTeacher && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['answer'])) {
    $answer = trim($_POST['answer']);
    if (!$answer) {
        $error = 'Напишите ответ перед отправкой';
    } else {
        $existing = $db->prepare("SELECT id FROM submissions WHERE homework_id=? AND student_id=?");
        $existing->execute([$id, $user['id']]);
        if ($existing->fetchColumn()) {
            $upd = $db->prepare("UPDATE submissions SET answer=?, submitted_at=CURRENT_TIMESTAMP WHERE homework_id=? AND student_id=?");
            $upd->execute([$answer, $id, $user['id']]);
            $successMsg = 'Ответ обновлён!';
        } else {
            $ins = $db->prepare("INSERT INTO submissions (homework_id, student_id, answer) VALUES (?,?,?)");
            $ins->execute([$id, $user['id'], $answer]);
            $successMsg = 'Ответ отправлен!';
        }
    }
}

// Handle teacher grading
if ($isTeacher && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade'])) {
    $subId = intval($_POST['sub_id']);
    $grade = intval($_POST['grade']);
    $comment = trim($_POST['teacher_comment'] ?? '');
    if ($grade >= 1 && $grade <= 5) {
        $upd = $db->prepare("UPDATE submissions SET grade=?, teacher_comment=? WHERE id=? AND homework_id=?");
        $upd->execute([$grade, $comment, $subId, $id]);
        $successMsg = 'Оценка поставлена!';
    }
}

// Handle delete homework (teacher)
if ($isTeacher && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_hw'])) {
    if ($hw['teacher_id'] == $user['id']) {
        $db->prepare("DELETE FROM submissions WHERE homework_id=?")->execute([$id]);
        $db->prepare("DELETE FROM homework WHERE id=?")->execute([$id]);
        header('Location: homework.php?deleted=1'); exit;
    }
}

// Get student's submission
$mySubmission = null;
if (!$isTeacher) {
    $s = $db->prepare("SELECT * FROM submissions WHERE homework_id=? AND student_id=?");
    $s->execute([$id, $user['id']]);
    $mySubmission = $s->fetch(PDO::FETCH_ASSOC);
}

// Get all submissions (teacher)
$allSubs = [];
if ($isTeacher) {
    $s = $db->prepare("SELECT s.*, u.full_name FROM submissions s JOIN users u ON u.id=s.student_id WHERE s.homework_id=? ORDER BY s.submitted_at DESC");
    $s->execute([$id]);
    $allSubs = $s->fetchAll(PDO::FETCH_ASSOC);
}

function subjectClass($s) {
    $map=['Математика'=>'subject-math','Русский язык'=>'subject-rus','Физика'=>'subject-sci','Биология'=>'subject-sci','История'=>'subject-hist','Химия'=>'subject-sci'];
    return $map[$s]??'subject-default';
}

ob_start();
$created = isset($_GET['created']);
?>
<div class="topbar">
  <div>
    <div class="topbar-title"><?= htmlspecialchars($hw['title']) ?></div>
    <div class="topbar-sub"><?= htmlspecialchars($hw['subject']) ?> · <?= htmlspecialchars($hw['teacher_name']) ?></div>
  </div>
  <a href="homework.php" class="btn btn-outline btn-sm">← К заданиям</a>
</div>
<div class="content">

  <?php if ($created): ?>
  <div class="alert alert-success">✅ Задание успешно опубликовано!</div>
  <?php endif; ?>

  <?php if ($successMsg): ?>
  <div class="alert alert-success">✅ <?= htmlspecialchars($successMsg) ?></div>
  <?php endif; ?>

  <?php if ($error): ?>
  <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <!-- HW Card -->
  <div class="card" style="margin-bottom:24px">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px">
      <div style="flex:1">
        <span class="hw-subject <?= subjectClass($hw['subject']) ?>" style="margin-bottom:12px"><?= htmlspecialchars($hw['subject']) ?></span>
        <h1 style="font-family:'Playfair Display',serif;font-size:26px;color:var(--ink);margin:8px 0 12px;line-height:1.2"><?= htmlspecialchars($hw['title']) ?></h1>
        <div style="font-size:15px;color:var(--ink);line-height:1.7;white-space:pre-wrap"><?= htmlspecialchars($hw['description']) ?></div>
      </div>
    </div>
    <div style="display:flex;gap:20px;flex-wrap:wrap;margin-top:20px;padding-top:16px;border-top:1px solid var(--border);font-size:13px;color:var(--muted)">
      <span>👤 <?= htmlspecialchars($hw['teacher_name']) ?></span>
      <span>🗓 Создано: <?= date('d.m.Y', strtotime($hw['created_at'])) ?></span>
      <?php if ($hw['due_date']): ?>
      <span>📅 Срок: <?= date('d.m.Y', strtotime($hw['due_date'])) ?></span>
      <?php endif; ?>
      <?php if ($isTeacher): ?><span>📬 Ответов: <?= count($allSubs) ?></span><?php endif; ?>
    </div>

    <?php if ($isTeacher && $hw['teacher_id'] == $user['id']): ?>
    <div style="margin-top:16px;display:flex;gap:8px">
      <a href="add_homework.php?edit=<?= $id ?>" class="btn btn-outline btn-sm">✏️ Редактировать</a>
      <form method="POST" onsubmit="return confirm('Удалить это задание и все ответы?')">
        <input type="hidden" name="delete_hw" value="1">
        <button type="submit" class="btn btn-danger btn-sm">🗑 Удалить</button>
      </form>
    </div>
    <?php endif; ?>
  </div>

  <?php if (!$isTeacher): ?>
  <!-- STUDENT ANSWER FORM -->
  <div class="card" style="margin-bottom:24px">
    <h2 style="font-family:'Playfair Display',serif;font-size:20px;margin-bottom:16px">
      <?= $mySubmission ? '✏️ Мой ответ' : '📝 Отправить ответ' ?>
    </h2>

    <?php if ($mySubmission && $mySubmission['grade']): ?>
    <div style="display:flex;align-items:center;gap:16px;background:rgba(42,107,232,.06);border:1px solid rgba(42,107,232,.15);border-radius:12px;padding:16px;margin-bottom:20px">
      <div class="grade-circle grade-<?= $mySubmission['grade'] ?>"><?= $mySubmission['grade'] ?></div>
      <div>
        <div style="font-weight:500;font-size:15px">Оценка: <?= $mySubmission['grade'] ?>/5</div>
        <?php if ($mySubmission['teacher_comment']): ?>
        <div style="font-size:13px;color:var(--muted);margin-top:4px">💬 <?= htmlspecialchars($mySubmission['teacher_comment']) ?></div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label class="form-label"><?= $mySubmission ? 'Изменить ответ' : 'Ваш ответ' ?></label>
        <textarea name="answer" class="form-control" rows="6"
                  placeholder="Напишите ваш ответ здесь..."><?= htmlspecialchars($mySubmission['answer'] ?? '') ?></textarea>
      </div>
      <button type="submit" class="btn btn-dark">
        <?= $mySubmission ? '💾 Обновить ответ' : '📤 Отправить ответ' ?>
      </button>
      <?php if ($mySubmission): ?>
      <div style="margin-top:10px;font-size:13px;color:var(--muted)">
        Последнее обновление: <?= date('d.m.Y H:i', strtotime($mySubmission['submitted_at'])) ?>
      </div>
      <?php endif; ?>
    </form>
  </div>
  <?php endif; ?>

  <?php if ($isTeacher && !empty($allSubs)): ?>
  <!-- TEACHER: SUBMISSIONS LIST -->
  <h2 style="font-family:'Playfair Display',serif;font-size:20px;margin-bottom:16px">📬 Ответы учеников (<?= count($allSubs) ?>)</h2>
  <div style="display:grid;gap:16px">
    <?php foreach ($allSubs as $sub): ?>
    <div class="card card-sm" id="sub-<?= $sub['id'] ?>">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px;margin-bottom:14px">
        <div style="display:flex;align-items:center;gap:10px">
          <div class="avatar student-av" style="width:34px;height:34px;font-size:13px"><?= mb_strtoupper(mb_substr($sub['full_name'],0,1,'UTF-8'),'UTF-8') ?></div>
          <div>
            <div style="font-weight:500;font-size:14px"><?= htmlspecialchars($sub['full_name']) ?></div>
            <div style="font-size:12px;color:var(--muted)">📅 <?= date('d.m.Y H:i', strtotime($sub['submitted_at'])) ?></div>
          </div>
        </div>
        <?php if ($sub['grade']): ?>
          <div class="grade-circle grade-<?= $sub['grade'] ?>" style="width:36px;height:36px;font-size:16px"><?= $sub['grade'] ?></div>
        <?php else: ?>
          <span class="badge badge-pending">Не проверено</span>
        <?php endif; ?>
      </div>
      <div style="background:var(--cream);border-radius:10px;padding:14px;font-size:14px;line-height:1.7;margin-bottom:16px;white-space:pre-wrap">
        <?= htmlspecialchars($sub['answer']) ?>
      </div>

      <!-- Grade form -->
      <form method="POST" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
        <input type="hidden" name="sub_id" value="<?= $sub['id'] ?>">
        <div>
          <label class="form-label" style="margin-bottom:4px">Оценка</label>
          <select name="grade" class="form-control" style="width:90px;padding:8px 10px">
            <?php for($g=5;$g>=1;$g--): ?>
            <option value="<?= $g ?>" <?= ($sub['grade']==$g)?'selected':'' ?>><?= $g ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div style="flex:1;min-width:200px">
          <label class="form-label" style="margin-bottom:4px">Комментарий (необяз.)</label>
          <input type="text" name="teacher_comment" class="form-control" style="padding:9px 12px"
                 placeholder="Отлично! / Нужно доработать..."
                 value="<?= htmlspecialchars($sub['teacher_comment'] ?? '') ?>">
        </div>
        <button type="submit" class="btn btn-success btn-sm" style="margin-bottom:1px">✓ Поставить оценку</button>
      </form>
    </div>
    <?php endforeach; ?>
  </div>
  <?php elseif ($isTeacher): ?>
  <div class="card">
    <div class="empty-state">
      <span class="icon">📭</span>
      <h3>Ответов пока нет</h3>
      <p>Ученики ещё не сдали это задание</p>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php
$pageContent = ob_get_clean();
echo renderLayout(htmlspecialchars($hw['title']), 'homework', $pageContent);
