<?php
require_once __DIR__ . '/../../config/database.php';
$pageTitle = 'Add Youth Profile';
$rootPath  = '../../';

$db     = getDB();
$errors = [];
$data   = [
    'first_name' => '', 'middle_name' => '', 'last_name' => '', 'suffix' => '',
    'birthdate' => '', 'gender' => '', 'civil_status' => 'Single',
    'address' => '', 'contact' => '', 'email' => '',
    'educational_status' => '', 'school_name' => '',
    'employment_status' => '', 'occupation' => '',
    'sk_voter' => 'No', 'skills' => '', 'interests' => '',
    'emergency_contact_name' => '', 'emergency_contact_number' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($data as $key => $_) {
        $data[$key] = trim($_POST[$key] ?? '');
    }
    $data['civil_status'] = $_POST['civil_status'] ?? 'Single';
    $data['sk_voter']     = $_POST['sk_voter'] ?? 'No';

    // Validation
    if (empty($data['first_name'])) $errors[] = 'First name is required.';
    if (empty($data['last_name']))  $errors[] = 'Last name is required.';
    if (empty($data['birthdate']))  $errors[] = 'Birthdate is required.';
    if (empty($data['gender']))     $errors[] = 'Gender is required.';
    if (empty($data['address']))    $errors[] = 'Address is required.';

    $classification = '';
    if (!empty($data['birthdate'])) {
        $birth = new DateTime($data['birthdate']);
        $today = new DateTime();
        $age   = (int)$today->diff($birth)->y;

        if ($age >= 15 && $age <= 24) {
            $classification = 'Core Youth (15-24)';
        } elseif ($age >= 25 && $age <= 30) {
            $classification = 'Non-Core Youth (25-30)';
        } else {
            $errors[] = "Age must be between 15 and 30 to be a KK member. Computed age: $age.";
        }
    }

    if (empty($errors)) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $stmt = $db->prepare("
            INSERT INTO kk_youth
                (first_name, middle_name, last_name, suffix, birthdate, gender, civil_status,
                 address, contact, email, youth_classification, educational_status, school_name,
                 employment_status, occupation, sk_voter, skills, interests,
                 emergency_contact_name, emergency_contact_number)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");
        $stmt->execute([
            $data['first_name'], $data['middle_name'], $data['last_name'], $data['suffix'],
            $data['birthdate'], $data['gender'], $data['civil_status'],
            $data['address'], $data['contact'], $data['email'],
            $classification, $data['educational_status'], $data['school_name'],
            $data['employment_status'], $data['occupation'],
            $data['sk_voter'], $data['skills'], $data['interests'],
            $data['emergency_contact_name'], $data['emergency_contact_number'],
        ]);

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Youth profile added successfully.'];
        header('Location: index.php');
        exit;
    }
}

include __DIR__ . '/../../includes/header.php';

// Education options helper
$eduOptions = [
    'In School'    => ['Elementary In School', 'High School In School', 'Senior High School In School', 'College In School', 'Vocational/Technical In School'],
    'Out of School'=> ['Out of School Youth (OSY)'],
    'Graduates'    => ['High School Graduate', 'College Graduate', 'Vocational/Technical Graduate'],
];
?>

<div class="page-header">
    <h4><i class="bi bi-person-plus-fill me-2"></i>Add Youth Profile</h4>
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to List
    </a>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger alert-dismissible">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <strong>Please fix the following:</strong>
    <ul class="mb-0 mt-1">
        <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST" action="add.php" novalidate>

    <!-- ── Personal Information ── -->
    <div class="card content-card mb-4">
        <div class="card-header">
            <i class="bi bi-person-fill me-2 text-primary"></i>Personal Information
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" class="form-control"
                           value="<?= htmlspecialchars($data['first_name']) ?>" required autofocus>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Middle Name</label>
                    <input type="text" name="middle_name" class="form-control"
                           value="<?= htmlspecialchars($data['middle_name']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" class="form-control"
                           value="<?= htmlspecialchars($data['last_name']) ?>" required>
                </div>
                <div class="col-md-1">
                    <label class="form-label">Suffix</label>
                    <input type="text" name="suffix" class="form-control" placeholder="Jr."
                           value="<?= htmlspecialchars($data['suffix']) ?>">
                </div>

                <!-- Birthdate + auto age/classification -->
                <div class="col-md-3">
                    <label class="form-label">Birthdate <span class="text-danger">*</span></label>
                    <input type="date" name="birthdate" id="birthdate" class="form-control"
                           value="<?= htmlspecialchars($data['birthdate']) ?>"
                           max="<?= date('Y-m-d', strtotime('-15 years')) ?>"
                           min="<?= date('Y-m-d', strtotime('-31 years')) ?>"
                           required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Age <small class="text-muted">(auto)</small></label>
                    <input type="text" id="age_display" class="form-control bg-light fw-bold text-center" readonly placeholder="—">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Youth Classification <small class="text-muted">(auto)</small></label>
                    <input type="text" id="classification_display" class="form-control bg-light fw-semibold" readonly placeholder="Computed from birthdate">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Gender <span class="text-danger">*</span></label>
                    <select name="gender" class="form-select" required>
                        <option value="">— Select —</option>
                        <option value="Male"   <?= $data['gender'] === 'Male'   ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= $data['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Civil Status</label>
                    <select name="civil_status" class="form-select">
                        <?php foreach (['Single','Married','Widowed','Separated'] as $cs): ?>
                        <option value="<?= $cs ?>" <?= $data['civil_status'] === $cs ? 'selected' : '' ?>><?= $cs ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Address <span class="text-danger">*</span></label>
                    <input type="text" name="address" class="form-control"
                           placeholder="Purok/Street, Barangay Bacungan, Naujan"
                           value="<?= htmlspecialchars($data['address']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Contact Number</label>
                    <input type="text" name="contact" class="form-control" placeholder="09XXXXXXXXX"
                           value="<?= htmlspecialchars($data['contact']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= htmlspecialchars($data['email']) ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- ── Education & Employment ── -->
    <div class="card content-card mb-4">
        <div class="card-header">
            <i class="bi bi-mortarboard-fill me-2 text-warning"></i>Education & Employment
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Educational Status</label>
                    <select name="educational_status" class="form-select">
                        <option value="">— Select Status —</option>
                        <?php foreach ($eduOptions as $group => $opts): ?>
                        <optgroup label="<?= $group ?>">
                            <?php foreach ($opts as $opt): ?>
                            <option value="<?= $opt ?>" <?= $data['educational_status'] === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">School / University Name</label>
                    <input type="text" name="school_name" class="form-control"
                           placeholder="e.g. Palawan State University"
                           value="<?= htmlspecialchars($data['school_name']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Employment Status</label>
                    <select name="employment_status" class="form-select">
                        <option value="">— Select Status —</option>
                        <?php foreach (['Student','Employed','Self-Employed','Unemployed','OFW'] as $es): ?>
                        <option value="<?= $es ?>" <?= $data['employment_status'] === $es ? 'selected' : '' ?>><?= $es ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Occupation / Course</label>
                    <input type="text" name="occupation" class="form-control"
                           placeholder="e.g. Farmer, BS Nursing, Carpenter"
                           value="<?= htmlspecialchars($data['occupation']) ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- ── SK Voter & Participation ── -->
    <div class="card content-card mb-4">
        <div class="card-header">
            <i class="bi bi-check2-square me-2 text-success"></i>SK Voter & Participation
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">SK Registered Voter</label>
                    <select name="sk_voter" class="form-select">
                        <option value="No"  <?= $data['sk_voter'] === 'No'  ? 'selected' : '' ?>>No</option>
                        <option value="Yes" <?= $data['sk_voter'] === 'Yes' ? 'selected' : '' ?>>Yes</option>
                    </select>
                    <div class="form-text">Must be 15–30 years old to register as SK voter.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Skills</label>
                    <input type="text" name="skills" class="form-control"
                           placeholder="e.g. Carpentry, Cooking, IT, Welding"
                           value="<?= htmlspecialchars($data['skills']) ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Interests / Hobbies</label>
                    <input type="text" name="interests" class="form-control"
                           placeholder="e.g. Sports, Arts, Music, Reading"
                           value="<?= htmlspecialchars($data['interests']) ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- ── Emergency Contact ── -->
    <div class="card content-card mb-4">
        <div class="card-header">
            <i class="bi bi-telephone-fill me-2 text-danger"></i>Emergency Contact
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Contact Person Name</label>
                    <input type="text" name="emergency_contact_name" class="form-control"
                           placeholder="Parent / Guardian name"
                           value="<?= htmlspecialchars($data['emergency_contact_name']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Contact Number</label>
                    <input type="text" name="emergency_contact_number" class="form-control"
                           placeholder="09XXXXXXXXX"
                           value="<?= htmlspecialchars($data['emergency_contact_number']) ?>">
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 justify-content-end mb-4">
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-x-circle me-1"></i>Cancel
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save me-2"></i>Save Profile
        </button>
    </div>
</form>

<script>
function computeAge(birthdateStr) {
    if (!birthdateStr) return null;
    const birth = new Date(birthdateStr);
    const today = new Date();
    let age = today.getFullYear() - birth.getFullYear();
    const m = today.getMonth() - birth.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
    return age;
}

function updateAgeClassification(birthdateStr) {
    const age      = computeAge(birthdateStr);
    const ageEl    = document.getElementById('age_display');
    const classEl  = document.getElementById('classification_display');
    if (age === null) return;

    ageEl.value = age + ' yrs old';

    if (age >= 15 && age <= 24) {
        classEl.value     = 'Core Youth (15–24)';
        classEl.className = 'form-control bg-warning-subtle text-warning-emphasis fw-semibold';
    } else if (age >= 25 && age <= 30) {
        classEl.value     = 'Non-Core Youth (25–30)';
        classEl.className = 'form-control bg-info-subtle text-info-emphasis fw-semibold';
    } else {
        classEl.value     = 'Not eligible (age must be 15–30)';
        classEl.className = 'form-control bg-danger-subtle text-danger fw-semibold';
    }
}

const bdInput = document.getElementById('birthdate');
bdInput.addEventListener('change', () => updateAgeClassification(bdInput.value));
// Run on load (in case of validation re-render)
if (bdInput.value) updateAgeClassification(bdInput.value);
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
