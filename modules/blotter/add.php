<?php
require_once __DIR__ . '/../../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$rootPath = '../../';

$db = getDB();

// Support edit mode via ?edit=ID
$editId = (int)($_GET['edit'] ?? 0);
$isEdit = $editId > 0;
$pageTitle = $isEdit ? 'Edit Blotter Record' : 'File Blotter Report';

$existing = null;
if ($isEdit) {
    $stmt = $db->prepare("SELECT * FROM blotter WHERE id = ?");
    $stmt->execute([$editId]);
    $existing = $stmt->fetch();
    if (!$existing) {
        $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Blotter record not found.'];
        header('Location: index.php');
        exit;
    }
}

$natures = [
    'Physical Assault / Mauling',
    'Verbal Abuse / Threats',
    'Theft / Robbery',
    'Trespassing',
    'Noise Disturbance',
    'Domestic Violence',
    'Property Damage',
    'Illegal Gambling',
    'Drug-Related Incident',
    'Estafa / Fraud',
    'Dispute / Quarrel',
    'Other',
];

$errors = [];
$data = [
    'incident_date' => $existing['incident_date'] ?? '',
    'complainant'   => $existing['complainant'] ?? '',
    'respondent'    => $existing['respondent'] ?? '',
    'nature'        => $existing['nature'] ?? '',
    'details'       => $existing['details'] ?? '',
    'status'        => $existing['status'] ?? 'Pending',
    'action_taken'  => $existing['action_taken'] ?? '',
    'recorded_by'   => $existing['recorded_by'] ?? CAPTAIN_NAME,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($data as $key => $_) {
        $data[$key] = trim($_POST[$key] ?? '');
    }

    if (empty($data['complainant'])) $errors[] = 'Complainant name is required.';
    if (empty($data['respondent']))  $errors[] = 'Respondent name is required.';
    if (empty($data['nature']))      $errors[] = 'Nature of incident is required.';

    if (empty($errors)) {
        if ($isEdit) {
            $stmt = $db->prepare("
                UPDATE blotter SET
                    incident_date=:incident_date, complainant=:complainant, respondent=:respondent,
                    nature=:nature, details=:details, status=:status,
                    action_taken=:action_taken, recorded_by=:recorded_by,
                    updated_at=datetime('now','localtime')
                WHERE id=:id
            ");
            $data['id'] = $editId;
            $stmt->execute($data);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Blotter record updated successfully.'];
        } else {
            $stmt = $db->prepare("
                INSERT INTO blotter (incident_date, complainant, respondent, nature, details, status, action_taken, recorded_by)
                VALUES (:incident_date, :complainant, :respondent, :nature, :details, :status, :action_taken, :recorded_by)
            ");
            $stmt->execute($data);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Blotter report filed successfully.'];
        }
        header('Location: index.php');
        exit;
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h4>
        <i class="bi bi-journal-<?= $isEdit ? 'text' : 'plus' ?> me-2"></i>
        <?= $isEdit ? 'Edit Blotter Record #' . str_pad($editId, 4, '0', STR_PAD_LEFT) : 'File Blotter Report' ?>
    </h4>
    <a href="index.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card content-card">
    <div class="card-header"><i class="bi bi-journal-text me-2"></i>Incident Details</div>
    <div class="card-body">
        <form method="POST" novalidate>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Date of Incident</label>
                    <input type="date" name="incident_date" class="form-control" value="<?= htmlspecialchars($data['incident_date']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nature of Incident <span class="text-danger">*</span></label>
                    <select name="nature" class="form-select" required>
                        <option value="">-- Select Nature --</option>
                        <?php foreach ($natures as $n): ?>
                        <option value="<?= $n ?>" <?= $data['nature']===$n?'selected':'' ?>><?= $n ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['Pending','Ongoing','Resolved','Dismissed'] as $s): ?>
                        <option value="<?= $s ?>" <?= $data['status']===$s?'selected':'' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Complainant <span class="text-danger">*</span></label>
                    <input type="text" name="complainant" class="form-control" placeholder="Full name of complainant" value="<?= htmlspecialchars($data['complainant']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Respondent <span class="text-danger">*</span></label>
                    <input type="text" name="respondent" class="form-control" placeholder="Full name of respondent" value="<?= htmlspecialchars($data['respondent']) ?>" required>
                </div>

                <div class="col-12">
                    <label class="form-label">Details / Narrative</label>
                    <textarea name="details" class="form-control" rows="4" placeholder="Describe the incident in detail..."><?= htmlspecialchars($data['details']) ?></textarea>
                </div>

                <div class="col-12">
                    <label class="form-label">Action Taken</label>
                    <textarea name="action_taken" class="form-control" rows="3" placeholder="Actions taken by barangay officials..."><?= htmlspecialchars($data['action_taken']) ?></textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Recorded By</label>
                    <input type="text" name="recorded_by" class="form-control" value="<?= htmlspecialchars($data['recorded_by']) ?>">
                </div>

                <div class="col-12 d-flex gap-2 justify-content-end pt-2">
                    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-save me-1"></i>
                        <?= $isEdit ? 'Update Record' : 'File Report' ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
