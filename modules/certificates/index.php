<?php
require_once __DIR__ . '/../../config/database.php';
$pageTitle = 'Certificates';
$rootPath = '../../';

$db = getDB();

$search   = trim($_GET['search'] ?? '');
$type     = $_GET['type'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo   = $_GET['date_to'] ?? '';

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(r.first_name LIKE ? OR r.last_name LIKE ? OR c.or_number LIKE ? OR c.purpose LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like, $like]);
}
if ($type !== '') {
    $where[] = "c.cert_type = ?";
    $params[] = $type;
}
if ($dateFrom !== '') {
    $where[] = "DATE(c.issued_at) >= ?";
    $params[] = $dateFrom;
}
if ($dateTo !== '') {
    $where[] = "DATE(c.issued_at) <= ?";
    $params[] = $dateTo;
}

$sql = "SELECT c.*, r.first_name, r.last_name, r.address
        FROM certificates c
        LEFT JOIN residents r ON c.resident_id = r.id"
     . ($where ? " WHERE " . implode(" AND ", $where) : "")
     . " ORDER BY c.issued_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$certs = $stmt->fetchAll();

$certTypes = ['Barangay Clearance', 'Certificate of Residency', 'Certificate of Indigency'];

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
    <h4><i class="bi bi-file-earmark-text-fill me-2"></i>Certificates</h4>
    <a href="issue.php" class="btn btn-warning">
        <i class="bi bi-file-earmark-plus-fill me-1"></i> Issue Certificate
    </a>
</div>

<!-- Filters -->
<div class="card content-card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <div class="search-wrapper">
                    <i class="bi bi-search"></i>
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="Search name, OR#, purpose..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="type" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    <?php foreach ($certTypes as $ct): ?>
                    <option value="<?= $ct ?>" <?= $type===$ct?'selected':'' ?>><?= $ct ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($dateFrom) ?>" placeholder="From">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($dateTo) ?>" placeholder="To">
            </div>
            <div class="col-md-2 d-flex gap-2">
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
        <i class="bi bi-list-ul me-1"></i> <?= count($certs) ?> certificate(s) found
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Resident</th>
                        <th>Certificate Type</th>
                        <th>Purpose</th>
                        <th>OR Number</th>
                        <th>Amount</th>
                        <th>Issued By</th>
                        <th>Date Issued</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($certs)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>No certificates found.
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($certs as $i => $c): ?>
                    <?php
                        $typeColor = match($c['cert_type']) {
                            'Barangay Clearance'       => 'bg-primary-subtle text-primary',
                            'Certificate of Residency' => 'bg-success-subtle text-success',
                            'Certificate of Indigency' => 'bg-warning-subtle text-warning',
                            default                    => 'bg-secondary-subtle text-secondary',
                        };
                    ?>
                    <tr>
                        <td class="text-muted"><?= $i + 1 ?></td>
                        <td>
                            <div class="fw-semibold">
                                <?= $c['first_name'] ? htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) : '<em class="text-muted">Walk-in</em>' ?>
                            </div>
                            <small class="text-muted"><?= htmlspecialchars($c['address'] ?? '') ?></small>
                        </td>
                        <td><span class="badge <?= $typeColor ?>"><?= htmlspecialchars($c['cert_type']) ?></span></td>
                        <td class="text-truncate" style="max-width:140px"><?= htmlspecialchars($c['purpose'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($c['or_number'] ?? '—') ?></td>
                        <td><?= $c['amount'] > 0 ? '₱' . number_format($c['amount'], 2) : '<span class="text-muted">Free</span>' ?></td>
                        <td><?= htmlspecialchars($c['issued_by'] ?? '—') ?></td>
                        <td><?= date('M d, Y', strtotime($c['issued_at'])) ?></td>
                        <td>
                            <a href="print.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary" target="_blank" data-bs-toggle="tooltip" title="Print">
                                <i class="bi bi-printer-fill"></i>
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
