<?php
require_once __DIR__ . '/../../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$pageTitle = 'Edit Resident';
$rootPath = '../../';

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

$resident = $db->prepare("SELECT * FROM residents WHERE id = ?");
$resident->execute([$id]);
$resident = $resident->fetch();

if (!$resident) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Resident not found.'];
    header('Location: index.php');
    exit;
}

$errors = [];
$data = $resident;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['first_name','middle_name','last_name','birthdate','gender','civil_status','address','contact','email','occupation','voter_status'];
    foreach ($fields as $key) {
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
        $stmt = $db->prepare("
            UPDATE residents SET
                first_name=:first_name, middle_name=:middle_name, last_name=:last_name,
                birthdate=:birthdate, gender=:gender, civil_status=:civil_status,
                address=:address, contact=:contact, email=:email,
                occupation=:occupation, voter_status=:voter_status,
                updated_at=datetime('now','localtime')
            WHERE id=:id
        ");
        $data['id'] = $id;
        $stmt->execute($data);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Resident updated successfully.'];
        header('Location: index.php');
        exit;
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h4><i class="bi bi-pencil-square me-2"></i>Edit Resident</h4>
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
    <div class="card-header">
        <i class="bi bi-person-vcard me-2"></i>
        Editing: <strong><?= htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']) ?></strong>
        <small class="text-muted ms-2">ID #<?= $id ?></small>
    </div>
    <div class="card-body">
        <form method="POST" novalidate>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($data['first_name']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Middle Name</label>
                    <input type="text" name="middle_name" class="form-control" value="<?= htmlspecialchars($data['middle_name'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($data['last_name']) ?>" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Birthdate</label>
                    <input type="date" name="birthdate" id="birthdate" class="form-control" value="<?= htmlspecialchars($data['birthdate'] ?? '') ?>">
                    <small class="text-muted" id="ageDisplay"></small>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="">-- Select --</option>
                        <?php foreach (['Male','Female','Other'] as $g): ?>
                        <option value="<?= $g ?>" <?= ($data['gender']??'')===$g?'selected':'' ?>><?= $g ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Civil Status</label>
                    <select name="civil_status" class="form-select">
                        <option value="">-- Select --</option>
                        <?php foreach (['Single','Married','Widowed','Separated','Divorced'] as $cs): ?>
                        <option value="<?= $cs ?>" <?= ($data['civil_status']??'')===$cs?'selected':'' ?>><?= $cs ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Voter Status</label>
                    <select name="voter_status" class="form-select">
                        <option value="No" <?= ($data['voter_status']??'')==='No'?'selected':'' ?>>Not Registered</option>
                        <option value="Yes" <?= ($data['voter_status']??'')==='Yes'?'selected':'' ?>>Registered Voter</option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Address</label>
                    <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($data['address'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Contact Number</label>
                    <input type="text" name="contact" class="form-control" value="<?= htmlspecialchars($data['contact'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($data['email'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Occupation</label>
                    <input type="text" name="occupation" class="form-control" value="<?= htmlspecialchars($data['occupation'] ?? '') ?>">
                </div>

                <div class="col-12 d-flex gap-2 justify-content-end pt-2">
                    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Update Resident
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
