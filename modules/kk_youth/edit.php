<?php
require_once __DIR__ . '/../../config/auth.php';
requireRole(['Admin', 'SK Official']);
require_once __DIR__ . '/../../config/database.php';
$pageTitle = 'Edit KK Youth Profile';
$rootPath  = '../../';

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM kk_youth WHERE id = ?");
$stmt->execute([$id]);
$existing = $stmt->fetch();

if (!$existing) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Youth profile not found.'];
    header('Location: index.php');
    exit;
}

$errors = [];
$data   = $existing;

// Map legacy column names → new names for existing records
if (empty($data['home_address'])           && !empty($data['address']))           $data['home_address'] = $data['address'];
if (empty($data['educational_attainment']) && !empty($data['educational_status'])) $data['educational_attainment'] = $data['educational_status'];
if (empty($data['work_status'])            && !empty($data['employment_status'])) $data['work_status'] = $data['employment_status'];
if (empty($data['registered_sk_voter'])    && !empty($data['sk_voter']))          $data['registered_sk_voter'] = $data['sk_voter'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'first_name','middle_name','last_name','suffix',
        'region','province','city_municipality','barangay','purok',
        'birthdate','gender','email','contact','home_address','civil_status',
        'youth_classification','educational_attainment','school_name',
        'work_status','occupation',
        'registered_sk_voter','voted_last_sk_election','registered_national_voter',
        'attended_kk_assembly','kk_assembly_times','kk_assembly_no_reason',
        'skills','interests','emergency_contact_name','emergency_contact_number',
    ];
    foreach ($fields as $f) {
        $data[$f] = trim($_POST[$f] ?? '');
    }

    if (empty($data['first_name'])) $errors[] = 'First name is required.';
    if (empty($data['last_name']))  $errors[] = 'Last name is required.';
    if (empty($data['birthdate']))  $errors[] = 'Birthdate is required.';
    if (empty($data['gender']))     $errors[] = 'Sex assigned at birth is required.';

    $age = null;
    $youth_age_group = '';
    if (!empty($data['birthdate'])) {
        $birth = new DateTime($data['birthdate']);
        $today = new DateTime();
        $age   = (int)$today->diff($birth)->y;

        if ($age >= 15 && $age <= 17)      $youth_age_group = 'Child Youth (15-17 yrs old)';
        elseif ($age >= 18 && $age <= 24)  $youth_age_group = 'Core Youth (18-24 yrs old)';
        elseif ($age >= 25 && $age <= 30)  $youth_age_group = 'Young Adult (25-30 yrs old)';
        else $errors[] = "Age must be between 15 and 30. Computed age: $age.";
    }

    if (empty($errors)) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $stmt = $db->prepare("
            UPDATE kk_youth SET
                first_name=?, middle_name=?, last_name=?, suffix=?,
                region=?, province=?, city_municipality=?, barangay=?, purok=?,
                birthdate=?, gender=?, age=?, civil_status=?, email=?, contact=?, home_address=?, address=?,
                youth_classification=?, youth_age_group=?,
                educational_attainment=?, school_name=?,
                work_status=?, occupation=?,
                registered_sk_voter=?, voted_last_sk_election=?,
                registered_national_voter=?,
                attended_kk_assembly=?, kk_assembly_times=?, kk_assembly_no_reason=?,
                skills=?, interests=?,
                emergency_contact_name=?, emergency_contact_number=?,
                updated_at=datetime('now','localtime')
            WHERE id=?
        ");
        $stmt->execute([
            $data['first_name'], $data['middle_name'], $data['last_name'], $data['suffix'],
            $data['region'], $data['province'], $data['city_municipality'], $data['barangay'], $data['purok'],
            $data['birthdate'], $data['gender'], $age, $data['civil_status'],
            $data['email'], $data['contact'], $data['home_address'], $data['home_address'],
            $data['youth_classification'], $youth_age_group,
            $data['educational_attainment'], $data['school_name'],
            $data['work_status'], $data['occupation'],
            $data['registered_sk_voter'], $data['voted_last_sk_election'],
            $data['registered_national_voter'],
            $data['attended_kk_assembly'], $data['kk_assembly_times'], $data['kk_assembly_no_reason'],
            $data['skills'], $data['interests'],
            $data['emergency_contact_name'], $data['emergency_contact_number'],
            $id,
        ]);

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Youth profile updated successfully.'];
        header("Location: view.php?id=$id");
        exit;
    }
}

include __DIR__ . '/../../includes/header.php';

$civilStatusOptions  = ['Single','Married','Widowed','Divorced','Separated','Annulled','Unknown','Live-in'];
$youthClassOptions   = ['In School Youth','Out of School Youth','Working Youth','Youth w/ Specific Needs','Indigenous People','Children in Conflict w/ Law','Person w/ Disability'];
$workStatusOptions   = ['Employed','Unemployed','Self-Employed','Currently looking for a job','Not interested looking for a job'];
$eduOptions = [
    'Elementary Level','Elementary Graduate',
    'High School Level','High School Graduate',
    'Vocational Graduate',
    'College Level','College Graduate',
    'Masters Level','Masters Graduate',
    'Doctorate Level','Doctorate Graduate',
];
$assemblyTimesOptions    = ['1-2 Times','3-4 Times','5 and Above'];
$assemblyNoReasonOptions = ['There was no KK Assembly Meeting','Not Interested to attend'];
?>

<div class="page-header">
    <h4><i class="bi bi-pencil-square me-2"></i>Edit KK Youth Profile</h4>
    <a href="view.php?id=<?= $id ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Profile
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

<form method="POST" action="edit.php?id=<?= $id ?>" novalidate>

    <!-- ── I. PROFILE ── -->
    <div class="card content-card mb-4">
        <div class="card-header bg-primary text-white">
            <i class="bi bi-person-fill me-2"></i>I. Profile
        </div>
        <div class="card-body">

            <!-- Name -->
            <h6 class="fw-semibold text-muted mb-2"><i class="bi bi-type me-1"></i>Name of Respondent</h6>
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" class="form-control"
                           value="<?= htmlspecialchars($data['last_name']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" class="form-control"
                           value="<?= htmlspecialchars($data['first_name']) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Middle Name</label>
                    <input type="text" name="middle_name" class="form-control"
                           value="<?= htmlspecialchars($data['middle_name'] ?? '') ?>">
                </div>
                <div class="col-md-1">
                    <label class="form-label">Suffix</label>
                    <input type="text" name="suffix" class="form-control"
                           value="<?= htmlspecialchars($data['suffix'] ?? '') ?>">
                </div>
            </div>

            <hr class="my-3">

            <!-- Location -->
            <h6 class="fw-semibold text-muted mb-2"><i class="bi bi-geo-alt me-1"></i>Location</h6>
            <div class="row g-3 mb-3">
                <div class="col-md-2">
                    <label class="form-label">Region</label>
                    <input type="text" name="region" class="form-control"
                           value="<?= htmlspecialchars($data['region'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Province</label>
                    <input type="text" name="province" class="form-control"
                           value="<?= htmlspecialchars($data['province'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">City / Municipality</label>
                    <input type="text" name="city_municipality" class="form-control"
                           value="<?= htmlspecialchars($data['city_municipality'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Barangay</label>
                    <input type="text" name="barangay" class="form-control"
                           value="<?= htmlspecialchars($data['barangay'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Purok</label>
                    <input type="text" name="purok" class="form-control"
                           value="<?= htmlspecialchars($data['purok'] ?? '') ?>">
                </div>
            </div>

            <hr class="my-3">

            <!-- Personal Details -->
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Sex Assigned at Birth <span class="text-danger">*</span></label>
                    <select name="gender" class="form-select" required>
                        <option value="">— Select —</option>
                        <option value="Male"   <?= ($data['gender'] ?? '') === 'Male'   ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= ($data['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Birthday <span class="text-danger">*</span></label>
                    <input type="date" name="birthdate" id="birthdate" class="form-control"
                           value="<?= htmlspecialchars($data['birthdate']) ?>"
                           max="<?= date('Y-m-d', strtotime('-15 years')) ?>"
                           min="<?= date('Y-m-d', strtotime('-31 years')) ?>"
                           required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Age <small class="text-muted">(auto)</small></label>
                    <input type="text" id="age_display" class="form-control bg-light fw-bold text-center" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Youth Age Group <small class="text-muted">(auto)</small></label>
                    <input type="text" id="age_group_display" class="form-control bg-light fw-semibold" readonly
                           value="<?= htmlspecialchars($data['youth_age_group'] ?? '') ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">E-mail Address</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= htmlspecialchars($data['email'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Contact #</label>
                    <input type="text" name="contact" class="form-control"
                           value="<?= htmlspecialchars($data['contact'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Home Address</label>
                    <input type="text" name="home_address" class="form-control"
                           value="<?= htmlspecialchars($data['home_address'] ?? '') ?>">
                </div>
            </div>

        </div>
    </div>

    <!-- ── II. DEMOGRAPHIC CHARACTERISTICS ── -->
    <div class="card content-card mb-4">
        <div class="card-header bg-success text-white">
            <i class="bi bi-bar-chart-fill me-2"></i>II. Demographic Characteristics
        </div>
        <div class="card-body">
            <div class="row g-4">

                <!-- Civil Status -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Civil Status</label>
                    <div class="row row-cols-2 g-1">
                        <?php foreach ($civilStatusOptions as $cs): ?>
                        <div class="col">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="civil_status"
                                       id="cs_<?= $cs ?>" value="<?= $cs ?>"
                                       <?= (($data['civil_status'] ?? 'Single') === $cs) ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="cs_<?= $cs ?>"><?= $cs ?></label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Youth Classification -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Youth Classification</label>
                    <div class="d-flex flex-column gap-1">
                        <?php foreach ($youthClassOptions as $yc): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="youth_classification"
                                   id="yc_<?= md5($yc) ?>" value="<?= $yc ?>"
                                   <?= (($data['youth_classification'] ?? '') === $yc) ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="yc_<?= md5($yc) ?>"><?= $yc ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Work Status -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Work Status</label>
                    <div class="d-flex flex-column gap-1">
                        <?php foreach ($workStatusOptions as $ws): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="work_status"
                                   id="ws_<?= md5($ws) ?>" value="<?= $ws ?>"
                                   <?= (($data['work_status'] ?? '') === $ws) ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="ws_<?= md5($ws) ?>"><?= $ws ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Educational Background -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Educational Background</label>
                    <div class="d-flex flex-column gap-1">
                        <?php foreach ($eduOptions as $edu): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="educational_attainment"
                                   id="edu_<?= md5($edu) ?>" value="<?= $edu ?>"
                                   <?= (($data['educational_attainment'] ?? '') === $edu) ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="edu_<?= md5($edu) ?>"><?= $edu ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-2">
                        <label class="form-label small text-muted">School / University (if applicable)</label>
                        <input type="text" name="school_name" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($data['school_name'] ?? '') ?>">
                    </div>
                </div>

                <!-- Voter & Assembly Blocks -->
                <div class="col-md-8">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <div class="card border-success-subtle h-100">
                                <div class="card-body">
                                    <label class="form-label fw-semibold">Registered SK Voter?</label>
                                    <div class="d-flex gap-3">
                                        <?php foreach (['Yes','No'] as $v): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="registered_sk_voter"
                                                   id="sk_<?= $v ?>" value="<?= $v ?>"
                                                   <?= (($data['registered_sk_voter'] ?? 'No') === $v) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="sk_<?= $v ?>"><?= $v ?></label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card border-success-subtle h-100">
                                <div class="card-body">
                                    <label class="form-label fw-semibold">Did you vote last SK Election?</label>
                                    <div class="d-flex gap-3">
                                        <?php foreach (['Yes','No'] as $v): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="voted_last_sk_election"
                                                   id="voted_<?= $v ?>" value="<?= $v ?>"
                                                   <?= (($data['voted_last_sk_election'] ?? 'No') === $v) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="voted_<?= $v ?>"><?= $v ?></label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card border-primary-subtle h-100">
                                <div class="card-body">
                                    <label class="form-label fw-semibold">Registered National Voter?</label>
                                    <div class="d-flex gap-3">
                                        <?php foreach (['Yes','No'] as $v): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="registered_national_voter"
                                                   id="nv_<?= $v ?>" value="<?= $v ?>"
                                                   <?= (($data['registered_national_voter'] ?? 'No') === $v) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="nv_<?= $v ?>"><?= $v ?></label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card border-primary-subtle h-100">
                                <div class="card-body">
                                    <label class="form-label fw-semibold">If Yes, How many times?</label>
                                    <div class="d-flex flex-column gap-1">
                                        <?php foreach ($assemblyTimesOptions as $at): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="kk_assembly_times"
                                                   id="at_<?= md5($at) ?>" value="<?= $at ?>"
                                                   <?= (($data['kk_assembly_times'] ?? '') === $at) ? 'checked' : '' ?>>
                                            <label class="form-check-label small" for="at_<?= md5($at) ?>"><?= $at ?></label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card border-warning-subtle h-100">
                                <div class="card-body">
                                    <label class="form-label fw-semibold">Have you attended a KK Assembly?</label>
                                    <div class="d-flex gap-3">
                                        <?php foreach (['Yes','No'] as $v): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="attended_kk_assembly"
                                                   id="kka_<?= $v ?>" value="<?= $v ?>"
                                                   <?= (($data['attended_kk_assembly'] ?? 'No') === $v) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="kka_<?= $v ?>"><?= $v ?></label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card border-warning-subtle h-100">
                                <div class="card-body">
                                    <label class="form-label fw-semibold">If No, Why?</label>
                                    <div class="d-flex flex-column gap-1">
                                        <?php foreach ($assemblyNoReasonOptions as $nr): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="kk_assembly_no_reason"
                                                   id="nr_<?= md5($nr) ?>" value="<?= $nr ?>"
                                                   <?= (($data['kk_assembly_no_reason'] ?? '') === $nr) ? 'checked' : '' ?>>
                                            <label class="form-check-label small" for="nr_<?= md5($nr) ?>"><?= $nr ?></label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- ── Extra Fields ── -->
    <div class="card content-card mb-4">
        <div class="card-header">
            <i class="bi bi-stars me-2 text-warning"></i>Additional Information
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Occupation / Course</label>
                    <input type="text" name="occupation" class="form-control"
                           value="<?= htmlspecialchars($data['occupation'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Skills</label>
                    <input type="text" name="skills" class="form-control"
                           value="<?= htmlspecialchars($data['skills'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Interests / Hobbies</label>
                    <input type="text" name="interests" class="form-control"
                           value="<?= htmlspecialchars($data['interests'] ?? '') ?>">
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
                           value="<?= htmlspecialchars($data['emergency_contact_name'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Contact Number</label>
                    <input type="text" name="emergency_contact_number" class="form-control"
                           value="<?= htmlspecialchars($data['emergency_contact_number'] ?? '') ?>">
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 justify-content-end mb-4">
        <a href="view.php?id=<?= $id ?>" class="btn btn-outline-secondary">
            <i class="bi bi-x-circle me-1"></i>Cancel
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save me-2"></i>Update Profile
        </button>
    </div>
</form>

<script>
function computeAge(bd) {
    if (!bd) return null;
    const birth = new Date(bd), today = new Date();
    let age = today.getFullYear() - birth.getFullYear();
    const m = today.getMonth() - birth.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
    return age;
}
function updateAgeGroup(bd) {
    const age = computeAge(bd);
    const ageEl   = document.getElementById('age_display');
    const groupEl = document.getElementById('age_group_display');
    if (age === null) return;
    ageEl.value = age + ' yrs old';
    if (age >= 15 && age <= 17) {
        groupEl.value = 'Child Youth (15-17 yrs old)';
        groupEl.className = 'form-control bg-info-subtle text-info-emphasis fw-semibold';
    } else if (age >= 18 && age <= 24) {
        groupEl.value = 'Core Youth (18-24 yrs old)';
        groupEl.className = 'form-control bg-warning-subtle text-warning-emphasis fw-semibold';
    } else if (age >= 25 && age <= 30) {
        groupEl.value = 'Young Adult (25-30 yrs old)';
        groupEl.className = 'form-control bg-success-subtle text-success-emphasis fw-semibold';
    } else {
        groupEl.value = 'Not eligible (must be 15–30)';
        groupEl.className = 'form-control bg-danger-subtle text-danger fw-semibold';
    }
}
const bdInput = document.getElementById('birthdate');
bdInput.addEventListener('change', () => updateAgeGroup(bdInput.value));
updateAgeGroup(bdInput.value);
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
