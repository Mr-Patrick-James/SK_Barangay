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
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . sanitizeFilename($title) . '.html"');
    
    echo '<!DOCTYPE html><html><head>';
    echo '<meta charset="UTF-8">';
    echo '<title>' . htmlspecialchars($title) . '</title>';
    echo '<style>';
    echo 'body { font-family: Arial, sans-serif; margin: 20px; }';
    echo 'h1 { text-align: center; color: #1a56db; border-bottom: 3px solid #f59e0b; padding-bottom: 10px; }';
    echo 'table { width: 100%; border-collapse: collapse; margin-top: 20px; }';
    echo 'th { background: #1a56db; color: white; padding: 12px; text-align: left; font-weight: bold; }';
    echo 'td { padding: 10px; border-bottom: 1px solid #ddd; }';
    echo 'tr:nth-child(even) { background: #f9f9f9; }';
    echo 'tr:hover { background: #f0f0f0; }';
    echo '.header { margin-bottom: 20px; color: #666; font-size: 14px; }';
    echo '.footer { margin-top: 30px; text-align: center; color: #999; font-size: 12px; }';
    echo '</style>';
    echo '</head><body>';
    
    echo '<h1>' . htmlspecialchars($title) . '</h1>';
    echo '<div class="header">';
    echo '<p><strong>Barangay:</strong> ' . htmlspecialchars(BARANGAY_NAME) . '</p>';
    echo '<p><strong>Municipality:</strong> ' . htmlspecialchars(MUNICIPALITY) . '</p>';
    echo '<p><strong>Generated:</strong> ' . date('F d, Y h:i A') . '</p>';
    echo '</div>';
    
    echo '<table><thead><tr>';
    foreach ($headers as $header) {
        echo '<th>' . htmlspecialchars($header) . '</th>';
    }
    echo '</tr></thead><tbody>';
    
    foreach ($data as $row) {
        echo '<tr>';
        foreach ($row as $value) {
            echo '<td>' . htmlspecialchars($value ?? '—') . '</td>';
        }
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    echo '<div class="footer"><p>This document was generated from ' . BARANGAY_NAME . ' Management System</p></div>';
    echo '</body></html>';
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
    echo "Generated: " . date('F d, Y h:i A') . "\n\n";
    
    // Column headers
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
    // Create a simple HTML that can be saved as DOCX
    // Modern Word can open and convert HTML to DOCX format
    $html = '<!DOCTYPE html>';
    $html .= '<html><head><meta charset="UTF-8"><title>' . htmlspecialchars($title) . '</title>';
    $html .= '<style>';
    $html .= 'body { font-family: Calibri, Arial; margin: 1in; }';
    $html .= 'h1 { color: #1a56db; text-align: center; }';
    $html .= 'table { width: 100%; border-collapse: collapse; margin-top: 20px; }';
    $html .= 'th { background: #1a56db; color: white; padding: 10px; text-align: left; }';
    $html .= 'td { padding: 8px; border: 1px solid #ddd; }';
    $html .= '.info { color: #666; margin-bottom: 20px; }';
    $html .= '</style></head><body>';
    
    $html .= '<h1>' . htmlspecialchars($title) . '</h1>';
    $html .= '<div class="info">';
    $html .= '<p><strong>Barangay:</strong> ' . htmlspecialchars(BARANGAY_NAME) . '</p>';
    $html .= '<p><strong>Municipality:</strong> ' . htmlspecialchars(MUNICIPALITY) . '</p>';
    $html .= '<p><strong>Generated:</strong> ' . date('F d, Y h:i A') . '</p>';
    $html .= '</div>';
    
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
    
    $html .= '</table></body></html>';
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . sanitizeFilename($title) . '.docx"');
    header('Content-Length: ' . strlen($html));
    
    echo $html;
}

function sanitizeFilename($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $filename);
    return substr($filename, 0, 100);
}
