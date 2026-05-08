<?php
require_once __DIR__ . '/../../config/database.php';
$pageTitle = 'KK Youth Profiling';
$rootPath = '../../';

$db = getDB();

// Read flash before session is touched by header.php
if (session_status() === PHP_SESSION_NONE) session_start();
$flash = null;
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

$search         = trim($_GET['search'] ?? '');
$classification = $_GET['classification'] ?? '';
$education      = $_GET['education'] ?? '';
$employment     = $_GET['employment'] ?? '';

$where  = [];
$params = [];

if ($search !== '') {
    $where[]  = "(first_name LIKE ? OR last_name LIKE ? OR middle_name LIKE ? OR address LIKE ?)";
    $like     = "%$search%";
    $params   = array_merge($params, [$like, $like, $like, $like]);
}
if ($classification !== '') {
    $where[]  = "youth_classification = ?";
    $params[] = $classification;
}
if ($education !== '') {
    $where[]  = "educational_status = ?";
    $params[] = $education;
}
if ($employment !== '') {
    $where[]  = "employment_status = ?";
    $params[] = $employment;
}

$sql = "SELECT *,
        CAST((julianday('now') - julianday(birthdate)) / 365.25 AS INTEGER) AS current_age
        FROM kk_youth"
        . ($where ? " WHERE " . implode(" AND ", $where) : "")
        . " ORDER BY last_name, first_name";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$youth = $stmt->fetchAll();

// Statistics
$totalYouth   = $db->query("SELECT COUNT(*) FROM kk_youth")->fetchColumn();
$coreYouth    = $db->query("SELECT COUNT(*) FROM kk_youth WHERE youth_classification='Core Youth (15-24)'")->fetchColumn();
$nonCoreYouth = $db->query("SELECT COUNT(*) FROM kk_youth WHERE youth_classification='Non-Core Youth (25-30)'")->fetchColumn();
$skVoters     = $db->query("SELECT COUNT(*) FROM kk_youth WHERE sk_voter='Yes'")->fetchColumn();
$inSchool     = $db->query("SELECT COUNT(*) FROM kk_youth WHERE educational_status LIKE '%In School%'")->fetchColumn();

include __DIR__ . '/../../includes/header.php';
?>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#dbeafe">
                    <i class="bi bi-people-fill text-primary"></i>
                </div>
                <div>
                    <div class="stat-number text-primary"><?= number_format($totalYouth) ?></div>
                    <div class="text-muted small">Total KK Members</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#fef3c7">
                    <i class="bi bi-star-fill text-warning"></i>
                </div>
                <div>
                    <div class="stat-number text-warning"><?= number_format($coreYouth) ?></div>
                    <div class="text-muted small">Core Youth (15–24)</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#d1fae5">
                    <i class="bi bi-check-circle-fill text-success"></i>
                </div>
                <div>
                    <div class="stat-number text-success"><?= number_format($skVoters) ?></div>
                    <div class="text-muted small">SK Registered Voters</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#fee2e2">
                    <i class="bi bi-book-fill text-danger"></i>
                </div>
                <div>
                    <div class="stat-number text-danger"><?= number_format($inSchool) ?></div>
                    <div class="text-muted small">In School</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-header">
    <h4><i class="bi bi-people me-2"></i>KK Youth Profiling</h4>
    <a href="add.php" class="btn btn-primary">
        <i class="bi bi-person-plus-fill me-2"></i>Add Youth Profile
    </a>
</div>

<!-- Filters -->
<div class="card content-card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Search</label>
                <div class="search-wrapper">
                    <i class="bi bi-search"></i>
                    <input type="text" name="search" class="form-control"
                           placeholder="Search by name or address..."
                           value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Classification</label>
                <select name="classification" class="form-select">
                    <option value="">All Classifications</option>
                    <option value="Core Youth (15-24)"    <?= $classification === 'Core Youth (15-24)'    ? 'selected' : '' ?>>Core Youth (15–24)</option>
                    <option value="Non-Core Youth (25-30)" <?= $classification === 'Non-Core Youth (25-30)' ? 'selected' : '' ?>>Non-Core Youth (25–30)</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Education Status</label>
                <select name="education" class="form-select">
                    <option value="">All Education Status</option>
                    <option value="Elementary In School"       <?= $education === 'Elementary In School'       ? 'selected' : '' ?>>Elementary In School</option>
                    <option value="High School In School"      <?= $education === 'High School In School'      ? 'selected' : '' ?>>High School In School</option>
                    <option value="Senior High School In School" <?= $education === 'Senior High School In School' ? 'selected' : '' ?>>Senior High School In School</option>
                    <option value="College In School"          <?= $education === 'College In School'          ? 'selected' : '' ?>>College In School</option>
                    <option value="Vocational/Technical In School" <?= $education === 'Vocational/Technical In School' ? 'selected' : '' ?>>Vocational/Technical In School</option>
                    <option value="Out of School Youth (OSY)"  <?= $education === 'Out of School Youth (OSY)'  ? 'selected' : '' ?>>Out of School Youth (OSY)</option>
                    <option value="High School Graduate"       <?= $education === 'High School Graduate'       ? 'selected' : '' ?>>High School Graduate</option>
                    <option value="College Graduate"           <?= $education === 'College Graduate'           ? 'selected' : '' ?>>College Graduate</option>
                    <option value="Vocational/Technical Graduate" <?= $education === 'Vocational/Technical Graduate' ? 'selected' : '' ?>>Vocational/Technical Graduate</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Employment</label>
                <select name="employment" class="form-select">
                    <option value="">All Employment</option>
                    <option value="Student"       <?= $employment === 'Student'       ? 'selected' : '' ?>>Student</option>
                    <option value="Employed"      <?= $employment === 'Employed'      ? 'selected' : '' ?>>Employed</option>
                    <option value="Self-Employed" <?= $employment === 'Self-Employed' ? 'selected' : '' ?>>Self-Employed</option>
                    <option value="Unemployed"    <?= $employment === 'Unemployed'    ? 'selected' : '' ?>>Unemployed</option>
                    <option value="OFW"           <?= $employment === 'OFW'           ? 'selected' : '' ?>>OFW</option>
                </select>
            </div>
            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-primary w-100" title="Filter">
                    <i class="bi bi-funnel-fill"></i>
                </button>
                <?php if ($search || $classification || $education || $employment): ?>
                <a href="index.php" class="btn btn-outline-secondary" title="Clear">
                    <i class="bi bi-x-lg"></i>
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Youth List -->
<div class="card content-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list-ul me-2 text-primary"></i>Youth Records</span>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
            <?= count($youth) ?> result<?= count($youth) !== 1 ? 's' : '' ?>
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Age / Birthdate</th>
                        <th>Classification</th>
                        <th>Education</th>
                        <th>Employment</th>
                        <th>SK Voter</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($youth)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-people fs-2 d-block mb-2 opacity-25"></i>
                            No youth profiles found.
                            <?php if ($search || $classification || $education || $employment): ?>
                                <a href="index.php" class="d-block mt-1 small">Clear filters</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($youth as $i => $y): ?>
                    <tr>
                        <td class="text-muted small"><?= $i + 1 ?></td>
                        <td>
                            <div class="fw-semibold">
                                <?= htmlspecialchars($y['last_name'] . ', ' . $y['first_name'] . ($y['middle_name'] ? ' ' . $y['middle_name'][0] . '.' : '')) ?>
                                <?php if ($y['suffix']): ?>
                                    <small class="text-muted"><?= htmlspecialchars($y['suffix']) ?></small>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted"><?= htmlspecialchars($y['address'] ?? '—') ?></small>
                        </td>
                        <td>
                            <span class="fw-semibold"><?= $y['current_age'] ?></span>
                            <small class="text-muted d-block"><?= date('M d, Y', strtotime($y['birthdate'])) ?></small>
                        </td>
                        <td>
                            <?php $classColor = $y['youth_classification'] === 'Core Youth (15-24)' ? 'warning' : 'info'; ?>
                            <span class="badge bg-<?= $classColor ?>-subtle text-<?= $classColor ?> border border-<?= $classColor ?>-subtle">
                                <?= htmlspecialchars($y['youth_classification'] ?? '—') ?>
                            </span>
                        </td>
                        <td class="small"><?= htmlspecialchars($y['educational_status'] ?? '—') ?></td>
                        <td class="small"><?= htmlspecialchars($y['employment_status'] ?? '—') ?></td>
                        <td>
                            <?php if ($y['sk_voter'] === 'Yes'): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    <i class="bi bi-check-circle-fill me-1"></i>Yes
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary">No</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="view.php?id=<?= $y['id'] ?>" class="btn btn-outline-primary" title="View Profile">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="edit.php?id=<?= $y['id'] ?>" class="btn btn-outline-secondary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="delete.php?id=<?= $y['id'] ?>"
                                   class="btn btn-outline-danger"
                                   title="Delete"
                                   onclick="return confirm('Delete profile of <?= htmlspecialchars(addslashes($y['first_name'] . ' ' . $y['last_name'])) ?>?')">
                                    <i class="bi bi-trash"></i>
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
