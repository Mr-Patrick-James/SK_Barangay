<?php
require_once __DIR__ . '/../../config/database.php';
$pageTitle = 'Barangay Officials';
$rootPath = '../../';

$db = getDB();

if (session_status() === PHP_SESSION_NONE) session_start();
$flash = null;
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

$officials = $db->query("SELECT * FROM officials ORDER BY
    CASE position
        WHEN 'Barangay Captain' THEN 1
        WHEN 'Barangay Secretary' THEN 2
        WHEN 'Barangay Treasurer' THEN 3
        WHEN 'SK Chairman' THEN 4
        WHEN 'Barangay Kagawad' THEN 5
        ELSE 6
    END, last_name, first_name")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
    <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($flash['msg']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="page-header">
    <h4><i class="bi bi-person-badge-fill me-2"></i>Barangay Officials</h4>
    <a href="add.php" class="btn btn-success">
        <i class="bi bi-person-plus-fill me-1"></i> Add Official
    </a>
</div>

<!-- Officials Grid -->
<?php
$captain = null;
$kagawads = [];
$others = [];
foreach ($officials as $o) {
    if ($o['position'] === 'Barangay Captain') $captain = $o;
    elseif ($o['position'] === 'Barangay Kagawad') $kagawads[] = $o;
    else $others[] = $o;
}
?>

<?php if ($captain): ?>
<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="card content-card border-primary">
            <div class="card-body d-flex align-items-center gap-4 py-3">
                <div class="stat-icon bg-primary-subtle" style="width:64px;height:64px;border-radius:50%;font-size:2rem">
                    <i class="bi bi-person-fill-gear text-primary"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-bold fs-5"><?= htmlspecialchars($captain['first_name'] . ' ' . ($captain['middle_name'] ? $captain['middle_name'] . ' ' : '') . $captain['last_name']) ?></div>
                    <div class="text-primary fw-semibold"><?= htmlspecialchars($captain['position']) ?></div>
                    <div class="text-muted small">
                        Term: <?= $captain['term_start'] ? date('Y', strtotime($captain['term_start'])) : '—' ?>
                        – <?= $captain['term_end'] ? date('Y', strtotime($captain['term_end'])) : '—' ?>
                        <?php if ($captain['contact']): ?>
                        &nbsp;|&nbsp; <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($captain['contact']) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="edit.php?id=<?= $captain['id'] ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil-fill me-1"></i>Edit
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Kagawads -->
<?php if (!empty($kagawads)): ?>
<h6 class="text-muted fw-semibold mb-2 mt-3">
    <i class="bi bi-people me-1"></i>Sangguniang Barangay Members (Kagawad)
</h6>
<div class="row g-3 mb-3">
    <?php foreach ($kagawads as $o): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card content-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-success-subtle" style="width:48px;height:48px;border-radius:50%;font-size:1.3rem;flex-shrink:0">
                    <i class="bi bi-person-fill text-success"></i>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="fw-semibold text-truncate"><?= htmlspecialchars($o['first_name'] . ' ' . ($o['middle_name'] ? $o['middle_name'] . ' ' : '') . $o['last_name']) ?></div>
                    <div class="small text-success"><?= htmlspecialchars($o['position']) ?></div>
                    <?php if ($o['contact']): ?>
                    <div class="small text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($o['contact']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="d-flex flex-column gap-1">
                    <a href="edit.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2">
                        <i class="bi bi-pencil-fill"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Other Officials -->
<?php if (!empty($others)): ?>
<h6 class="text-muted fw-semibold mb-2 mt-3">
    <i class="bi bi-person-badge me-1"></i>Other Officials
</h6>
<div class="row g-3 mb-3">
    <?php foreach ($others as $o): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card content-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-warning-subtle" style="width:48px;height:48px;border-radius:50%;font-size:1.3rem;flex-shrink:0">
                    <i class="bi bi-person-fill text-warning"></i>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="fw-semibold text-truncate"><?= htmlspecialchars($o['first_name'] . ' ' . ($o['middle_name'] ? $o['middle_name'] . ' ' : '') . $o['last_name']) ?></div>
                    <div class="small text-warning"><?= htmlspecialchars($o['position']) ?></div>
                    <?php if ($o['contact']): ?>
                    <div class="small text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($o['contact']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="d-flex flex-column gap-1">
                    <a href="edit.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2">
                        <i class="bi bi-pencil-fill"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Full Table -->
<div class="card content-card mt-3">
    <div class="card-header"><i class="bi bi-table me-1"></i>All Officials — Full List</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Position</th>
                        <th>Term</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($officials)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No officials found.</td></tr>
                <?php else: ?>
                    <?php foreach ($officials as $i => $o): ?>
                    <tr>
                        <td class="text-muted"><?= $i + 1 ?></td>
                        <td class="fw-semibold"><?= htmlspecialchars($o['first_name'] . ' ' . ($o['middle_name'] ? $o['middle_name'] . ' ' : '') . $o['last_name']) ?></td>
                        <td><?= htmlspecialchars($o['position']) ?></td>
                        <td class="text-muted small">
                            <?= $o['term_start'] ? date('Y', strtotime($o['term_start'])) : '—' ?>
                            – <?= $o['term_end'] ? date('Y', strtotime($o['term_end'])) : '—' ?>
                        </td>
                        <td><?= htmlspecialchars($o['contact'] ?? '—') ?></td>
                        <td>
                            <span class="badge <?= $o['status']==='Active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?>">
                                <?= htmlspecialchars($o['status']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="edit.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil-fill"></i>
                            </a>
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
