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
    $barangayName = BARANGAY_NAME;
    $municipality = MUNICIPALITY;
    $province = PROVINCE;
    
    // Generate HTML content
    $html = '<!DOCTYPE html><html><head>';
    $html .= '<meta charset="UTF-8">';
    $html .= '<title>' . htmlspecialchars($title) . '</title>';
    $html .= '<style>';
    $html .= 'body { font-family: "Segoe UI", Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }';
    $html .= '.container { max-width: 1200px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }';
    $html .= '.header { display: flex; align-items: center; justify-content: space-between; border-bottom: 4px solid; border-image: linear-gradient(to right, #1a56db 33.3%, #e02424 33.3% 66.6%, #f59e0b 66.6%) 1; padding-bottom: 20px; margin-bottom: 30px; }';
    $html .= '.logos { display: flex; gap: 15px; align-items: center; }';
    $html .= '.logo-img { width: 70px; height: 70px; object-fit: contain; }';
    $html .= '.header-text h1 { margin: 0; color: #1a56db; font-size: 24px; font-weight: bold; }';
    $html .= '.header-text p { margin: 5px 0; color: #666; font-size: 14px; }';
    $html .= '.info-bar { display: flex; justify-content: space-between; background: linear-gradient(to right, #f0f4fb 0%, #fef3c7 50%, #fee2e2 100%); padding: 15px 20px; border-radius: 6px; margin-bottom: 30px; font-size: 13px; }';
    $html .= '.info-bar div { color: #333; }';
    $html .= 'table { width: 100%; border-collapse: collapse; margin-top: 20px; }';
    $html .= 'th { background: linear-gradient(to right, #1a56db, #1240a8); color: white; padding: 14px; text-align: left; font-weight: 600; border: 1px solid #ddd; font-size: 13px; }';
    $html .= 'td { padding: 12px 14px; border: 1px solid #e0e0e0; font-size: 13px; color: #333; }';
    $html .= 'tr:nth-child(even) { background: #f9f9f9; }';
    $html .= 'tr:hover { background: #f0f8ff; }';
    $html .= 'h2 { color: #1a56db; margin: 0 0 10px 0; font-size: 20px; }';
    $html .= '.page-title { color: #1a56db; text-align: center; font-size: 22px; font-weight: bold; margin-bottom: 10px; }';
    $html .= '.footer { margin-top: 40px; padding-top: 20px; border-top: 2px solid #e0e0e0; text-align: center; color: #999; font-size: 12px; }';
    $html .= '.timestamp { color: #666; font-size: 12px; }';
    $html .= '@media print { body { background: white; } }';
    $html .= '</style>';
    $html .= '</head><body>';
    
    $html .= '<div class="container">';
    
    // Header with logos
    $html .= '<div class="header">';
    $html .= '<div class="logos">';
    $html .= '<img src="data:image/png;base64,' . getBase64Image('assets/img/barangay.png') . '" alt="Barangay Logo" class="logo-img">';
    $html .= '<img src="data:image/png;base64,' . getBase64Image('assets/img/sk.png') . '" alt="SK Logo" class="logo-img">';
    $html .= '</div>';
    $html .= '<div class="header-text">';
    $html .= '<h1>' . htmlspecialchars($barangayName) . '</h1>';
    $html .= '<p><strong>' . htmlspecialchars($municipality) . '</strong></p>';
    $html .= '<p>' . htmlspecialchars($province) . '</p>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Info bar
    $html .= '<div class="info-bar">';
    $html .= '<div><strong>Document:</strong> ' . htmlspecialchars($title) . '</div>';
    $html .= '<div><strong>Records:</strong> ' . count($data) . '</div>';
    $html .= '<div class="timestamp"><strong>Generated:</strong> ' . date('F d, Y h:i A') . '</div>';
    $html .= '</div>';
    
    // Title
    $html .= '<h2 class="page-title">' . htmlspecialchars($title) . '</h2>';
    
    // Table
    $html .= '<table>';
    $html .= '<thead><tr>';
    foreach ($headers as $header) {
        $html .= '<th>' . htmlspecialchars($header) . '</th>';
    }
    $html .= '</tr></thead>';
    $html .= '<tbody>';
    
    foreach ($data as $row) {
        $html .= '<tr>';
        foreach ($row as $value) {
            $html .= '<td>' . htmlspecialchars($value ?? '—') . '</td>';
        }
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table>';
    
    // Footer
    $html .= '<div class="footer">';
    $html .= '<p>This document was generated from ' . htmlspecialchars($barangayName) . ' Management System</p>';
    $html .= '<p style="margin-top: 10px; color: #bbb; font-size: 11px;">BMS v1.0 © ' . date('Y') . ' All Rights Reserved</p>';
    $html .= '</div>';
    
    $html .= '</div></body></html>';
    
    // Output as HTML that can be printed/saved as PDF
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    generateSimplePDF($html, $title);
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

/**
 * Generate print-friendly HTML that can be saved as PDF
 * Users can save using browser's Print → Save as PDF feature
 * This works offline without external dependencies
 */
function generateSimplePDF($html, $title) {
    ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        @media screen {
            body {
                background: #e8e8e8;
                padding: 20px;
                font-family: 'Segoe UI', Arial, sans-serif;
            }
            .print-toolbar {
                background: #2c3e50;
                color: white;
                padding: 15px;
                border-radius: 4px 4px 0 0;
                display: flex;
                justify-content: space-between;
                align-items: center;
                max-width: 1000px;
                margin: 0 auto 10px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            }
            .print-toolbar h2 {
                font-size: 16px;
                margin: 0;
            }
            .print-toolbar button {
                background: #3498db;
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
                margin-left: 10px;
                transition: background 0.3s;
            }
            .print-toolbar button:hover {
                background: #2980b9;
            }
            .pdf-document {
                background: white;
                max-width: 1000px;
                margin: 0 auto;
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
                padding: 20px;
            }
        }
        
        @page {
            size: A4;
            margin: 0.5in;
        }
        
        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            body {
                background: white;
                margin: 0;
                padding: 0;
            }
            .print-toolbar {
                display: none !important;
            }
            .pdf-document {
                max-width: 100%;
                margin: 0;
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="print-toolbar">
        <h2>📄 <?= htmlspecialchars($title) ?></h2>
        <button onclick="window.print()">🖨️ Print / Save as PDF</button>
    </div>
    <div class="pdf-document">
        <?= $html ?>
    </div>
    <script>
        // Show instruction for first-time users
        if (!localStorage.getItem('pdfHelpShown')) {
            setTimeout(() => {
                alert('💡 To save as PDF:\n1. Click "Print / Save as PDF"\n2. Select "Save as PDF" from the printer dropdown\n3. Click Save');
                localStorage.setItem('pdfHelpShown', '1');
            }, 500);
        }
    </script>
</body>
</html>
    <?php
}

