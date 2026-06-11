<?php
require_once __DIR__ . '/../../config/auth.php';
requireLogin();
require_once __DIR__ . '/../../config/database.php';

$db = getDB();
$type = $_GET['type'] ?? '';
$format = $_GET['format'] ?? 'pdf';

// Fetch data based on type
$data = [];
$title = '';
$headers = [];

switch ($type) {
    case 'residents':
        $title = 'Barangay Residents List';
        $data = $db->query("SELECT first_name, middle_name, last_name, birthdate, gender, civil_status, address, contact FROM residents ORDER BY last_name, first_name")->fetchAll();
        $headers = ['First Name', 'Middle Name', 'Last Name', 'Birthdate', 'Gender', 'Civil Status', 'Address', 'Contact'];
        break;

    case 'officials':
        $title = 'Barangay Officials List';
        $data = $db->query("SELECT first_name, middle_name, last_name, position, term_start, term_end, contact, status FROM officials ORDER BY position, last_name")->fetchAll();
        $headers = ['First Name', 'Middle Name', 'Last Name', 'Position', 'Term Start', 'Term End', 'Contact', 'Status'];
        break;

    case 'kk_youth':
        $title = 'KK Youth Profiling List';
        $stmt = $db->query("SELECT first_name, middle_name, last_name, birthdate, gender, youth_classification, educational_status, employment_status, sk_voter FROM kk_youth ORDER BY last_name, first_name");
        $data = $stmt->fetchAll();
        $headers = ['First Name', 'Middle Name', 'Last Name', 'Birthdate', 'Gender', 'Classification', 'Education', 'Employment', 'SK Voter'];
        break;

    case 'activities':
        $title = 'Activities List';
        $data = $db->query("SELECT title, description, activity_date, status FROM activities ORDER BY activity_date DESC")->fetchAll();
        $headers = ['Title', 'Description', 'Date', 'Status'];
        break;

    case 'users':
        $title = 'User Management List';
        $data = $db->query("SELECT full_name, username, role, created_at FROM users ORDER BY role, full_name")->fetchAll();
        $headers = ['Full Name', 'Username', 'Role', 'Created At'];
        break;

    case 'certificates':
        $title = 'Certificates Issued List';
        $data = $db->query("SELECT c.id, COALESCE(r.first_name || ' ' || r.last_name, 'N/A') as resident_name, c.cert_type, c.purpose, c.or_number, c.amount, c.issued_at FROM certificates c LEFT JOIN residents r ON c.resident_id = r.id ORDER BY c.issued_at DESC")->fetchAll();
        $headers = ['ID', 'Resident Name', 'Certificate Type', 'Purpose', 'OR Number', 'Amount', 'Issued Date'];
        break;

    default:
        http_response_code(400);
        die('Invalid export type');
}

if (empty($data)) {
    http_response_code(404);
    die('No data to export');
}

// Export based on format
switch ($format) {
    case 'pdf':
        exportToPDF($title, $headers, $data);
        break;
    case 'excel':
        exportToExcel($title, $headers, $data);
        break;
    case 'docx':
        exportToDocx($title, $headers, $data);
        break;
    default:
        http_response_code(400);
        die('Invalid export format');
}

function exportToPDF($title, $headers, $data) {
    require_once __DIR__ . '/../../fpdf.php';
    
    // Helper function to safely convert UTF-8 to windows-1252
    function utf8_to_latin1($str) {
        return iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $str);
    }
    
    $barangayName = BARANGAY_NAME;
    $municipality = MUNICIPALITY;
    $province = PROVINCE;
    
    // Create PDF
    $pdf = new FPDF('L', 'mm', 'A4'); // Landscape for tables
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(true, 15);
    
    // Header with logos
    if (file_exists(__DIR__ . '/../../assets/img/barangay.png')) {
        $pdf->Image(__DIR__ . '/../../assets/img/barangay.png', 20, 10, 20);
    }
    if (file_exists(__DIR__ . '/../../assets/img/sk.png')) {
        $pdf->Image(__DIR__ . '/../../assets/img/sk.png', 247, 10, 20);
    }
    
    // Title section
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetTextColor(26, 86, 219);
    $pdf->Cell(0, 8, utf8_to_latin1($barangayName), 0, 1, 'C');
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 5, utf8_to_latin1($municipality . ' | ' . $province), 0, 1, 'C');
    
    // Tricolor line
    $pdf->SetFillColor(26, 86, 219);
    $pdf->Rect(10, $pdf->GetY(), 93.33, 2, 'F');
    $pdf->SetFillColor(224, 36, 36);
    $pdf->Rect(103.33, $pdf->GetY(), 93.33, 2, 'F');
    $pdf->SetFillColor(245, 158, 11);
    $pdf->Rect(196.66, $pdf->GetY(), 93.34, 2, 'F');
    $pdf->Ln(5);
    
    // Document title
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(26, 86, 219);
    $pdf->Cell(0, 8, utf8_to_latin1($title), 0, 1, 'C');
    
    // Info bar
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(240, 244, 251);
    $pdf->Cell(0, 6, 'Records: ' . count($data) . ' | Generated: ' . date('F d, Y h:i A'), 0, 1, 'C', true);
    $pdf->Ln(3);
    
    // Table header
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetFillColor(26, 86, 219);
    $pdf->SetTextColor(255, 255, 255);
    
    // Calculate column widths
    $pageWidth = 277; // A4 landscape width minus margins
    $colWidth = $pageWidth / count($headers);
    if ($colWidth < 20) $colWidth = 20; // Minimum width
    
    foreach ($headers as $header) {
        $pdf->Cell($colWidth, 7, utf8_to_latin1($header), 1, 0, 'C', true);
    }
    $pdf->Ln();
    
    // Table data
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(249, 249, 249);
    
    $fill = false;
    foreach ($data as $row) {
        foreach ($row as $value) {
            $pdf->Cell($colWidth, 6, utf8_to_latin1(substr($value ?? '', 0, 30)), 1, 0, 'L', $fill);
        }
        $pdf->Ln();
        $fill = !$fill;
    }
    
    // Footer
    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->SetTextColor(150, 150, 150);
    $pdf->Cell(0, 5, utf8_to_latin1('Generated from ' . $barangayName . ' Management System | BMS v1.0 © ' . date('Y')), 0, 1, 'C');
    
    // Output PDF
    $filename = sanitizeFilename($title) . '.pdf';
    $pdf->Output('D', $filename);
}

function exportToExcel($title, $headers, $data) {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . sanitizeFilename($title) . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    
    // Header info
    echo htmlspecialchars($title) . "\n";
    echo "Barangay: " . htmlspecialchars(BARANGAY_NAME) . "\n";
    echo "Municipality: " . htmlspecialchars(MUNICIPALITY) . "\n";
    echo "Province: " . htmlspecialchars(PROVINCE) . "\n";
    echo "Generated: " . date('F d, Y h:i A') . "\n";
    echo "Records: " . count($data) . "\n\n";
    
    // Column headers with formatting
    echo implode("\t", array_map('htmlspecialchars', $headers)) . "\n";
    
    // Data rows
    foreach ($data as $row) {
        $values = [];
        foreach ($row as $value) {
            $values[] = htmlspecialchars($value ?? '—');
        }
        echo implode("\t", $values) . "\n";
    }
}

function exportToDocx($title, $headers, $data) {
    // Create modern HTML for Word
    $html = '<!DOCTYPE html>';
    $html .= '<html><head><meta charset="UTF-8"><title>' . htmlspecialchars($title) . '</title>';
    $html .= '<style>';
    $html .= 'body { font-family: Calibri, Arial; margin: 1in; color: #333; }';
    $html .= '.header { display: flex; align-items: center; gap: 20px; border-bottom: 3px solid; border-image: linear-gradient(to right, #1a56db, #e02424, #f59e0b) 1; padding-bottom: 15px; margin-bottom: 20px; }';
    $html .= '.logo { width: 70px; height: 70px; }';
    $html .= '.header-text h1 { margin: 0; color: #1a56db; font-size: 22px; }';
    $html .= '.header-text p { margin: 3px 0; color: #666; font-size: 12px; }';
    $html .= 'h2 { color: #1a56db; text-align: center; margin-top: 30px; }';
    $html .= '.info { background: #f0f4fb; padding: 12px; margin: 15px 0; border-radius: 4px; font-size: 12px; }';
    $html .= 'table { width: 100%; border-collapse: collapse; margin-top: 20px; }';
    $html .= 'th { background: #1a56db; color: white; padding: 10px; text-align: left; font-weight: bold; border: 1px solid #999; }';
    $html .= 'td { padding: 8px; border: 1px solid #ccc; font-size: 11px; }';
    $html .= 'tr:nth-child(even) { background: #f9f9f9; }';
    $html .= '.footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid #ddd; text-align: center; font-size: 11px; color: #999; }';
    $html .= '</style></head><body>';
    
    // Header with logos (as base64)
    $html .= '<div class="header">';
    $html .= '<img src="data:image/png;base64,' . getBase64Image('assets/img/barangay.png') . '" alt="Barangay Logo" class="logo" style="width:70px;height:70px;">';
    $html .= '<img src="data:image/png;base64,' . getBase64Image('assets/img/sk.png') . '" alt="SK Logo" class="logo" style="width:70px;height:70px;">';
    $html .= '<div class="header-text">';
    $html .= '<h1>' . htmlspecialchars(BARANGAY_NAME) . '</h1>';
    $html .= '<p><strong>' . htmlspecialchars(MUNICIPALITY) . '</strong></p>';
    $html .= '<p>' . htmlspecialchars(PROVINCE) . '</p>';
    $html .= '</div></div>';
    
    // Info box
    $html .= '<div class="info">';
    $html .= '<strong>Document:</strong> ' . htmlspecialchars($title) . ' | ';
    $html .= '<strong>Records:</strong> ' . count($data) . ' | ';
    $html .= '<strong>Generated:</strong> ' . date('F d, Y h:i A');
    $html .= '</div>';
    
    // Title
    $html .= '<h2>' . htmlspecialchars($title) . '</h2>';
    
    // Table
    $html .= '<table><tr>';
    foreach ($headers as $header) {
        $html .= '<th>' . htmlspecialchars($header) . '</th>';
    }
    $html .= '</tr>';
    
    foreach ($data as $row) {
        $html .= '<tr>';
        foreach ($row as $value) {
            $html .= '<td>' . htmlspecialchars($value ?? '—') . '</td>';
        }
        $html .= '</tr>';
    }
    
    $html .= '</table>';
    
    // Footer
    $html .= '<div class="footer">';
    $html .= '<p>This document was generated from ' . htmlspecialchars(BARANGAY_NAME) . ' Management System</p>';
    $html .= '<p>BMS v1.0 © ' . date('Y') . ' All Rights Reserved</p>';
    $html .= '</div>';
    
    $html .= '</body></html>';
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . sanitizeFilename($title) . '.docx"');
    header('Content-Length: ' . strlen($html));
    
    echo $html;
}

function sanitizeFilename($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $filename);
    return substr($filename, 0, 100);
}

function getBase64Image($imagePath) {
    $fullPath = __DIR__ . '/../../' . $imagePath;
    if (file_exists($fullPath)) {
        $imageData = file_get_contents($fullPath);
        return base64_encode($imageData);
    }
    return '';
}
