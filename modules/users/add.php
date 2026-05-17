<?php
require_once __DIR__ . '/../../config/auth.php';
requireRole('Admin');
require_once __DIR__ . '/../../config/database.php';
$pageTitle = 'Add User';
$rootPath  = '../../';

$db = getDB();
$errors = [];
$data = ['username' => '', 'full_name' => '', 'role' => 'Katipunan Member'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['username'] = trim($_POST['username'] ?? '');
    $data['full_name'] = trim($_POST['full_name'] ?? '');
    $data['role'] = trim($_POST['role'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($data['username'])) $errors[] = 'Username is required.';
    if (empty($data['full_name'])) $errors[] = 'Full name is required.';
    if (empty($password)) $errors[] = 'Password is required.';
    
    // Check if username already exists
    if (empty($errors)) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$data['username']]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Username already exists. Please choose another one.';
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$data['username'], $hash, $data['full_name'], $data['role']]);
        
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'User added successfully.'];
        header('Location: index.php');
        exit;
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header mb-4 d-flex justify-content-between align-items-center">
    <h4 class="mb-0"><i class="bi bi-person-plus-fill me-2"></i>Add User</h4>
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
                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($data['full_name']) ?>" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">Username <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text">@</span>
                    <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($data['username']) ?>" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Password <span class="text-danger">*</span></label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Role <span class="text-danger">*</span></label>
                <select name="role" class="form-select" required>
                    <option value="Admin" <?= $data['role'] === 'Admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="SK Official" <?= $data['role'] === 'SK Official' ? 'selected' : '' ?>>SK Official</option>
                    <option value="Katipunan Member" <?= $data['role'] === 'Katipunan Member' ? 'selected' : '' ?>>Katipunan Member</option>
                </select>
            </div>
            <div class="text-end mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save User</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
