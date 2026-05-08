<?php
require_once __DIR__ . '/../../config/database.php';
$pageTitle = 'Print Certificate';
$rootPath = '../../';

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("
    SELECT c.*, r.first_name, r.middle_name, r.last_name, r.address, r.birthdate, r.civil_status, r.gender, r.occupation
    FROM certificates c
    LEFT JOIN residents r ON c.resident_id = r.id
    WHERE c.id = ?
");
$stmt->execute([$id]);
$cert = $stmt->fetch();

if (!$cert) {
    die('<div class="alert alert-danger m-4">Certificate not found.</div>');
}

$fullName = trim(($cert['first_name'] ?? '') . ' ' . ($cert['middle_name'] ? $cert['middle_name'] . ' ' : '') . ($cert['last_name'] ?? ''));
if (!$fullName) $fullName = 'N/A';

$issuedDate = date('F d, Y', strtotime($cert['issued_at']));
$issuedDay  = date('d', strtotime($cert['issued_at']));
$issuedMonth = date('F', strtotime($cert['issued_at']));
$issuedYear  = date('Y', strtotime($cert['issued_at']));

// Get barangay captain
$captain = $db->query("SELECT first_name, middle_name, last_name FROM officials WHERE position='Barangay Captain' LIMIT 1")->fetch();
$captainName = $captain ? trim($captain['first_name'] . ' ' . ($captain['middle_name'] ? $captain['middle_name'] . ' ' : '') . $captain['last_name']) : CAPTAIN_NAME;

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header no-print">
    <h4><i class="bi bi-printer-fill me-2"></i>Print Certificate</h4>
    <div class="d-flex gap-2">
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <button onclick="window.print()" class="btn btn-primary btn-sm">
            <i class="bi bi-printer me-1"></i> Print
        </button>
    </div>
</div>

<!-- Certificate Paper -->
<div class="certificate-paper">
    <div class="cert-border">

        <!-- Header -->
        <div class="cert-header">
            <div class="cert-republic">Republic of the Philippines</div>
            <div class="cert-republic"><?= PROVINCE ?></div>
            <div class="cert-republic"><?= MUNICIPALITY ?></div>
            <div class="cert-seal mt-2 mb-1">
                <i class="bi bi-shield-fill-check" style="font-size:3.5rem;color:#1a3a5c"></i>
            </div>
            <div class="cert-brgy-name"><?= BARANGAY_NAME ?></div>
            <div class="cert-location">Office of the Punong Barangay</div>
        </div>

        <hr style="border-color:#1a3a5c;border-width:2px;margin:12px 0">

        <!-- Certificate Title -->
        <div class="cert-title"><?= strtoupper($cert['cert_type']) ?></div>

        <!-- Control Number -->
        <div class="text-end mb-3" style="font-size:0.8rem;color:#555">
            Control No.: <strong><?= str_pad($cert['id'], 6, '0', STR_PAD_LEFT) ?></strong>
            <?php if ($cert['or_number']): ?>
            &nbsp;|&nbsp; OR No.: <strong><?= htmlspecialchars($cert['or_number']) ?></strong>
            <?php endif; ?>
        </div>

        <!-- Body -->
        <div class="cert-body">
            <?php if ($cert['cert_type'] === 'Barangay Clearance'): ?>
            <p>TO WHOM IT MAY CONCERN:</p>
            <p>
                This is to certify that <span class="highlight"><?= htmlspecialchars($fullName) ?></span>,
                <?php if ($cert['gender']): ?>
                    <?= $cert['gender'] === 'Male' ? 'a male' : 'a female' ?>,
                <?php endif; ?>
                <?php if ($cert['civil_status']): ?>
                    <?= strtolower($cert['civil_status']) ?>,
                <?php endif; ?>
                <?php if ($cert['birthdate']): ?>
                    born on <?= date('F d, Y', strtotime($cert['birthdate'])) ?>,
                <?php endif; ?>
                is a <strong>bona fide resident</strong> of
                <span class="highlight"><?= htmlspecialchars($cert['address'] ?: BARANGAY_NAME) ?></span>,
                <?= MUNICIPALITY ?>, <?= PROVINCE ?>.
            </p>
            <p>
                Further, this is to certify that the above-named person is of <strong>good moral character</strong>,
                law-abiding, and has no derogatory record on file in this office as of this date.
            </p>
            <p>
                This certification is issued upon the request of the above-named person for the purpose of
                <span class="highlight"><?= htmlspecialchars($cert['purpose']) ?></span>
                and for whatever legal purpose it may serve.
            </p>

            <?php elseif ($cert['cert_type'] === 'Certificate of Residency'): ?>
            <p>TO WHOM IT MAY CONCERN:</p>
            <p>
                This is to certify that <span class="highlight"><?= htmlspecialchars($fullName) ?></span>,
                <?php if ($cert['civil_status']): ?>
                    <?= strtolower($cert['civil_status']) ?>,
                <?php endif; ?>
                is a <strong>bona fide resident</strong> of
                <span class="highlight"><?= htmlspecialchars($cert['address'] ?: BARANGAY_NAME) ?></span>,
                <?= MUNICIPALITY ?>, <?= PROVINCE ?>.
            </p>
            <p>
                The above-named person has been residing in this barangay and is known to the undersigned.
            </p>
            <p>
                This certification is issued upon the request of the above-named person for the purpose of
                <span class="highlight"><?= htmlspecialchars($cert['purpose']) ?></span>
                and for whatever legal purpose it may serve.
            </p>

            <?php elseif ($cert['cert_type'] === 'Certificate of Indigency'): ?>
            <p>TO WHOM IT MAY CONCERN:</p>
            <p>
                This is to certify that <span class="highlight"><?= htmlspecialchars($fullName) ?></span>,
                <?php if ($cert['civil_status']): ?>
                    <?= strtolower($cert['civil_status']) ?>,
                <?php endif; ?>
                a resident of
                <span class="highlight"><?= htmlspecialchars($cert['address'] ?: BARANGAY_NAME) ?></span>,
                <?= MUNICIPALITY ?>, <?= PROVINCE ?>,
                belongs to an <strong>indigent family</strong> in this barangay.
            </p>
            <p>
                The above-named person is known to be of limited financial means and is in need of assistance.
                This office attests to the indigency of the said person based on available records and personal knowledge.
            </p>
            <p>
                This certification is issued upon the request of the above-named person for the purpose of
                <span class="highlight"><?= htmlspecialchars($cert['purpose']) ?></span>
                and for whatever legal purpose it may serve.
            </p>
            <?php endif; ?>

            <p>
                Issued this <strong><?= $issuedDay ?></strong> day of <strong><?= $issuedMonth ?></strong>,
                <strong><?= $issuedYear ?></strong> at <?= BARANGAY_NAME ?>, <?= MUNICIPALITY ?>, <?= PROVINCE ?>.
            </p>
        </div>

        <!-- Signature -->
        <div class="cert-signature row mt-4">
            <div class="col-6 text-center">
                <div style="margin-top:50px">
                    <div class="cert-sig-line"></div>
                    <div class="fw-bold mt-1" style="font-size:0.9rem"><?= htmlspecialchars($captainName) ?></div>
                    <div style="font-size:0.8rem;color:#555">Punong Barangay</div>
                    <div style="font-size:0.8rem;color:#555"><?= BARANGAY_NAME ?></div>
                </div>
            </div>
            <div class="col-6 text-center">
                <div style="margin-top:10px">
                    <div style="font-size:0.8rem;color:#555;margin-bottom:4px">Applicant's Signature</div>
                    <div style="border-top:1px solid #333;width:200px;margin:0 auto;margin-top:40px"></div>
                    <div class="fw-bold mt-1" style="font-size:0.9rem"><?= htmlspecialchars($fullName) ?></div>
                    <div style="font-size:0.8rem;color:#555">Applicant</div>
                </div>
            </div>
        </div>

        <!-- Footer Note -->
        <div class="text-center mt-4" style="font-size:0.7rem;color:#888;border-top:1px solid #ddd;padding-top:8px">
            NOT VALID WITHOUT THE OFFICIAL SEAL OF <?= strtoupper(BARANGAY_NAME) ?>
            <?php if ($cert['amount'] > 0): ?>
            &nbsp;|&nbsp; Fee Paid: ₱<?= number_format($cert['amount'], 2) ?>
            <?php endif; ?>
        </div>

    </div><!-- /.cert-border -->
</div><!-- /.certificate-paper -->

<div class="text-center mt-3 no-print">
    <button onclick="window.print()" class="btn btn-primary">
        <i class="bi bi-printer me-1"></i> Print Certificate
    </button>
    <a href="issue.php" class="btn btn-outline-warning ms-2">
        <i class="bi bi-file-earmark-plus me-1"></i> Issue Another
    </a>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
