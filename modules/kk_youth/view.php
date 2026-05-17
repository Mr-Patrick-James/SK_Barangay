<?php
require_once __DIR__ . '/../../config/auth.php';
requireRole(['Admin', 'SK Official']);
require_once __DIR__ . '/../../config/database.php';
$pageTitle = 'Youth Profile';
$rootPath  = '../../';

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("
    SELECT *,
    CAST((julianday('now') - julianday(birthdate)) / 365.25 AS INTEGER) AS current_age
    FROM kk_youth WHERE id = ?
");
$stmt->execute([$id]);
$y = $stmt->fetch();

if (!$y) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Youth profile not found.'];
    header('Location: index.php');
    exit;
}

// Resolve legacy field names for existing records
$homeAddress         = $y['home_address']           ?: ($y['address'] ?? '—');
$eduAttainment       = $y['educational_attainment']  ?: ($y['educational_status'] ?? '—');
$workStatus          = $y['work_status']              ?: ($y['employment_status'] ?? '—');
$registeredSKVoter   = $y['registered_sk_voter']     ?: ($y['sk_voter'] ?? 'No');
$ageGroup            = $y['youth_age_group']          ?: '—';

// Age group color
$agColor = 'secondary';
if (strpos($ageGroup, 'Child') !== false)  $agColor = 'info';
elseif (strpos($ageGroup, 'Core') !== false)  $agColor = 'warning';
elseif (strpos($ageGroup, 'Young') !== false) $agColor = 'success';

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h4><i class="bi bi-person-badge me-2"></i>KK Youth Profile</h4>
    <div class="d-flex gap-2">
        <a href="edit.php?id=<?= $y['id'] ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-pencil me-1"></i>Edit Profile
        </a>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back to List
        </a>
    </div>
</div>

<div class="row g-4">

    <!-- ── Profile Summary Card ── -->
    <div class="col-lg-4">
        <div class="card content-card text-center">
            <div class="card-body py-4">
                <!-- Avatar -->
                <div class="mx-auto mb-3 d-flex align-items-center justify-content-center"
                     style="width:90px;height:90px;border-radius:50%;
                            background:var(--blue-light);
                            border:3px solid var(--blue)">
                    <i class="bi bi-person-fill fs-1 text-primary"></i>
                </div>

                <h5 class="fw-bold mb-0">
                    <?= htmlspecialchars(
                        $y['first_name'] . ' '
                        . ($y['middle_name'] ? $y['middle_name'] . ' ' : '')
                        . $y['last_name']
                        . ($y['suffix'] ? ', ' . $y['suffix'] : '')
                    ) ?>
                </h5>
                <p class="text-muted small mb-2"><?= htmlspecialchars($homeAddress) ?></p>

                <!-- Age Group badge -->
                <span class="badge bg-<?= $agColor ?> text-dark px-3 py-2 mb-1 d-block fs-6">
                    <?= htmlspecialchars($ageGroup) ?>
                </span>

                <!-- Youth Classification badge -->
                <?php if (!empty($y['youth_classification'])): ?>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 d-block mb-2">
                    <?= htmlspecialchars($y['youth_classification']) ?>
                </span>
                <?php endif; ?>

                <!-- SK Voter -->
                <?php if ($registeredSKVoter === 'Yes'): ?>
                <span class="badge bg-success px-3 py-2 d-block mb-2">
                    <i class="bi bi-check-circle-fill me-1"></i>SK Registered Voter
                </span>
                <?php else: ?>
                <span class="badge bg-secondary px-3 py-2 d-block mb-2">
                    <i class="bi bi-x-circle me-1"></i>Not an SK Voter
                </span>
                <?php endif; ?>

                <hr>

                <div class="row text-center g-2">
                    <div class="col-4">
                        <div class="fw-bold fs-4 text-primary"><?= $y['current_age'] ?></div>
                        <small class="text-muted">Age</small>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold text-dark"><?= htmlspecialchars($y['gender']) ?></div>
                        <small class="text-muted">Sex</small>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold text-dark" style="font-size:0.8rem"><?= htmlspecialchars($y['civil_status'] ?? '—') ?></div>
                        <small class="text-muted">Civil Status</small>
                    </div>
                </div>

                <hr>
                <div class="text-start small">
                    <?php if ($y['contact']): ?>
                    <div class="mb-1"><i class="bi bi-telephone me-2 text-primary"></i><?= htmlspecialchars($y['contact']) ?></div>
                    <?php endif; ?>
                    <?php if ($y['email']): ?>
                    <div class="mb-1"><i class="bi bi-envelope me-2 text-primary"></i><?= htmlspecialchars($y['email']) ?></div>
                    <?php endif; ?>
                    <div><i class="bi bi-calendar3 me-2 text-primary"></i>Born <?= date('F d, Y', strtotime($y['birthdate'])) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Detail Cards ── -->
    <div class="col-lg-8">

        <!-- I. Profile -->
        <div class="card content-card mb-3">
            <div class="card-header bg-primary text-white"><i class="bi bi-person-fill me-2"></i>I. Profile</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-8">
                        <small class="text-muted d-block">Full Name</small>
                        <span class="fw-semibold">
                            <?= htmlspecialchars(
                                $y['last_name'] . ', '
                                . $y['first_name'] . ' '
                                . ($y['middle_name'] ?? '')
                                . ($y['suffix'] ? ' ' . $y['suffix'] : '')
                            ) ?>
                        </span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Birthday</small>
                        <span class="fw-semibold"><?= date('F d, Y', strtotime($y['birthdate'])) ?></span>
                    </div>
                    <div class="col-sm-2">
                        <small class="text-muted d-block">Region</small>
                        <span class="fw-semibold"><?= htmlspecialchars($y['region'] ?? '—') ?></span>
                    </div>
                    <div class="col-sm-3">
                        <small class="text-muted d-block">Province</small>
                        <span class="fw-semibold"><?= htmlspecialchars($y['province'] ?? '—') ?></span>
                    </div>
                    <div class="col-sm-3">
                        <small class="text-muted d-block">City / Municipality</small>
                        <span class="fw-semibold"><?= htmlspecialchars($y['city_municipality'] ?? '—') ?></span>
                    </div>
                    <div class="col-sm-2">
                        <small class="text-muted d-block">Barangay</small>
                        <span class="fw-semibold"><?= htmlspecialchars($y['barangay'] ?? '—') ?></span>
                    </div>
                    <div class="col-sm-2">
                        <small class="text-muted d-block">Purok</small>
                        <span class="fw-semibold"><?= htmlspecialchars($y['purok'] ?? '—') ?></span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Home Address</small>
                        <span class="fw-semibold"><?= htmlspecialchars($homeAddress) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- II. Demographic Characteristics -->
        <div class="card content-card mb-3">
            <div class="card-header bg-success text-white"><i class="bi bi-bar-chart-fill me-2"></i>II. Demographic Characteristics</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Civil Status</small>
                        <span class="fw-semibold"><?= htmlspecialchars($y['civil_status'] ?? '—') ?></span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Youth Age Group</small>
                        <span class="badge bg-<?= $agColor ?>-subtle text-<?= $agColor ?> border border-<?= $agColor ?>-subtle">
                            <?= htmlspecialchars($ageGroup) ?>
                        </span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Youth Classification</small>
                        <span class="fw-semibold"><?= htmlspecialchars($y['youth_classification'] ?? '—') ?></span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Educational Attainment</small>
                        <span class="fw-semibold"><?= htmlspecialchars($eduAttainment) ?></span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">School / University</small>
                        <span class="fw-semibold"><?= htmlspecialchars($y['school_name'] ?: '—') ?></span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Work Status</small>
                        <span class="fw-semibold"><?= htmlspecialchars($workStatus) ?></span>
                    </div>
                    <?php if (!empty($y['occupation'])): ?>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Occupation / Course</small>
                        <span class="fw-semibold"><?= htmlspecialchars($y['occupation']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Voter & KK Participation -->
        <div class="card content-card mb-3">
            <div class="card-header"><i class="bi bi-ballot-fill me-2 text-primary"></i>Voter & KK Assembly Participation</div>
            <div class="card-body">
                <div class="row g-3">
                    <?php
                    $yesClass = 'badge bg-success-subtle text-success border border-success-subtle';
                    $noClass  = 'badge bg-secondary-subtle text-secondary';
                    function yesno($val, $yc, $nc) { return $val === 'Yes' ? "<span class=\"$yc\"><i class=\"bi bi-check-circle-fill me-1\"></i>Yes</span>" : "<span class=\"$nc\">No</span>"; }
                    ?>
                    <div class="col-sm-3">
                        <small class="text-muted d-block">Registered SK Voter</small>
                        <?= yesno($registeredSKVoter, $yesClass, $noClass) ?>
                    </div>
                    <div class="col-sm-3">
                        <small class="text-muted d-block">Voted Last SK Election</small>
                        <?= yesno($y['voted_last_sk_election'] ?? 'No', $yesClass, $noClass) ?>
                    </div>
                    <div class="col-sm-3">
                        <small class="text-muted d-block">Registered National Voter</small>
                        <?= yesno($y['registered_national_voter'] ?? 'No', $yesClass, $noClass) ?>
                    </div>
                    <div class="col-sm-3">
                        <small class="text-muted d-block">Attended KK Assembly</small>
                        <?= yesno($y['attended_kk_assembly'] ?? 'No', $yesClass, $noClass) ?>
                    </div>
                    <?php if (!empty($y['kk_assembly_times'])): ?>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Times Attended</small>
                        <span class="fw-semibold"><?= htmlspecialchars($y['kk_assembly_times']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($y['kk_assembly_no_reason'])): ?>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Reason for Not Attending</small>
                        <span class="fw-semibold"><?= htmlspecialchars($y['kk_assembly_no_reason']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Skills & Interests -->
        <?php if (!empty($y['skills']) || !empty($y['interests'])): ?>
        <div class="card content-card mb-3">
            <div class="card-header"><i class="bi bi-stars me-2 text-success"></i>Skills & Interests</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Skills</small>
                        <?php if ($y['skills']): ?>
                            <?php foreach (explode(',', $y['skills']) as $skill): ?>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1 mb-1">
                                <?= htmlspecialchars(trim($skill)) ?>
                            </span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Interests / Hobbies</small>
                        <?php if ($y['interests']): ?>
                            <?php foreach (explode(',', $y['interests']) as $interest): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle me-1 mb-1">
                                <?= htmlspecialchars(trim($interest)) ?>
                            </span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Emergency Contact -->
        <?php if (!empty($y['emergency_contact_name']) || !empty($y['emergency_contact_number'])): ?>
        <div class="card content-card">
            <div class="card-header"><i class="bi bi-telephone-fill me-2 text-danger"></i>Emergency Contact</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Contact Person</small>
                        <span class="fw-semibold"><?= htmlspecialchars($y['emergency_contact_name'] ?: '—') ?></span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Contact Number</small>
                        <span class="fw-semibold"><?= htmlspecialchars($y['emergency_contact_number'] ?: '—') ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /.col-lg-8 -->
</div>

<!-- Record metadata -->
<div class="text-muted small mt-3">
    <i class="bi bi-clock me-1"></i>
    Profile created: <?= date('F d, Y h:i A', strtotime($y['created_at'])) ?>
    &nbsp;|&nbsp;
    Last updated: <?= date('F d, Y h:i A', strtotime($y['updated_at'])) ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
