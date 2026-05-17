<?php
require_once __DIR__ . '/../../config/auth.php';
requireRole('Admin');
require_once __DIR__ . '/../../config/database.php';
$pageTitle = 'Edit User';
$rootPath  = '../../';

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$data = $stmt->fetch();

if (!$data) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'User not found.'];
    header('Location: index.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username)) $errors[] = 'Username is required.';
    if (empty($full_name)) $errors[] = 'Full name is required.';
    
    // Check if username already exists for a different user
    if (empty($errors)) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $id]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Username already exists. Please choose another one.';
        }
    }

    if (empty($errors)) {
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET username=?, full_name=?, role=?, password=? WHERE id=?");
            $stmt->execute([$username, $full_name, $role, $hash, $id]);
        } else {
            $stmt = $db->prepare("UPDATE users SET username=?, full_name=?, role=? WHERE id=?");
            $stmt->execute([$username, $full_name, $role, $id]);
        }
        
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'User updated successfully.'];
        header('Location: index.php');
        exit;
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header mb-4 d-flex justify-content-between align-items-center">
    <h4 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit User</h4>
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
                <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($data['full_name']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Username <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text">@</span>
                    <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($data['username']) ?>" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">New Password</label>
                <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
            </div>
            <div class="mb-3">
                <label class="form-label">Role <span class="text-danger">*</span></label>
                <select name="role" class="form-select" required>
                    <option value="Admin" <?= $data['role'] === 'Admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="SK Official" <?= $data['role'] === 'SK Official' ? 'selected' : '' ?>>SK Official</option>
                    <option value="Katipunan Member" <?= $data['role'] === 'Katipunan Member' ? 'selected' : '' ?>>Katipunan Member</option>
                </select>
                <?php if ($id === $currentUser['id']): ?>
                <div class="form-text text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Careful: Changing your own role may log you out or remove your admin access.</div>
                <?php endif; ?>
            </div>
            <div class="text-end mt-4 d-flex justify-content-between">
                <?php if ($id !== $currentUser['id']): ?>
                <a href="delete.php?id=<?= $id ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete this user?');">
                    <i class="bi bi-trash me-1"></i>Delete
                </a>
                <?php else: ?>
                <div></div>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
