<?php
require_once __DIR__ . '/../../config/auth.php';
requireRole(['Admin', 'SK Official']);
require_once __DIR__ . '/../../config/database.php';
$pageTitle = 'Edit Activity';
$rootPath  = '../../';

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM activities WHERE id = ?");
$stmt->execute([$id]);
$data = $stmt->fetch();

if (!$data) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Activity not found.'];
    header('Location: index.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['title'] = trim($_POST['title'] ?? '');
    $data['description'] = trim($_POST['description'] ?? '');
    $data['activity_date'] = trim($_POST['activity_date'] ?? '');
    $data['status'] = trim($_POST['status'] ?? 'Ongoing');

    if (empty($data['title'])) $errors[] = 'Activity title is required.';
    if (empty($data['activity_date'])) $errors[] = 'Activity date is required.';

    if (empty($errors)) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $stmt = $db->prepare("UPDATE activities SET title=?, description=?, activity_date=?, status=? WHERE id=?");
        $stmt->execute([$data['title'], $data['description'], $data['activity_date'], $data['status'], $id]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Activity updated successfully.'];
        header('Location: index.php');
        exit;
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header mb-4">
    <h4><i class="bi bi-pencil-square me-2"></i>Edit Activity</h4>
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
                <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($data['title']) ?>" required>
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
            <div class="text-end mt-2 d-flex justify-content-between">
                <a href="delete.php?id=<?= $id ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete this activity?');">
                    <i class="bi bi-trash me-1"></i>Delete
                </a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
