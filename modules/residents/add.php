<?php
require_once __DIR__ . '/../../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$pageTitle = 'Add Resident';
$rootPath = '../../';

$errors = [];
$data = [
    'first_name' => '', 'middle_name' => '', 'last_name' => '',
    'birthdate' => '', 'gender' => '', 'civil_status' => '',
    'address' => '', 'contact' => '', 'email' => '',
    'occupation' => '', 'voter_status' => 'No',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($data as $key => $_) {
        $data[$key] = trim($_POST[$key] ?? '');
    }

    if (empty($data['first_name'])) $errors[] = 'First name is required.';
    if (empty($data['last_name']))  $errors[] = 'Last name is required.';
    if (!empty($data['contact']) && !preg_match('/^[\d\s\+\-\(\)]{7,15}$/', $data['contact'])) {
        $errors[] = 'Contact number format is invalid.';
    }
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email address is invalid.';
    }

    if (empty($errors)) {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO residents (first_name, middle_name, last_name, birthdate, gender, civil_status, address, contact, email, occupation, voter_status)
            VALUES (:first_name, :middle_name, :last_name, :birthdate, :gender, :civil_status, :address, :contact, :email, :occupation, :voter_status)
        ");
        $stmt->execute($data);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Resident ' . $data['first_name'] . ' ' . $data['last_name'] . ' added successfully.'];
        header('Location: index.php');
        exit;
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h4><i class="bi bi-person-plus-fill me-2"></i>Add New Resident</h4>
    <a href="index.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <strong>Please fix the following errors:</strong>
    <ul class="mb-0 mt-1">
        <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card content-card">
    <div class="card-header"><i class="bi bi-person-vcard me-2"></i>Resident Information</div>
    <div class="card-body">
        <form method="POST" novalidate>
            <div class="row g-3">
                <!-- Name -->
                <div class="col-md-4">
                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($data['first_name']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Middle Name</label>
                    <input type="text" name="middle_name" class="form-control" value="<?= htmlspecialchars($data['middle_name']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($data['last_name']) ?>" required>
                </div>

                <!-- Personal Info -->
                <div class="col-md-3">
                    <label class="form-label">Birthdate</label>
                    <input type="date" name="birthdate" id="birthdate" class="form-control" value="<?= htmlspecialchars($data['birthdate']) ?>">
                    <small class="text-muted" id="ageDisplay"></small>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="">-- Select --</option>
                        <?php foreach (['Male','Female','Other'] as $g): ?>
                        <option value="<?= $g ?>" <?= $data['gender']===$g?'selected':'' ?>><?= $g ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Civil Status</label>
                    <select name="civil_status" class="form-select">
                        <option value="">-- Select --</option>
                        <?php foreach (['Single','Married','Widowed','Separated','Divorced'] as $cs): ?>
                        <option value="<?= $cs ?>" <?= $data['civil_status']===$cs?'selected':'' ?>><?= $cs ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Voter Status</label>
                    <select name="voter_status" class="form-select">
                        <option value="No" <?= $data['voter_status']==='No'?'selected':'' ?>>Not Registered</option>
                        <option value="Yes" <?= $data['voter_status']==='Yes'?'selected':'' ?>>Registered Voter</option>
                    </select>
                </div>

                <!-- Contact -->
                <div class="col-12">
                    <label class="form-label">Address</label>
                    <input type="text" name="address" class="form-control" placeholder="House No., Street, Purok/Sitio" value="<?= htmlspecialchars($data['address']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Contact Number</label>
                    <input type="text" name="contact" class="form-control" placeholder="e.g. 09XX-XXX-XXXX" value="<?= htmlspecialchars($data['contact']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="optional" value="<?= htmlspecialchars($data['email']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Occupation</label>
                    <input type="text" name="occupation" class="form-control" placeholder="e.g. Farmer, Teacher" value="<?= htmlspecialchars($data['occupation']) ?>">
                </div>

                <div class="col-12 d-flex gap-2 justify-content-end pt-2">
                    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Save Resident
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
