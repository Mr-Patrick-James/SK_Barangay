<?php
require_once __DIR__ . '/../../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$pageTitle = 'Issue Certificate';
$rootPath = '../../';

$db = getDB();
$residents = $db->query("SELECT id, first_name, middle_name, last_name, address FROM residents ORDER BY last_name, first_name")->fetchAll();

$certTypes = ['Barangay Clearance', 'Certificate of Residency', 'Certificate of Indigency'];

$errors = [];
$data = [
    'resident_id' => $_GET['resident_id'] ?? '',
    'cert_type'   => '',
    'purpose'     => '',
    'issued_by'   => CAPTAIN_NAME,
    'or_number'   => '',
    'amount'      => '0',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['resident_id'] = (int)($_POST['resident_id'] ?? 0);
    $data['cert_type']   = trim($_POST['cert_type'] ?? '');
    $data['purpose']     = trim($_POST['purpose'] ?? '');
    $data['issued_by']   = trim($_POST['issued_by'] ?? '');
    $data['or_number']   = trim($_POST['or_number'] ?? '');
    $data['amount']      = (float)($_POST['amount'] ?? 0);

    if (empty($data['cert_type']))  $errors[] = 'Certificate type is required.';
    if (empty($data['purpose']))    $errors[] = 'Purpose is required.';
    if (empty($data['issued_by']))  $errors[] = 'Issued by is required.';

    if (empty($errors)) {
        $stmt = $db->prepare("
            INSERT INTO certificates (resident_id, cert_type, purpose, issued_by, or_number, amount)
            VALUES (:resident_id, :cert_type, :purpose, :issued_by, :or_number, :amount)
        ");
        $stmt->execute([
            ':resident_id' => $data['resident_id'] ?: null,
            ':cert_type'   => $data['cert_type'],
            ':purpose'     => $data['purpose'],
            ':issued_by'   => $data['issued_by'],
            ':or_number'   => $data['or_number'],
            ':amount'      => $data['amount'],
        ]);
        $newId = $db->lastInsertId();
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Certificate issued successfully.'];
        header("Location: print.php?id=$newId");
        exit;
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h4><i class="bi bi-file-earmark-plus-fill me-2"></i>Issue Certificate</h4>
    <a href="index.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <strong>Please fix the following:</strong>
    <ul class="mb-0 mt-1"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card content-card">
            <div class="card-header"><i class="bi bi-file-earmark-text me-2"></i>Certificate Details</div>
            <div class="card-body">
                <form method="POST" novalidate>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Resident</label>
                            <select name="resident_id" class="form-select" id="residentSelect">
                                <option value="">-- Walk-in / Non-registered --</option>
                                <?php foreach ($residents as $r): ?>
                                <option value="<?= $r['id'] ?>" <?= (string)$data['resident_id']===(string)$r['id']?'selected':'' ?>>
                                    <?= htmlspecialchars($r['last_name'] . ', ' . $r['first_name'] . ($r['middle_name'] ? ' ' . $r['middle_name'] : '')) ?>
                                    — <?= htmlspecialchars($r['address'] ?? '') ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Leave blank for walk-in applicants not in the system.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Certificate Type <span class="text-danger">*</span></label>
                            <select name="cert_type" class="form-select" id="certTypeSelect" required>
                                <option value="">-- Select Type --</option>
                                <?php foreach ($certTypes as $ct): ?>
                                <option value="<?= $ct ?>" <?= $data['cert_type']===$ct?'selected':'' ?>><?= $ct ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Purpose <span class="text-danger">*</span></label>
                            <select name="purpose" class="form-select" id="purposeSelect" required>
                                <option value="">-- Select Purpose --</option>
                                <option value="Employment">Employment</option>
                                <option value="Travel Abroad">Travel Abroad</option>
                                <option value="Loan Application">Loan Application</option>
                                <option value="School Enrollment">School Enrollment</option>
                                <option value="Business Permit">Business Permit</option>
                                <option value="Government Transaction">Government Transaction</option>
                                <option value="Medical Assistance">Medical Assistance</option>
                                <option value="Legal Purposes">Legal Purposes</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Issued By</label>
                            <input type="text" name="issued_by" class="form-control" value="<?= htmlspecialchars($data['issued_by']) ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">OR Number</label>
                            <input type="text" name="or_number" class="form-control" placeholder="Optional" value="<?= htmlspecialchars($data['or_number']) ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Amount (₱)</label>
                            <input type="number" name="amount" class="form-control" min="0" step="0.01" value="<?= htmlspecialchars($data['amount']) ?>">
                        </div>

                        <div class="col-12 d-flex gap-2 justify-content-end pt-2">
                            <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-file-earmark-check me-1"></i> Issue & Print
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card content-card">
            <div class="card-header"><i class="bi bi-info-circle me-2"></i>Certificate Guide</div>
            <div class="card-body">
                <div class="mb-3">
                    <span class="badge bg-primary-subtle text-primary mb-1">Barangay Clearance</span>
                    <p class="small text-muted mb-0">For employment, business, and general purposes. Certifies good moral character.</p>
                </div>
                <div class="mb-3">
                    <span class="badge bg-success-subtle text-success mb-1">Certificate of Residency</span>
                    <p class="small text-muted mb-0">Certifies that the person is a bona fide resident of the barangay.</p>
                </div>
                <div class="mb-3">
                    <span class="badge bg-warning-subtle text-warning mb-1">Certificate of Indigency</span>
                    <p class="small text-muted mb-0">For indigent residents needing medical, legal, or financial assistance.</p>
                </div>
                <hr>
                <p class="small text-muted mb-0">
                    <i class="bi bi-printer me-1"></i>After issuing, you will be redirected to the print preview.
                </p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
