<?php
require_once __DIR__ . '/../../config/auth.php';
requireRole(['Admin', 'SK Official', 'Katipunan Member']);
require_once __DIR__ . '/../../config/database.php';
$pageTitle = 'Activities';
$rootPath  = '../../';

$db = getDB();
$currentUser = getCurrentUser();
$role = $currentUser['role'] ?? '';
$isKK = $role === 'Katipunan Member';

$where = "";
$params = [];
if ($isKK) {
    $where = "WHERE status = 'Ongoing'";
}

$stmt = $db->prepare("SELECT * FROM activities $where ORDER BY activity_date DESC, id DESC");
$stmt->execute($params);
$activities = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-calendar-event me-2"></i><?= $isKK ? 'Ongoing Activities' : 'All Activities' ?></h4>
    <?php if (!$isKK): ?>
    <a href="add.php" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Add Activity
    </a>
    <?php endif; ?>
</div>

<div class="card content-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="text-muted small">
                    <tr>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Date</th>
                        <th>Status</th>
                        <?php if (!$isKK): ?>
                        <th class="text-end">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($activities)): ?>
                    <tr>
                        <td colspan="<?= $isKK ? '4' : '5' ?>" class="text-center py-5 text-muted">
                            <i class="bi bi-calendar-x fs-2 d-block mb-2 opacity-25"></i>
                            No activities found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($activities as $a): ?>
                    <tr>
                        <td class="fw-semibold text-dark"><?= htmlspecialchars($a['title']) ?></td>
                        <td class="text-muted small" style="max-width:300px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            <?= htmlspecialchars($a['description'] ?? '—') ?>
                        </td>
                        <td class="small fw-semibold"><?= date('M d, Y', strtotime($a['activity_date'])) ?></td>
                        <td>
                            <?php if ($a['status'] === 'Ongoing'): ?>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1"><i class="bi bi-play-circle-fill me-1"></i>Ongoing</span>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i>Completed</span>
                            <?php endif; ?>
                        </td>
                        <?php if (!$isKK): ?>
                        <td class="text-end">
                            <a href="edit.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
