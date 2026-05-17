<?php
require_once __DIR__ . '/../../config/auth.php';
requireRole('Admin');
require_once __DIR__ . '/../../config/database.php';
$pageTitle = 'User Management';
$rootPath  = '../../';

$db = getDB();

if (session_status() === PHP_SESSION_NONE) session_start();
$flash = null;
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

$stmt = $db->query("SELECT id, username, full_name, role, created_at FROM users ORDER BY role, full_name");
$users = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
    <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-shield-lock-fill me-2 text-primary"></i>User Management</h4>
    <a href="add.php" class="btn btn-primary">
        <i class="bi bi-person-plus-fill me-2"></i>Add User
    </a>
</div>

<div class="card content-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="text-muted small">
                    <tr>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Created At</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted">No users found.</td></tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($u['full_name']) ?></td>
                        <td><span class="text-muted">@</span><?= htmlspecialchars($u['username']) ?></td>
                        <td>
                            <?php if ($u['role'] === 'Admin'): ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-shield-fill me-1"></i>Admin</span>
                            <?php elseif ($u['role'] === 'SK Official'): ?>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-person-badge-fill me-1"></i>SK Official</span>
                            <?php else: ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-person-fill me-1"></i>Katipunan Member</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                        <td class="text-end">
                            <a href="edit.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php if ($u['id'] !== $currentUser['id']): ?>
                            <a href="delete.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this user?');">
                                <i class="bi bi-trash"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
