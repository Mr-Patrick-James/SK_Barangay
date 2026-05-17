<?php
require_once __DIR__ . '/../../config/auth.php';
requireRole(['Admin', 'SK Official']);
require_once __DIR__ . '/../../config/database.php';
$pageTitle = 'KK Youth Profiling';
$rootPath  = '../../';

$db = getDB();

if (session_status() === PHP_SESSION_NONE) session_start();
$flash = null;
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

$search         = trim($_GET['search'] ?? '');
$classification = $_GET['classification'] ?? '';
$education      = $_GET['education'] ?? '';
$work           = $_GET['work'] ?? '';
$ageGroup       = $_GET['age_group'] ?? '';

$where  = ["is_archived = 0"];
$params = [];

if ($search !== '') {
    $where[]  = "(first_name LIKE ? OR last_name LIKE ? OR middle_name LIKE ? OR home_address LIKE ? OR purok LIKE ? OR barangay LIKE ?)";
    $like     = "%$search%";
    $params   = array_merge($params, [$like, $like, $like, $like, $like, $like]);
}
if ($classification !== '') { $where[] = "youth_classification = ?"; $params[] = $classification; }
if ($education !== '')      { $where[] = "educational_attainment = ?"; $params[] = $education; }
if ($work !== '')           { $where[] = "work_status = ?"; $params[] = $work; }
if ($ageGroup !== '')       { $where[] = "youth_age_group = ?"; $params[] = $ageGroup; }

$sql = "SELECT *,
        CAST((julianday('now') - julianday(birthdate)) / 365.25 AS INTEGER) AS current_age
        FROM kk_youth"
        . ($where ? " WHERE " . implode(" AND ", $where) : "")
        . " ORDER BY last_name, first_name";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$youth = $stmt->fetchAll();

// Statistics
$totalYouth  = $db->query("SELECT COUNT(*) FROM kk_youth")->fetchColumn();
$childYouth  = $db->query("SELECT COUNT(*) FROM kk_youth WHERE youth_age_group='Child Youth (15-17 yrs old)'")->fetchColumn();
$coreYouth   = $db->query("SELECT COUNT(*) FROM kk_youth WHERE youth_age_group='Core Youth (18-24 yrs old)'")->fetchColumn();
$youngAdult  = $db->query("SELECT COUNT(*) FROM kk_youth WHERE youth_age_group='Young Adult (25-30 yrs old)'")->fetchColumn();
$skVoters    = $db->query("SELECT COUNT(*) FROM kk_youth WHERE registered_sk_voter='Yes'")->fetchColumn();
$kkAttended  = $db->query("SELECT COUNT(*) FROM kk_youth WHERE attended_kk_assembly='Yes'")->fetchColumn();

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
    <div class="col-sm-6 col-xl-2">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#dbeafe">
                    <i class="bi bi-people-fill text-primary"></i>
                </div>
                <div>
                    <div class="stat-number text-primary"><?= number_format($totalYouth) ?></div>
                    <div class="text-muted small">Total Members</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-2">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#e0f2fe">
                    <i class="bi bi-person-fill text-info"></i>
                </div>
                <div>
                    <div class="stat-number text-info"><?= number_format($childYouth) ?></div>
                    <div class="text-muted small">Child Youth (15–17)</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-2">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#fef3c7">
                    <i class="bi bi-star-fill text-warning"></i>
                </div>
                <div>
                    <div class="stat-number text-warning"><?= number_format($coreYouth) ?></div>
                    <div class="text-muted small">Core Youth (18–24)</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-2">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#d1fae5">
                    <i class="bi bi-person-fill text-success"></i>
                </div>
                <div>
                    <div class="stat-number text-success"><?= number_format($youngAdult) ?></div>
                    <div class="text-muted small">Young Adult (25–30)</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-2">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#ede9fe">
                    <i class="bi bi-ballot-fill text-purple" style="color:#7c3aed"></i>
                </div>
                <div>
                    <div class="stat-number" style="color:#7c3aed"><?= number_format($skVoters) ?></div>
                    <div class="text-muted small">SK Registered Voters</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-2">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#fee2e2">
                    <i class="bi bi-calendar-check-fill text-danger"></i>
                </div>
                <div>
                    <div class="stat-number text-danger"><?= number_format($kkAttended) ?></div>
                    <div class="text-muted small">Attended KK Assembly</div>
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
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Search</label>
                <div class="search-wrapper">
                    <i class="bi bi-search"></i>
                    <input type="text" name="search" class="form-control"
                           placeholder="Name, address, purok, barangay…"
                           value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Age Group</label>
                <select name="age_group" class="form-select">
                    <option value="">All Age Groups</option>
                    <option value="Child Youth (15-17 yrs old)"  <?= $ageGroup === 'Child Youth (15-17 yrs old)'  ? 'selected' : '' ?>>Child Youth (15–17)</option>
                    <option value="Core Youth (18-24 yrs old)"   <?= $ageGroup === 'Core Youth (18-24 yrs old)'   ? 'selected' : '' ?>>Core Youth (18–24)</option>
                    <option value="Young Adult (25-30 yrs old)"  <?= $ageGroup === 'Young Adult (25-30 yrs old)'  ? 'selected' : '' ?>>Young Adult (25–30)</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Youth Classification</label>
                <select name="classification" class="form-select">
                    <option value="">All Classifications</option>
                    <?php foreach (['In School Youth','Out of School Youth','Working Youth','Youth w/ Specific Needs','Indigenous People','Children in Conflict w/ Law','Person w/ Disability'] as $yc): ?>
                    <option value="<?= $yc ?>" <?= $classification === $yc ? 'selected' : '' ?>><?= $yc ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Educational Attainment</label>
                <select name="education" class="form-select">
                    <option value="">All Education</option>
                    <?php foreach (['Elementary Level','Elementary Graduate','High School Level','High School Graduate','Vocational Graduate','College Level','College Graduate','Masters Level','Masters Graduate','Doctorate Level','Doctorate Graduate'] as $edu): ?>
                    <option value="<?= $edu ?>" <?= $education === $edu ? 'selected' : '' ?>><?= $edu ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Work Status</label>
                <select name="work" class="form-select">
                    <option value="">All Work Status</option>
                    <?php foreach (['Employed','Unemployed','Self-Employed','Currently looking for a job','Not interested looking for a job'] as $ws): ?>
                    <option value="<?= $ws ?>" <?= $work === $ws ? 'selected' : '' ?>><?= $ws ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-primary w-100" title="Filter">
                    <i class="bi bi-funnel-fill"></i>
                </button>
                <?php if ($search || $classification || $education || $work || $ageGroup): ?>
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
        <style>
            .youth-table th {
                font-size: 11px !important;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                white-space: nowrap;
            }
            .youth-table td {
                font-size: 12px !important;
                vertical-align: middle;
            }
            .youth-table .badge {
                font-size: 11px !important;
            }
        </style>
        <div class="table-responsive">
            <table class="table table-hover mb-0 youth-table">
                <thead class="text-muted">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Age / Birthday</th>
                        <th>Age Group</th>
                        <th>Youth Classification</th>
                        <th>Educational Attainment</th>
                        <th>Work Status</th>
                        <th>SK Voter</th>
                        <th>Voted Last Election</th>
                        <th>Attended KK Assembly</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($youth)): ?>
                    <tr>
                        <td colspan="11" class="text-center text-muted py-5">
                            <i class="bi bi-people fs-2 d-block mb-2 opacity-25"></i>
                            No youth profiles found.
                            <?php if ($search || $classification || $education || $work || $ageGroup): ?>
                                <a href="index.php" class="d-block mt-1 small">Clear filters</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($youth as $i => $y): ?>
                    <?php
                        $ag = $y['youth_age_group'] ?? '';
                        $agColor = 'secondary';
                        if (strpos($ag, 'Child') !== false)  $agColor = 'info';
                        elseif (strpos($ag, 'Core') !== false) $agColor = 'warning';
                        elseif (strpos($ag, 'Young') !== false) $agColor = 'success';
                    ?>
                    <tr>
                        <td class="text-muted small"><?= $i + 1 ?></td>
                        <td>
                            <div class="fw-semibold">
                                <?= htmlspecialchars($y['last_name'] . ', ' . $y['first_name'] . ($y['middle_name'] ? ' ' . $y['middle_name'][0] . '.' : '')) ?>
                                <?php if ($y['suffix']): ?>
                                    <small class="text-muted"><?= htmlspecialchars($y['suffix']) ?></small>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted">
                                <?php
                                $locParts = array_filter([$y['purok'] ?? '', $y['barangay'] ?? '', $y['city_municipality'] ?? '']);
                                $locStr   = $locParts ? trim(implode(', ', $locParts)) : ($y['home_address'] ?? '—');
                                echo htmlspecialchars($locStr);
                                ?>
                            </small>
                        </td>
                        <td>
                            <span class="fw-semibold"><?= $y['current_age'] ?></span>
                            <small class="text-muted d-block"><?= date('M d, Y', strtotime($y['birthdate'])) ?></small>
                        </td>
                        <td>
                            <span class="badge bg-<?= $agColor ?>-subtle text-<?= $agColor ?> border border-<?= $agColor ?>-subtle" style="white-space:normal;max-width:120px">
                                <?= htmlspecialchars($ag ?: '—') ?>
                            </span>
                        </td>
                        <td class="small"><?= htmlspecialchars($y['youth_classification'] ?? '—') ?></td>
                        <td class="small"><?= htmlspecialchars($y['educational_attainment'] ?? $y['educational_status'] ?? '—') ?></td>
                        <td class="small"><?= htmlspecialchars($y['work_status'] ?? $y['employment_status'] ?? '—') ?></td>
                        <td>
                            <?php $sv = $y['registered_sk_voter'] ?? $y['sk_voter'] ?? 'No'; ?>
                            <?php if ($sv === 'Yes'): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i>Yes</span>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary px-2 py-1">No</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php $vl = $y['voted_last_sk_election'] ?? 'No'; ?>
                            <span class="badge px-2 py-1 <?= $vl === 'Yes' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?>"><?= $vl ?></span>
                        </td>
                        <td>
                            <?php $kka = $y['attended_kk_assembly'] ?? 'No'; ?>
                            <span class="badge px-2 py-1 <?= $kka === 'Yes' ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary' ?>"><?= $kka ?></span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="view.php?id=<?= $y['id'] ?>" class="btn btn-outline-primary" title="View Profile">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="edit.php?id=<?= $y['id'] ?>" class="btn btn-outline-secondary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="archive.php?id=<?= $y['id'] ?>"
                                   class="btn btn-outline-warning"
                                   title="Archive"
                                   onclick="return confirm('Archive profile of <?= htmlspecialchars(addslashes($y['first_name'] . ' ' . $y['last_name'])) ?>?')">
                                    <i class="bi bi-archive"></i>
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
