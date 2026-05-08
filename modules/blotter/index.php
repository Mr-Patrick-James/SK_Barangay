<?php
require_once __DIR__ . '/../../config/database.php';
$pageTitle = 'Blotter Records';
$rootPath = '../../';

$db = getDB();

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$nature = trim($_GET['nature'] ?? '');

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(complainant LIKE ? OR respondent LIKE ? OR nature LIKE ? OR details LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like, $like]);
}
if ($status !== '') {
    $where[] = "status = ?";
    $params[] = $status;
}
if ($nature !== '') {
    $where[] = "nature LIKE ?";
    $params[] = "%$nature%";
}

$sql = "SELECT * FROM blotter"
     . ($where ? " WHERE " . implode(" AND ", $where) : "")
     . " ORDER BY created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

$statusCounts = $db->query("SELECT status, COUNT(*) as cnt FROM blotter GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

if (session_status() === PHP_SESSION_NONE) session_start();
$flash = null;
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

include __DIR__ . '/../../includes/header.php';
?>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
    <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($flash['msg']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="page-header">
    <h4><i class="bi bi-journal-text me-2"></i>Blotter Records</h4>
    <a href="add.php" class="btn btn-danger">
        <i class="bi bi-journal-plus me-1"></i> File Blotter
    </a>
</div>

<!-- Status Summary -->
<div class="row g-2 mb-3">
    <?php
    $statuses = [
        'Pending'   => ['bg-warning-subtle text-warning',   'bi-clock-fill'],
        'Ongoing'   => ['bg-primary-subtle text-primary',   'bi-arrow-repeat'],
        'Resolved'  => ['bg-success-subtle text-success',   'bi-check-circle-fill'],
        'Dismissed' => ['bg-secondary-subtle text-secondary','bi-x-circle-fill'],
    ];
    foreach ($statuses as $s => [$cls, $icon]):
    ?>
    <div class="col-6 col-md-3">
        <a href="?status=<?= $s ?>" class="text-decoration-none">
            <div class="card content-card <?= $status===$s ? 'border-primary' : '' ?>">
                <div class="card-body py-2 d-flex align-items-center gap-2">
                    <i class="bi <?= $icon ?> <?= $cls ?> fs-5"></i>
                    <div>
                        <div class="fw-bold"><?= $statusCounts[$s] ?? 0 ?></div>
                        <div class="small text-muted"><?= $s ?></div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filters -->
<div class="card content-card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <div class="search-wrapper">
                    <i class="bi bi-search"></i>
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="Search complainant, respondent, nature..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <?php foreach (array_keys($statuses) as $s): ?>
                    <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <a href="index.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-circle"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card content-card">
    <div class="card-header">
        <i class="bi bi-list-ul me-1"></i> <?= count($records) ?> record(s) found
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Entry #</th>
                        <th>Date Filed</th>
                        <th>Incident Date</th>
                        <th>Complainant</th>
                        <th>Respondent</th>
                        <th>Nature of Incident</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($records)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>No blotter records found.
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($records as $b): ?>
                    <?php
                        $statusClass = match($b['status']) {
                            'Resolved'  => 'bg-success-subtle text-success',
                            'Ongoing'   => 'bg-primary-subtle text-primary',
                            'Dismissed' => 'bg-secondary-subtle text-secondary',
                            default     => 'bg-warning-subtle text-warning',
                        };
                    ?>
                    <tr>
                        <td class="fw-bold text-muted">#<?= str_pad($b['id'], 4, '0', STR_PAD_LEFT) ?></td>
                        <td><?= date('M d, Y', strtotime($b['created_at'])) ?></td>
                        <td><?= $b['incident_date'] ? date('M d, Y', strtotime($b['incident_date'])) : '—' ?></td>
                        <td class="fw-semibold"><?= htmlspecialchars($b['complainant']) ?></td>
                        <td><?= htmlspecialchars($b['respondent']) ?></td>
                        <td class="text-truncate" style="max-width:160px" title="<?= htmlspecialchars($b['nature']) ?>">
                            <?= htmlspecialchars($b['nature']) ?>
                        </td>
                        <td><span class="badge <?= $statusClass ?>"><?= htmlspecialchars($b['status']) ?></span></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="view.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="View Details">
                                    <i class="bi bi-eye-fill"></i>
                                </a>
                                <a href="add.php?edit=<?= $b['id'] ?>" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Edit">
                                    <i class="bi bi-pencil-fill"></i>
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
