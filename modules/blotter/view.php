<?php
require_once __DIR__ . '/../../config/database.php';
$pageTitle = 'Blotter Details';
$rootPath = '../../';

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM blotter WHERE id = ?");
$stmt->execute([$id]);
$b = $stmt->fetch();

if (!$b) {
    echo '<div class="alert alert-danger m-4">Blotter record not found.</div>';
    include __DIR__ . '/../../includes/footer.php';
    exit;
}

$statusClass = match($b['status']) {
    'Resolved'  => 'bg-success text-white',
    'Ongoing'   => 'bg-primary text-white',
    'Dismissed' => 'bg-secondary text-white',
    default     => 'bg-warning text-dark',
};

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header no-print">
    <h4><i class="bi bi-journal-text me-2"></i>Blotter Record Details</h4>
    <div class="d-flex gap-2">
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <a href="add.php?edit=<?= $b['id'] ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <button onclick="window.print()" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-printer me-1"></i> Print
        </button>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card content-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>
                    <i class="bi bi-journal-text me-2"></i>
                    Entry #<?= str_pad($b['id'], 4, '0', STR_PAD_LEFT) ?>
                </span>
                <span class="badge <?= $statusClass ?> fs-6"><?= htmlspecialchars($b['status']) ?></span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted small">COMPLAINANT</label>
                        <div class="fw-semibold fs-6"><?= htmlspecialchars($b['complainant']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">RESPONDENT</label>
                        <div class="fw-semibold fs-6"><?= htmlspecialchars($b['respondent']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">NATURE OF INCIDENT</label>
                        <div><?= htmlspecialchars($b['nature']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">DATE OF INCIDENT</label>
                        <div><?= $b['incident_date'] ? date('F d, Y', strtotime($b['incident_date'])) : '—' ?></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted small">DETAILS / NARRATIVE</label>
                        <div class="p-3 bg-light rounded" style="white-space:pre-wrap;font-size:0.9rem">
                            <?= $b['details'] ? htmlspecialchars($b['details']) : '<em class="text-muted">No details provided.</em>' ?>
                        </div>
                    </div>
                    <?php if ($b['action_taken']): ?>
                    <div class="col-12">
                        <label class="form-label text-muted small">ACTION TAKEN</label>
                        <div class="p-3 bg-success-subtle rounded" style="white-space:pre-wrap;font-size:0.9rem">
                            <?= htmlspecialchars($b['action_taken']) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card content-card mb-3">
            <div class="card-header"><i class="bi bi-info-circle me-2"></i>Record Info</div>
            <div class="card-body">
                <dl class="row mb-0" style="font-size:0.875rem">
                    <dt class="col-5 text-muted">Entry No.</dt>
                    <dd class="col-7">#<?= str_pad($b['id'], 4, '0', STR_PAD_LEFT) ?></dd>

                    <dt class="col-5 text-muted">Filed On</dt>
                    <dd class="col-7"><?= date('M d, Y g:i A', strtotime($b['created_at'])) ?></dd>

                    <dt class="col-5 text-muted">Last Updated</dt>
                    <dd class="col-7"><?= date('M d, Y g:i A', strtotime($b['updated_at'])) ?></dd>

                    <dt class="col-5 text-muted">Recorded By</dt>
                    <dd class="col-7"><?= htmlspecialchars($b['recorded_by'] ?? '—') ?></dd>

                    <dt class="col-5 text-muted">Status</dt>
                    <dd class="col-7"><span class="badge <?= $statusClass ?>"><?= htmlspecialchars($b['status']) ?></span></dd>
                </dl>
            </div>
        </div>

        <!-- Update Status Quick Form -->
        <div class="card content-card no-print">
            <div class="card-header"><i class="bi bi-arrow-repeat me-2"></i>Update Status</div>
            <div class="card-body">
                <form method="POST" action="update_status.php">
                    <input type="hidden" name="id" value="<?= $b['id'] ?>">
                    <div class="mb-2">
                        <select name="status" class="form-select form-select-sm">
                            <?php foreach (['Pending','Ongoing','Resolved','Dismissed'] as $s): ?>
                            <option value="<?= $s ?>" <?= $b['status']===$s?'selected':'' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <textarea name="action_taken" class="form-control form-control-sm" rows="3"
                                  placeholder="Action taken..."><?= htmlspecialchars($b['action_taken'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary w-100">
                        <i class="bi bi-check-circle me-1"></i> Update
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
