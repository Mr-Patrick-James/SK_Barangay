<?php
require_once __DIR__ . '/../../config/auth.php';
requireRole(['Admin', 'SK Official']);
require_once __DIR__ . '/../../config/database.php';
$pageTitle = 'Add Activity';
$rootPath  = '../../';

$db = getDB();
$errors = [];
$data = ['title' => '', 'description' => '', 'activity_date' => date('Y-m-d'), 'status' => 'Ongoing'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['title'] = trim($_POST['title'] ?? '');
    $data['description'] = trim($_POST['description'] ?? '');
    $data['activity_date'] = trim($_POST['activity_date'] ?? '');
    $data['status'] = trim($_POST['status'] ?? 'Ongoing');

    if (empty($data['title'])) $errors[] = 'Activity title is required.';
    if (empty($data['activity_date'])) $errors[] = 'Activity date is required.';

    if (empty($errors)) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $stmt = $db->prepare("INSERT INTO activities (title, description, activity_date, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$data['title'], $data['description'], $data['activity_date'], $data['status']]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Activity added successfully.'];
        header('Location: index.php');
        exit;
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header mb-4">
    <h4><i class="bi bi-calendar-plus me-2"></i>Add Activity</h4>
    <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger">
    <ul class="mb-0">
        <?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card content-card" style="max-width: 600px;">
    <div class="card-body">
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Activity Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($data['title']) ?>" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($data['description']) ?></textarea>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Activity Date <span class="text-danger">*</span></label>
                    <input type="date" name="activity_date" class="form-control" value="<?= htmlspecialchars($data['activity_date']) ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="Ongoing" <?= $data['status'] === 'Ongoing' ? 'selected' : '' ?>>Ongoing</option>
                        <option value="Completed" <?= $data['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                    </select>
                </div>
            </div>
            <div class="text-end mt-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Activity</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
