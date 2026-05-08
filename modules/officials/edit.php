<?php
require_once __DIR__ . '/../../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$pageTitle = 'Edit Official';
$rootPath = '../../';

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM officials WHERE id = ?");
$stmt->execute([$id]);
$official = $stmt->fetch();

if (!$official) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Official not found.'];
    header('Location: index.php');
    exit;
}

$errors = [];
$data = $official;

$positions = [
    'Barangay Captain',
    'Barangay Kagawad',
    'SK Chairman',
    'Barangay Secretary',
    'Barangay Treasurer',
    'Barangay Health Worker',
    'Barangay Tanod',
    'Other',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['first_name','middle_name','last_name','position','term_start','term_end','contact','status'];
    foreach ($fields as $key) {
        $data[$key] = trim($_POST[$key] ?? '');
    }

    if (empty($data['first_name'])) $errors[] = 'First name is required.';
    if (empty($data['last_name']))  $errors[] = 'Last name is required.';
    if (empty($data['position']))   $errors[] = 'Position is required.';

    if (empty($errors)) {
        $stmt = $db->prepare("
            UPDATE officials SET
                first_name=:first_name, middle_name=:middle_name, last_name=:last_name,
                position=:position, term_start=:term_start, term_end=:term_end,
                contact=:contact, status=:status
            WHERE id=:id
        ");
        $data['id'] = $id;
        $stmt->execute($data);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Official updated successfully.'];
        header('Location: index.php');
        exit;
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h4><i class="bi bi-pencil-square me-2"></i>Edit Official</h4>
    <a href="index.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card content-card" style="max-width:700px">
    <div class="card-header">
        <i class="bi bi-person-badge me-2"></i>
        Editing: <strong><?= htmlspecialchars($official['first_name'] . ' ' . $official['last_name']) ?></strong>
    </div>
    <div class="card-body">
        <form method="POST" novalidate>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($data['first_name']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Middle Name</label>
                    <input type="text" name="middle_name" class="form-control" value="<?= htmlspecialchars($data['middle_name'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($data['last_name']) ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Position <span class="text-danger">*</span></label>
                    <select name="position" class="form-select" required>
                        <option value="">-- Select Position --</option>
                        <?php foreach ($positions as $p): ?>
                        <option value="<?= $p ?>" <?= ($data['position']??'')===$p?'selected':'' ?>><?= $p ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="Active" <?= ($data['status']??'')==='Active'?'selected':'' ?>>Active</option>
                        <option value="Inactive" <?= ($data['status']??'')==='Inactive'?'selected':'' ?>>Inactive</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Term Start</label>
                    <input type="date" name="term_start" class="form-control" value="<?= htmlspecialchars($data['term_start'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Term End</label>
                    <input type="date" name="term_end" class="form-control" value="<?= htmlspecialchars($data['term_end'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Contact Number</label>
                    <input type="text" name="contact" class="form-control" value="<?= htmlspecialchars($data['contact'] ?? '') ?>">
                </div>

                <div class="col-12 d-flex gap-2 justify-content-end pt-2">
                    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Update Official
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
