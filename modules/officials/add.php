<?php
require_once __DIR__ . '/../../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$pageTitle = 'Add Official';
$rootPath = '../../';

$errors = [];
$data = [
    'first_name'  => '',
    'middle_name' => '',
    'last_name'   => '',
    'position'    => '',
    'term_start'  => '',
    'term_end'    => '',
    'contact'     => '',
    'status'      => 'Active',
];

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
    foreach ($data as $key => $_) {
        $data[$key] = trim($_POST[$key] ?? '');
    }

    if (empty($data['first_name'])) $errors[] = 'First name is required.';
    if (empty($data['last_name']))  $errors[] = 'Last name is required.';
    if (empty($data['position']))   $errors[] = 'Position is required.';

    $create_account = isset($_POST['create_account']);
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (empty($password)) $password = 'sk123';

    if ($create_account) {
        if (empty($username)) $errors[] = 'Username is required if creating an account.';
        else {
            $db = getDB();
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) $errors[] = 'Username already exists. Please choose another one.';
        }
    }

    if (empty($errors)) {
        $db = getDB();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("
                INSERT INTO officials (first_name, middle_name, last_name, position, term_start, term_end, contact, status)
                VALUES (:first_name, :middle_name, :last_name, :position, :term_start, :term_end, :contact, :status)
            ");
            $stmt->execute($data);
            
            if ($create_account) {
                $role = (strpos($data['position'], 'SK') !== false) ? 'SK Official' : 'Admin';
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $fullName = $data['first_name'] . ' ' . $data['last_name'];
                $stmt = $db->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $hash, $fullName, $role]);
            }
            
            $db->commit();
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Official added successfully.'];
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h4><i class="bi bi-person-plus-fill me-2"></i>Add Official</h4>
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
    <div class="card-header"><i class="bi bi-person-badge me-2"></i>Official Information</div>
    <div class="card-body">
        <form method="POST" novalidate>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($data['first_name']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Middle Name</label>
                    <input type="text" name="middle_name" class="form-control" value="<?= htmlspecialchars($data['middle_name']) ?>">
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
                        <option value="<?= $p ?>" <?= $data['position']===$p?'selected':'' ?>><?= $p ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="Active" <?= $data['status']==='Active'?'selected':'' ?>>Active</option>
                        <option value="Inactive" <?= $data['status']==='Inactive'?'selected':'' ?>>Inactive</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Term Start</label>
                    <input type="date" name="term_start" class="form-control" value="<?= htmlspecialchars($data['term_start']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Term End</label>
                    <input type="date" name="term_end" class="form-control" value="<?= htmlspecialchars($data['term_end']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Contact Number</label>
                    <input type="text" name="contact" class="form-control" value="<?= htmlspecialchars($data['contact']) ?>">
                </div>

                <hr class="mt-4 mb-2 text-secondary">
                <h6 class="mb-3 text-primary"><i class="bi bi-shield-lock me-2"></i>Create User Account (Optional)</h6>
                <div class="col-md-12 mb-2">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="create_account" id="createAccount" <?= isset($_POST['create_account']) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="createAccount">Generate a login account for this official</label>
                        <div class="form-text text-muted">SK Officials will automatically receive the "SK Official" role. Others will receive "Admin".</div>
                    </div>
                </div>
                <div id="accountFields" class="row g-3 <?= isset($_POST['create_account']) ? '' : 'd-none' ?>">
                    <div class="col-md-6">
                        <label class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text">@</span>
                            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Default: sk123">
                    </div>
                </div>

                <script>
                    document.getElementById('createAccount').addEventListener('change', function() {
                        document.getElementById('accountFields').classList.toggle('d-none', !this.checked);
                    });
                </script>

                <div class="col-12 d-flex gap-2 justify-content-end pt-2">
                    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save me-1"></i> Save Official
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
