<?php
require_once __DIR__ . '/../../config/auth.php';
requireRole(['Admin', 'SK Official']);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../fpdf.php';

// Helper function to safely convert UTF-8 to windows-1252
function utf8_to_latin1($str) {
    return iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $str);
}

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
    die('Youth profile not found.');
}

// Resolve legacy field names
$homeAddress         = $y['home_address']           ?: ($y['address'] ?? '');
$eduAttainment       = $y['educational_attainment']  ?: ($y['educational_status'] ?? '');
$workStatus          = $y['work_status']              ?: ($y['employment_status'] ?? '');
$registeredSKVoter   = $y['registered_sk_voter']     ?: ($y['sk_voter'] ?? 'No');
$ageGroup            = $y['youth_age_group']          ?: '';

$fullName = $y['first_name'] . ' '
    . ($y['middle_name'] ? $y['middle_name'] . ' ' : '')
    . $y['last_name']
    . ($y['suffix'] ? ', ' . $y['suffix'] : '');

// Create PDF
class YouthProfilePDF extends FPDF {
    private $barangayName;
    private $municipality;
    private $province;
    
    function __construct($barangay, $municipality, $province) {
        parent::__construct();
        $this->barangayName = $barangay;
        $this->municipality = $municipality;
        $this->province = $province;
    }
    
    function Header() {
        // Logos
        if (file_exists('../../assets/img/barangay.png')) {
            $this->Image('../../assets/img/barangay.png', 62, 10, 18);
        }
        if (file_exists('../../assets/img/sk.png')) {
            $this->Image('../../assets/img/sk.png', 130, 10, 18);
        }
        
        // Header text
        $this->SetFont('Arial', '', 8);
        $this->SetY(30);
        $this->Cell(0, 3, 'Republic of the Philippines', 0, 1, 'C');
        $this->Cell(0, 3, utf8_to_latin1($this->province), 0, 1, 'C');
        $this->Cell(0, 3, utf8_to_latin1($this->municipality), 0, 1, 'C');
        
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 5, utf8_to_latin1($this->barangayName), 0, 1, 'C');
        
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(0, 4, 'KATIPUNAN NG KABATAAN', 0, 1, 'C');
        
        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor(26, 86, 219);
        $this->Cell(0, 5, 'YOUTH PROFILE FORM', 0, 1, 'C');
        $this->SetTextColor(0, 0, 0);
        
        // Tricolor line
        $this->SetFillColor(26, 86, 219);
        $this->Rect(10, $this->GetY(), 63.33, 1.5, 'F');
        $this->SetFillColor(224, 36, 36);
        $this->Rect(73.33, $this->GetY(), 63.33, 1.5, 'F');
        $this->SetFillColor(245, 158, 11);
        $this->Rect(136.66, $this->GetY(), 63.34, 1.5, 'F');
        
        $this->Ln(4);
    }
    
    function Footer() {
        $this->SetY(-12);
        $this->SetFont('Arial', 'I', 7);
        $this->Cell(0, 10, 'BMS v1.0 - Page ' . $this->PageNo(), 0, 0, 'C');
    }
    
    function SectionHeader($title) {
        $this->SetFillColor(26, 86, 219);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 6, utf8_to_latin1($title), 1, 1, 'L', true);
        $this->SetTextColor(0, 0, 0);
        $this->Ln(0.5);
    }
    
    function FieldRow($fields) {
        $startX = 10;
        $y = $this->GetY();
        
        // Draw labels
        $this->SetFont('Arial', 'B', 7.5);
        $this->SetTextColor(100, 100, 100);
        foreach ($fields as $field) {
            $this->SetXY($startX, $y);
            $this->Cell($field['width'], 2.5, utf8_to_latin1($field['label']), 0, 0, 'L');
            $startX += $field['width'];
        }
        
        // Draw underlines and values
        $this->SetY($y + 2.5);
        $startX = 10;
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(0, 0, 0);
        
        foreach ($fields as $field) {
            $this->SetXY($startX, $y + 2.5);
            $value = utf8_to_latin1(substr($field['value'], 0, 50));
            $this->Cell($field['width'], 4.5, $value, 0, 0, 'L');
            // Draw underline
            $this->Line($startX, $y + 7, $startX + $field['width'] - 1, $y + 7);
            $startX += $field['width'];
        }
        
        $this->SetY($y + 8.5);
    }
    
    function CheckboxField($label, $checked) {
        $this->SetFont('Arial', '', 9);
        $x = $this->GetX();
        $y = $this->GetY();
        
        // Checkbox
        $this->Rect($x, $y, 4, 4);
        if ($checked) {
            $this->SetFont('Arial', 'B', 12);
            $this->Text($x + 0.5, $y + 3.2, utf8_to_latin1('v'));
        }
        
        // Label
        $this->SetFont('Arial', '', 9);
        $this->SetXY($x + 5, $y);
        $this->Cell(0, 4, utf8_to_latin1($label), 0, 0);
    }
}

// Create PDF instance
$pdf = new YouthProfilePDF(BARANGAY_NAME, MUNICIPALITY, PROVINCE);
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 15);

// I. PROFILE section starts here
$sectionStartY = $pdf->GetY(); // This will be after header (around 55mm now)

// Photo box - positioned at top right corner of the form
$photoBoxX = 163;
$photoBoxY = $sectionStartY + 5;
$photoBoxWidth = 32;
$photoBoxHeight = 35;
$pdf->Rect($photoBoxX, $photoBoxY, $photoBoxWidth, $photoBoxHeight);
$pdf->SetFont('Arial', 'I', 7);
$pdf->SetXY($photoBoxX, $photoBoxY + ($photoBoxHeight / 2) - 1);
$pdf->Cell($photoBoxWidth, 3, '2x2 PHOTO', 0, 0, 'C');

// I. PROFILE
$pdf->SetY($sectionStartY);
$pdf->SectionHeader('I. PROFILE');
$pdf->FieldRow([
    ['label' => 'Last Name', 'value' => $y['last_name'], 'width' => 40],
    ['label' => 'First Name', 'value' => $y['first_name'], 'width' => 40],
    ['label' => 'Middle Name', 'value' => $y['middle_name'] ?? '', 'width' => 35],
    ['label' => 'Suffix', 'value' => $y['suffix'] ?? '', 'width' => 15],
]);

$pdf->FieldRow([
    ['label' => 'Region', 'value' => $y['region'] ?? '', 'width' => 18],
    ['label' => 'Province', 'value' => $y['province'] ?? '', 'width' => 32],
    ['label' => 'City/Municipality', 'value' => $y['city_municipality'] ?? '', 'width' => 35],
    ['label' => 'Barangay', 'value' => $y['barangay'] ?? '', 'width' => 30],
    ['label' => 'Purok', 'value' => $y['purok'] ?? '', 'width' => 15],
]);

$pdf->FieldRow([
    ['label' => 'Home Address', 'value' => $homeAddress, 'width' => 130],
]);

$pdf->FieldRow([
    ['label' => 'Birthdate', 'value' => date('F d, Y', strtotime($y['birthdate'])), 'width' => 45],
    ['label' => 'Age', 'value' => $y['current_age'], 'width' => 15],
    ['label' => 'Civil Status', 'value' => $y['civil_status'] ?? '', 'width' => 40],
]);

// Sex checkboxes
$pdf->SetFont('Arial', 'B', 7.5);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(0, 2.5, 'Sex', 0, 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 9);

$y_pos = $pdf->GetY();
$pdf->CheckboxField('Male', $y['gender'] === 'Male');
$pdf->SetXY(40, $y_pos);
$pdf->CheckboxField('Female', $y['gender'] === 'Female');
$pdf->Ln(7);

$pdf->FieldRow([
    ['label' => 'Email Address', 'value' => $y['email'] ?? '', 'width' => 65],
    ['label' => 'Contact Number', 'value' => $y['contact'] ?? '', 'width' => 65],
]);

// II. DEMOGRAPHIC CHARACTERISTICS
$pdf->SectionHeader('II. DEMOGRAPHIC CHARACTERISTICS');
$pdf->FieldRow([
    ['label' => 'Youth Age Group', 'value' => $ageGroup, 'width' => 60],
    ['label' => 'Youth Classification', 'value' => $y['youth_classification'] ?? '', 'width' => 85],
]);

$pdf->FieldRow([
    ['label' => 'Educational Attainment', 'value' => $eduAttainment, 'width' => 60],
    ['label' => 'School / University', 'value' => $y['school_name'] ?? '', 'width' => 85],
]);

$pdf->FieldRow([
    ['label' => 'Work Status', 'value' => $workStatus, 'width' => 60],
    ['label' => 'Occupation / Course', 'value' => $y['occupation'] ?? '', 'width' => 85],
]);

// III. VOTER & KK ASSEMBLY PARTICIPATION
$pdf->SectionHeader('III. VOTER & KK ASSEMBLY PARTICIPATION');

$pdf->SetFont('Arial', 'B', 7.5);
$pdf->SetTextColor(100, 100, 100);
$y_pos = $pdf->GetY();
$pdf->Text(10, $y_pos + 2.5, 'Registered SK Voter');
$pdf->Text(80, $y_pos + 2.5, 'Voted Last SK Election');
$pdf->SetTextColor(0, 0, 0);
$pdf->SetY($y_pos + 3.5);

$y_pos = $pdf->GetY();
$pdf->SetXY(10, $y_pos);
$pdf->CheckboxField('Yes', $registeredSKVoter === 'Yes');
$pdf->SetXY(32, $y_pos);
$pdf->CheckboxField('No', $registeredSKVoter !== 'Yes');
$pdf->SetXY(80, $y_pos);
$pdf->CheckboxField('Yes', ($y['voted_last_sk_election'] ?? 'No') === 'Yes');
$pdf->SetXY(102, $y_pos);
$pdf->CheckboxField('No', ($y['voted_last_sk_election'] ?? 'No') !== 'Yes');
$pdf->Ln(7);

$pdf->SetFont('Arial', 'B', 7.5);
$pdf->SetTextColor(100, 100, 100);
$y_pos = $pdf->GetY();
$pdf->Text(10, $y_pos + 2.5, 'Registered National Voter');
$pdf->Text(80, $y_pos + 2.5, 'Attended KK Assembly');
$pdf->SetTextColor(0, 0, 0);
$pdf->SetY($y_pos + 3.5);

$y_pos = $pdf->GetY();
$pdf->SetXY(10, $y_pos);
$pdf->CheckboxField('Yes', ($y['registered_national_voter'] ?? 'No') === 'Yes');
$pdf->SetXY(32, $y_pos);
$pdf->CheckboxField('No', ($y['registered_national_voter'] ?? 'No') !== 'Yes');
$pdf->SetXY(80, $y_pos);
$pdf->CheckboxField('Yes', ($y['attended_kk_assembly'] ?? 'No') === 'Yes');
$pdf->SetXY(102, $y_pos);
$pdf->CheckboxField('No', ($y['attended_kk_assembly'] ?? 'No') !== 'Yes');
$pdf->Ln(7);

// IV. SKILLS & INTERESTS
if (!empty($y['skills']) || !empty($y['interests'])) {
    $pdf->SectionHeader('IV. SKILLS & INTERESTS');
    $pdf->FieldRow([
        ['label' => 'Skills / Talents', 'value' => $y['skills'] ?? '', 'width' => 145],
    ]);
    $pdf->FieldRow([
        ['label' => 'Interests / Hobbies', 'value' => $y['interests'] ?? '', 'width' => 145],
    ]);
}

// V. EMERGENCY CONTACT
$pdf->SectionHeader('V. EMERGENCY CONTACT');
$pdf->FieldRow([
    ['label' => 'Contact Person Name', 'value' => $y['emergency_contact_name'] ?? '', 'width' => 95],
    ['label' => 'Contact Number', 'value' => $y['emergency_contact_number'] ?? '', 'width' => 50],
]);

// Signature Section
$pdf->Ln(6);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(0, 4, '_________________________________________', 0, 1, 'C');
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 4, utf8_to_latin1($fullName), 0, 1, 'C');
$pdf->SetFont('Arial', 'I', 8);
$pdf->Cell(0, 3, 'Youth Signature Over Printed Name', 0, 1, 'C');
$pdf->Ln(1);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(0, 3, 'Date: ' . date('F d, Y'), 0, 1, 'C');

// Footer note
$pdf->Ln(3);
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(0, 3, 'CERTIFIED TRUE AND CORRECT', 0, 1, 'C');
$pdf->SetFont('Arial', '', 7.5);
$pdf->Cell(0, 3, utf8_to_latin1('This profile was generated from ' . BARANGAY_NAME . ' KK Management System'), 0, 1, 'C');
$pdf->Cell(0, 3, 'Control No.: ' . str_pad($y['id'], 6, '0', STR_PAD_LEFT) . ' | Generated: ' . date('F d, Y h:i A'), 0, 1, 'C');

// Output PDF
$filename = 'KK_Youth_Profile_' . str_replace(' ', '_', $fullName) . '.pdf';
$pdf->Output('D', $filename);
?>
