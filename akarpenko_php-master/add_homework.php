<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/layout.php';

requireRole('teacher');
$user = currentUser();
$db = getDB();

$errors = [];
$success = false;

$subjects = ['Математика','Русский язык','Физика','Химия','Биология','История','Литература','Английский язык','Информатика','Другое'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $due_date = trim($_POST['due_date'] ?? '') ?: null;

    if (!$title) $errors[] = 'Укажите название задания';
    if (!$description) $errors[] = 'Добавьте описание задания';
    if (!$subject || !in_array($subject, $subjects)) $errors[] = 'Выберите предмет';

    if (empty($errors)) {
        $stmt = $db->prepare("INSERT INTO homework (teacher_id, title, description, subject, due_date) VALUES (?,?,?,?,?)");
        $stmt->execute([$user['id'], $title, $description, $subject, $due_date]);
        $newId = $db->lastInsertId();
        header('Location: homework_detail.php?id='.$newId.'&created=1');
        exit;
    }
}

ob_start();
?>
<div class="topbar">
  <div>
    <div class="topbar-title">Новое задание</div>
    <div class="topbar-sub">Заполните форму ниже</div>
  </div>
  <a href="homework.php" class="btn btn-outline btn-sm">← Назад</a>
</div>
<div class="content" style="max-width:700px">
  <?php if ($errors): ?>
  <div class="alert alert-error">
    ⚠ <?= implode('; ', array_map('htmlspecialchars', $errors)) ?>
  </div>
  <?php endif; ?>

  <div class="card">
    <form method="POST">
      <div class="form-group">
        <label class="form-label">Предмет</label>
        <select name="subject" class="form-control">
          <option value="">Выберите предмет...</option>
          <?php foreach ($subjects as $s): ?>
          <option value="<?= $s ?>" <?= (($_POST['subject'] ?? '') === $s) ? 'selected' : '' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Название задания</label>
        <input type="text" name="title" class="form-control"
               placeholder="Например: Параграф 12, упражнения 1-5"
               value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label class="form-label">Описание / условие задания</label>
        <textarea name="description" class="form-control" rows="6"
                  placeholder="Подробно опишите задание: что нужно сделать, на что обратить внимание..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
      </div>

      <div class="form-group">
        <label class="form-label">Срок сдачи (необязательно)</label>
        <input type="date" name="due_date" class="form-control"
               value="<?= htmlspecialchars($_POST['due_date'] ?? '') ?>"
               min="<?= date('Y-m-d') ?>">
      </div>

      <div style="display:flex;gap:10px;margin-top:8px">
        <button type="submit" class="btn btn-dark">✏️ Опубликовать задание</button>
        <a href="homework.php" class="btn btn-outline">Отмена</a>
      </div>
    </form>
  </div>
</div>
<?php
$pageContent = ob_get_clean();
echo renderLayout('Новое задание', 'add_homework', $pageContent);
