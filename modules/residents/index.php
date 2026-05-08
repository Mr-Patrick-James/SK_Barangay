<?php
require_once __DIR__ . '/../../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$pageTitle = 'Residents';
$rootPath = '../../';

$db = getDB();

$search = trim($_GET['search'] ?? '');
$gender = $_GET['gender'] ?? '';
$civil  = $_GET['civil'] ?? '';

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(first_name LIKE ? OR last_name LIKE ? OR middle_name LIKE ? OR address LIKE ? OR contact LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
}
if ($gender !== '') {
    $where[] = "gender = ?";
    $params[] = $gender;
}
if ($civil !== '') {
    $where[] = "civil_status = ?";
    $params[] = $civil;
}

$sql = "SELECT * FROM residents" . ($where ? " WHERE " . implode(" AND ", $where) : "") . " ORDER BY last_name, first_name";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$residents = $stmt->fetchAll();

$flash = null;
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

include __DIR__ . '/../../includes/header.php';
?>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
    <i class="bi bi-<?= $flash['type']==='success' ? 'check-circle' : 'exclamation-triangle' ?>-fill me-2"></i>
    <?= htmlspecialchars($flash['msg']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="page-header">
    <h4><i class="bi bi-people-fill me-2"></i>Residents</h4>
    <a href="add.php" class="btn btn-primary">
        <i class="bi bi-person-plus-fill me-1"></i> Add Resident
    </a>
</div>

<!-- Filters -->
<div class="card content-card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5">
                <div class="search-wrapper">
                    <i class="bi bi-search"></i>
                    <input type="text" name="search" id="tableSearch" class="form-control form-control-sm"
                           placeholder="Search by name, address, contact..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-2">
                <select name="gender" class="form-select form-select-sm">
                    <option value="">All Genders</option>
                    <option value="Male" <?= $gender==='Male'?'selected':'' ?>>Male</option>
                    <option value="Female" <?= $gender==='Female'?'selected':'' ?>>Female</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="civil" class="form-select form-select-sm">
                    <option value="">All Civil Status</option>
                    <?php foreach (['Single','Married','Widowed','Separated','Divorced'] as $cs): ?>
                    <option value="<?= $cs ?>" <?= $civil===$cs?'selected':'' ?>><?= $cs ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <a href="index.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x-circle"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card content-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-list-ul me-1"></i>
            Showing <strong id="searchCount"><?= count($residents) ?></strong> resident(s)
        </span>
        <span class="text-muted small">Total in DB: <?= $db->query("SELECT COUNT(*) FROM residents")->fetchColumn() ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Full Name</th>
                        <th>Gender</th>
                        <th>Civil Status</th>
                        <th>Birthdate</th>
                        <th>Address</th>
                        <th>Contact</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($residents)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>No residents found.
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($residents as $i => $r): ?>
                    <tr>
                        <td class="text-muted"><?= $i + 1 ?></td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($r['last_name'] . ', ' . $r['first_name'] . ($r['middle_name'] ? ' ' . $r['middle_name'] : '')) ?></div>
                            <?php if ($r['occupation']): ?>
                            <small class="text-muted"><?= htmlspecialchars($r['occupation']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= $r['gender']==='Male' ? 'bg-primary-subtle text-primary' : ($r['gender']==='Female' ? 'bg-danger-subtle text-danger' : 'bg-secondary-subtle text-secondary') ?>">
                                <?= htmlspecialchars($r['gender'] ?? '—') ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($r['civil_status'] ?? '—') ?></td>
                        <td><?= $r['birthdate'] ? date('M d, Y', strtotime($r['birthdate'])) : '—' ?></td>
                        <td class="text-truncate" style="max-width:160px" title="<?= htmlspecialchars($r['address'] ?? '') ?>">
                            <?= htmlspecialchars($r['address'] ?? '—') ?>
                        </td>
                        <td><?= htmlspecialchars($r['contact'] ?? '—') ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="edit.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Edit">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <a href="../../modules/certificates/issue.php?resident_id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-warning" data-bs-toggle="tooltip" title="Issue Certificate">
                                    <i class="bi bi-file-earmark-plus"></i>
                                </a>
                                <a href="delete.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger"
                                   data-confirm="Delete <?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?>? This cannot be undone."
                                   data-bs-toggle="tooltip" title="Delete">
                                    <i class="bi bi-trash-fill"></i>
                                </a>
                            </div>
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
