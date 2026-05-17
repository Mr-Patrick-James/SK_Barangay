<?php
require_once __DIR__ . '/config/auth.php';
requireRole('Admin');
require_once __DIR__ . '/config/database.php';
$pageTitle = 'Dashboard';
$rootPath = '';

$db = getDB();

$totalResidents  = $db->query("SELECT COUNT(*) FROM residents")->fetchColumn();
$totalOfficials  = $db->query("SELECT COUNT(*) FROM officials WHERE status='Active'")->fetchColumn();
$totalCerts      = $db->query("SELECT COUNT(*) FROM certificates")->fetchColumn();
$certsThisMonth  = $db->query("SELECT COUNT(*) FROM certificates WHERE strftime('%Y-%m', issued_at) = strftime('%Y-%m', 'now')")->fetchColumn();
$maleCount       = $db->query("SELECT COUNT(*) FROM residents WHERE gender='Male'")->fetchColumn();
$femaleCount     = $db->query("SELECT COUNT(*) FROM residents WHERE gender='Female'")->fetchColumn();
$totalKK         = $db->query("SELECT COUNT(*) FROM kk_youth")->fetchColumn();
$skVoters        = $db->query("SELECT COUNT(*) FROM kk_youth WHERE sk_voter='Yes'")->fetchColumn();
$recentResidents = $db->query("SELECT * FROM residents ORDER BY created_at DESC LIMIT 5")->fetchAll();
$recentCerts     = $db->query("SELECT c.*, r.first_name, r.last_name FROM certificates c LEFT JOIN residents r ON c.resident_id = r.id ORDER BY c.issued_at DESC LIMIT 5")->fetchAll();
$certBreakdown = $db->query("SELECT cert_type, COUNT(*) as cnt FROM certificates GROUP BY cert_type")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-4">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#dbeafe">
                    <i class="bi bi-people-fill text-primary"></i>
                </div>
                <div>
                    <div class="stat-number text-primary"><?= number_format($totalResidents) ?></div>
                    <div class="text-muted small">Total Residents</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#d1fae5">
                    <i class="bi bi-person-badge-fill text-success"></i>
                </div>
                <div>
                    <div class="stat-number text-success"><?= number_format($totalOfficials) ?></div>
                    <div class="text-muted small">Active Officials</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#fef3c7">
                    <i class="bi bi-file-earmark-text-fill text-warning"></i>
                </div>
                <div>
                    <div class="stat-number text-warning"><?= number_format($totalCerts) ?></div>
                    <div class="text-muted small">Certificates Issued</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Secondary Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card content-card h-100">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-bar-chart-fill text-primary"></i> Population Overview
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted small">Male Residents</span>
                    <span class="fw-bold text-primary"><?= $maleCount ?></span>
                </div>
                <div class="progress mb-3" style="height:8px">
                    <div class="progress-bar bg-primary" style="width:<?= $totalResidents > 0 ? round($maleCount/$totalResidents*100) : 0 ?>%"></div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted small">Female Residents</span>
                    <span class="fw-bold text-danger"><?= $femaleCount ?></span>
                </div>
                <div class="progress mb-3" style="height:8px">
                    <div class="progress-bar bg-danger" style="width:<?= $totalResidents > 0 ? round($femaleCount/$totalResidents*100) : 0 ?>%"></div>
                </div>
                <hr>
                <div class="d-flex justify-content-between">
                    <span class="text-muted small">Certs This Month</span>
                    <span class="badge bg-warning text-dark"><?= $certsThisMonth ?></span>
                </div>
                <div class="d-flex justify-content-between mt-2">
                    <span class="text-muted small">KK Youth Members</span>
                    <span class="badge bg-primary"><?= $totalKK ?></span>
                </div>
                <div class="d-flex justify-content-between mt-2">
                    <span class="text-muted small">SK Registered Voters</span>
                    <span class="badge bg-warning text-dark"><?= $skVoters ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card content-card h-100">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-check-fill text-warning"></i> Certificate Breakdown
            </div>
            <div class="card-body">
                <?php if (empty($certBreakdown)): ?>
                    <p class="text-muted text-center py-3">No certificates issued yet.</p>
                <?php else: ?>
                    <?php foreach ($certBreakdown as $cb): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small"><?= htmlspecialchars($cb['cert_type']) ?></span>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= $cb['cnt'] ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <hr>
                <a href="modules/certificates/issue.php" class="btn btn-sm btn-warning w-100">
                    <i class="bi bi-plus-circle me-1"></i> Issue Certificate
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card content-card h-100">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-lightning-fill text-success"></i> Quick Actions
            </div>
            <div class="card-body d-flex flex-column gap-2">
                <a href="modules/residents/add.php" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-person-plus me-2"></i>Add New Resident
                </a>
                <a href="modules/certificates/issue.php" class="btn btn-outline-warning btn-sm">
                    <i class="bi bi-file-earmark-plus me-2"></i>Issue Certificate
                </a>
                <a href="modules/officials/add.php" class="btn btn-outline-success btn-sm">
                    <i class="bi bi-person-badge me-2"></i>Add Official
                </a>
                <a href="modules/kk_youth/add.php" class="btn btn-outline-warning btn-sm">
                    <i class="bi bi-stars me-2"></i>Add KK Youth Profile
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Recent Records -->
<div class="row g-3">
    <div class="col-12">
        <div class="card content-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-people me-2 text-primary"></i>Recent Residents</span>
                <a href="modules/residents/index.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Name</th><th>Gender</th><th>Added</th></tr></thead>
                        <tbody>
                        <?php if (empty($recentResidents)): ?>
                            <tr><td colspan="3" class="text-center text-muted py-3">No residents yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentResidents as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></td>
                                <td><span class="badge <?= $r['gender']==='Male' ? 'bg-primary-subtle text-primary' : 'bg-danger-subtle text-danger' ?>"><?= htmlspecialchars($r['gender'] ?? '—') ?></span></td>
                                <td class="text-muted"><?= date('M d', strtotime($r['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
