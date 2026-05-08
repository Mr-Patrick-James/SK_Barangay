<?php
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

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h4><i class="bi bi-person-badge me-2"></i>Youth Profile</h4>
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
                <p class="text-muted small mb-3"><?= htmlspecialchars($y['address']) ?></p>

                <!-- Classification badge -->
                <?php $classColor = $y['youth_classification'] === 'Core Youth (15-24)' ? 'warning' : 'info'; ?>
                <span class="badge bg-<?= $classColor ?> text-dark px-3 py-2 mb-2 d-block fs-6">
                    <?= htmlspecialchars($y['youth_classification'] ?? '—') ?>
                </span>

                <!-- SK Voter badge -->
                <?php if ($y['sk_voter'] === 'Yes'): ?>
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
                        <small class="text-muted">Gender</small>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold text-dark" style="font-size:0.85rem"><?= htmlspecialchars($y['civil_status']) ?></div>
                        <small class="text-muted">Status</small>
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
                    <div><i class="bi bi-calendar3 me-2 text-primary"></i>
                        Born <?= date('F d, Y', strtotime($y['birthdate'])) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Detail Cards ── -->
    <div class="col-lg-8">

        <!-- Personal Info -->
        <div class="card content-card mb-3">
            <div class="card-header"><i class="bi bi-person-fill me-2 text-primary"></i>Personal Information</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Full Name</small>
                        <span class="fw-semibold">
                            <?= htmlspecialchars(
                                $y['first_name'] . ' '
                                . ($y['middle_name'] ? $y['middle_name'] . ' ' : '')
                                . $y['last_name']
                                . ($y['suffix'] ? ', ' . $y['suffix'] : '')
                            ) ?>
                        </span>
                    </div>
                    <div class="col-sm-3">
                        <small class="text-muted d-block">Birthdate</small>
                        <span class="fw-semibold"><?= date('F d, Y', strtotime($y['birthdate'])) ?></span>
                    </div>
                    <div class="col-sm-3">
                        <small class="text-muted d-block">Age</small>
                        <span class="fw-semibold"><?= $y['current_age'] ?> years old</span>
                    </div>
                    <div class="col-sm-3">
                        <small class="text-muted d-block">Gender</small>
                        <span class="fw-semibold"><?= htmlspecialchars($y['gender']) ?></span>
                    </div>
                    <div class="col-sm-3">
                        <small class="text-muted d-block">Civil Status</small>
                        <span class="fw-semibold"><?= htmlspecialchars($y['civil_status']) ?></span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Address</small>
                        <span class="fw-semibold"><?= htmlspecialchars($y['address']) ?></span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Contact Number</small>
                        <span class="fw-semibold"><?= htmlspecialchars($y['contact'] ?: '—') ?></span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Email</small>
                        <span class="fw-semibold"><?= htmlspecialchars($y['email'] ?: '—') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Education & Employment -->
        <div class="card content-card mb-3">
            <div class="card-header"><i class="bi bi-mortarboard-fill me-2 text-warning"></i>Education & Employment</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Educational Status</small>
                        <span class="fw-semibold"><?= htmlspecialchars($y['educational_status'] ?: '—') ?></span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">School / University</small>
                        <span class="fw-semibold"><?= htmlspecialchars($y['school_name'] ?: '—') ?></span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Employment Status</small>
                        <span class="fw-semibold"><?= htmlspecialchars($y['employment_status'] ?: '—') ?></span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Occupation / Course</small>
                        <span class="fw-semibold"><?= htmlspecialchars($y['occupation'] ?: '—') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Skills & Interests -->
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

        <!-- Emergency Contact -->
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
