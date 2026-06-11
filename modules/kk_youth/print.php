<?php
require_once __DIR__ . '/../../config/auth.php';
requireRole(['Admin', 'SK Official']);
require_once __DIR__ . '/../../config/database.php';

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
$homeAddress         = $y['home_address']           ?: ($y['address'] ?? '—');
$eduAttainment       = $y['educational_attainment']  ?: ($y['educational_status'] ?? '—');
$workStatus          = $y['work_status']              ?: ($y['employment_status'] ?? '—');
$registeredSKVoter   = $y['registered_sk_voter']     ?: ($y['sk_voter'] ?? 'No');
$ageGroup            = $y['youth_age_group']          ?: '—';

$fullName = htmlspecialchars(
    $y['first_name'] . ' '
    . ($y['middle_name'] ? $y['middle_name'] . ' ' : '')
    . $y['last_name']
    . ($y['suffix'] ? ', ' . $y['suffix'] : '')
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KK Youth Profile Form - <?= $fullName ?></title>
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
                font-family: Arial, sans-serif;
            }
            .print-toolbar {
                background: #2c3e50;
                color: white;
                padding: 15px;
                border-radius: 4px 4px 0 0;
                display: flex;
                justify-content: space-between;
                align-items: center;
                max-width: 900px;
                margin: 0 auto 0;
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
            }
            .print-toolbar button:hover {
                background: #2980b9;
            }
            .form-container {
                background: white;
                max-width: 900px;
                margin: 0 auto;
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
                padding: 40px;
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
            .form-container {
                max-width: 100%;
                margin: 0;
                padding: 20px;
                box-shadow: none;
            }
        }
        
        /* Form Styles */
        .form-container {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            color: #333;
        }
        
        .form-header {
            text-align: center;
            border-bottom: 4px solid;
            border-image: linear-gradient(to right, #1a56db 33.3%, #e02424 33.3% 66.6%, #f59e0b 66.6%) 1;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        
        .form-header .logos {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 10px;
        }
        
        .form-header .logo {
            width: 60px;
            height: 60px;
        }
        
        .form-header h1 {
            font-size: 18pt;
            color: #1a56db;
            margin: 5px 0;
        }
        
        .form-header .subtitle {
            font-size: 10pt;
            color: #666;
            margin: 2px 0;
        }
        
        .section {
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .section-header {
            background: linear-gradient(to right, #1a56db, #1240a8);
            color: white;
            padding: 8px 12px;
            font-weight: bold;
            font-size: 11pt;
        }
        
        .section-body {
            padding: 15px;
        }
        
        .field-row {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }
        
        .field {
            flex: 1;
            min-width: 150px;
            margin-right: 15px;
            margin-bottom: 8px;
        }
        
        .field:last-child {
            margin-right: 0;
        }
        
        .field-label {
            font-size: 9pt;
            color: #666;
            font-weight: 600;
            margin-bottom: 3px;
            display: block;
        }
        
        .field-value {
            font-size: 10pt;
            color: #000;
            border-bottom: 1px solid #333;
            padding: 3px 0;
            min-height: 20px;
            font-weight: 500;
        }
        
        .checkbox {
            display: inline-block;
            width: 15px;
            height: 15px;
            border: 2px solid #333;
            margin-right: 5px;
            vertical-align: middle;
            position: relative;
        }
        
        .checkbox.checked::after {
            content: '✓';
            position: absolute;
            top: -4px;
            left: 2px;
            font-size: 16px;
            font-weight: bold;
            color: #1a56db;
        }
        
        .photo-box {
            width: 120px;
            height: 140px;
            border: 2px solid #333;
            float: right;
            margin-left: 15px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5f5f5;
            font-size: 9pt;
            color: #999;
        }
        
        .signature-box {
            margin-top: 40px;
            text-align: center;
        }
        
        .signature-line {
            border-top: 1px solid #333;
            width: 250px;
            margin: 40px auto 5px;
        }
        
        .footer-note {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #ddd;
            font-size: 9pt;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="print-toolbar">
    <h2>📄 KK Youth Profile Form - <?= $fullName ?></h2>
    <div>
        <button onclick="window.print()">🖨️ Print Form</button>
        <button onclick="window.close()">✕ Close</button>
    </div>
</div>

<div class="form-container">
    
    <!-- Header -->
    <div class="form-header">
        <div class="logos">
            <img src="../../assets/img/barangay.png" alt="Barangay Logo" class="logo">
            <img src="../../assets/img/sk.png" alt="SK Logo" class="logo">
        </div>
        <div class="subtitle">Republic of the Philippines</div>
        <div class="subtitle"><?= PROVINCE ?></div>
        <div class="subtitle"><?= MUNICIPALITY ?></div>
        <h1><?= BARANGAY_NAME ?></h1>
        <div class="subtitle" style="font-weight: bold; margin-top: 8px;">KATIPUNAN NG KABATAAN</div>
        <div class="subtitle" style="font-weight: bold; font-size: 12pt; color: #1a56db; margin-top: 5px;">YOUTH PROFILE FORM</div>
    </div>
    
    <!-- Photo Box -->
    <div class="photo-box">
        2x2 PHOTO<br>
        <small>(Paste Here)</small>
    </div>
    
    <!-- I. PROFILE -->
    <div class="section">
        <div class="section-header">I. PROFILE</div>
        <div class="section-body">
            <div class="field-row">
                <div class="field" style="flex: 2;">
                    <span class="field-label">Last Name</span>
                    <div class="field-value"><?= htmlspecialchars($y['last_name']) ?></div>
                </div>
                <div class="field" style="flex: 2;">
                    <span class="field-label">First Name</span>
                    <div class="field-value"><?= htmlspecialchars($y['first_name']) ?></div>
                </div>
                <div class="field" style="flex: 2;">
                    <span class="field-label">Middle Name</span>
                    <div class="field-value"><?= htmlspecialchars($y['middle_name'] ?? '') ?></div>
                </div>
                <div class="field" style="flex: 0.5;">
                    <span class="field-label">Suffix</span>
                    <div class="field-value"><?= htmlspecialchars($y['suffix'] ?? '') ?></div>
                </div>
            </div>
            
            <div class="field-row">
                <div class="field" style="flex: 0.5;">
                    <span class="field-label">Region</span>
                    <div class="field-value"><?= htmlspecialchars($y['region'] ?? '') ?></div>
                </div>
                <div class="field">
                    <span class="field-label">Province</span>
                    <div class="field-value"><?= htmlspecialchars($y['province'] ?? '') ?></div>
                </div>
                <div class="field">
                    <span class="field-label">City / Municipality</span>
                    <div class="field-value"><?= htmlspecialchars($y['city_municipality'] ?? '') ?></div>
                </div>
                <div class="field">
                    <span class="field-label">Barangay</span>
                    <div class="field-value"><?= htmlspecialchars($y['barangay'] ?? '') ?></div>
                </div>
                <div class="field" style="flex: 0.5;">
                    <span class="field-label">Purok</span>
                    <div class="field-value"><?= htmlspecialchars($y['purok'] ?? '') ?></div>
                </div>
            </div>
            
            <div class="field-row">
                <div class="field" style="flex: 3;">
                    <span class="field-label">Home Address</span>
                    <div class="field-value"><?= htmlspecialchars($homeAddress) ?></div>
                </div>
            </div>
            
            <div class="field-row">
                <div class="field">
                    <span class="field-label">Birthdate</span>
                    <div class="field-value"><?= date('F d, Y', strtotime($y['birthdate'])) ?></div>
                </div>
                <div class="field">
                    <span class="field-label">Age</span>
                    <div class="field-value"><?= $y['current_age'] ?></div>
                </div>
                <div class="field">
                    <span class="field-label">Sex</span>
                    <div class="field-value">
                        <span class="checkbox <?= $y['gender'] === 'Male' ? 'checked' : '' ?>"></span> Male
                        &nbsp;&nbsp;
                        <span class="checkbox <?= $y['gender'] === 'Female' ? 'checked' : '' ?>"></span> Female
                    </div>
                </div>
                <div class="field">
                    <span class="field-label">Civil Status</span>
                    <div class="field-value"><?= htmlspecialchars($y['civil_status'] ?? '') ?></div>
                </div>
            </div>
            
            <div class="field-row">
                <div class="field">
                    <span class="field-label">Email Address</span>
                    <div class="field-value"><?= htmlspecialchars($y['email'] ?? '') ?></div>
                </div>
                <div class="field">
                    <span class="field-label">Contact Number</span>
                    <div class="field-value"><?= htmlspecialchars($y['contact'] ?? '') ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- II. DEMOGRAPHIC CHARACTERISTICS -->
    <div class="section">
        <div class="section-header">II. DEMOGRAPHIC CHARACTERISTICS</div>
        <div class="section-body">
            <div class="field-row">
                <div class="field">
                    <span class="field-label">Youth Age Group</span>
                    <div class="field-value"><?= htmlspecialchars($ageGroup) ?></div>
                </div>
                <div class="field" style="flex: 2;">
                    <span class="field-label">Youth Classification</span>
                    <div class="field-value"><?= htmlspecialchars($y['youth_classification'] ?? '') ?></div>
                </div>
            </div>
            
            <div class="field-row">
                <div class="field">
                    <span class="field-label">Educational Attainment</span>
                    <div class="field-value"><?= htmlspecialchars($eduAttainment) ?></div>
                </div>
                <div class="field" style="flex: 2;">
                    <span class="field-label">School / University</span>
                    <div class="field-value"><?= htmlspecialchars($y['school_name'] ?? '') ?></div>
                </div>
            </div>
            
            <div class="field-row">
                <div class="field">
                    <span class="field-label">Work Status</span>
                    <div class="field-value"><?= htmlspecialchars($workStatus) ?></div>
                </div>
                <div class="field" style="flex: 2;">
                    <span class="field-label">Occupation / Course</span>
                    <div class="field-value"><?= htmlspecialchars($y['occupation'] ?? '') ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- III. VOTER & KK ASSEMBLY PARTICIPATION -->
    <div class="section">
        <div class="section-header">III. VOTER & KK ASSEMBLY PARTICIPATION</div>
        <div class="section-body">
            <div class="field-row">
                <div class="field">
                    <span class="field-label">Registered SK Voter</span>
                    <div class="field-value">
                        <span class="checkbox <?= $registeredSKVoter === 'Yes' ? 'checked' : '' ?>"></span> Yes
                        &nbsp;&nbsp;
                        <span class="checkbox <?= $registeredSKVoter !== 'Yes' ? 'checked' : '' ?>"></span> No
                    </div>
                </div>
                <div class="field">
                    <span class="field-label">Voted Last SK Election</span>
                    <div class="field-value">
                        <span class="checkbox <?= ($y['voted_last_sk_election'] ?? 'No') === 'Yes' ? 'checked' : '' ?>"></span> Yes
                        &nbsp;&nbsp;
                        <span class="checkbox <?= ($y['voted_last_sk_election'] ?? 'No') !== 'Yes' ? 'checked' : '' ?>"></span> No
                    </div>
                </div>
            </div>
            
            <div class="field-row">
                <div class="field">
                    <span class="field-label">Registered National Voter</span>
                    <div class="field-value">
                        <span class="checkbox <?= ($y['registered_national_voter'] ?? 'No') === 'Yes' ? 'checked' : '' ?>"></span> Yes
                        &nbsp;&nbsp;
                        <span class="checkbox <?= ($y['registered_national_voter'] ?? 'No') !== 'Yes' ? 'checked' : '' ?>"></span> No
                    </div>
                </div>
                <div class="field">
                    <span class="field-label">Attended KK Assembly</span>
                    <div class="field-value">
                        <span class="checkbox <?= ($y['attended_kk_assembly'] ?? 'No') === 'Yes' ? 'checked' : '' ?>"></span> Yes
                        &nbsp;&nbsp;
                        <span class="checkbox <?= ($y['attended_kk_assembly'] ?? 'No') !== 'Yes' ? 'checked' : '' ?>"></span> No
                    </div>
                </div>
            </div>
            
            <?php if (!empty($y['kk_assembly_times']) || !empty($y['kk_assembly_no_reason'])): ?>
            <div class="field-row">
                <?php if (!empty($y['kk_assembly_times'])): ?>
                <div class="field">
                    <span class="field-label">Times Attended KK Assembly</span>
                    <div class="field-value"><?= htmlspecialchars($y['kk_assembly_times']) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($y['kk_assembly_no_reason'])): ?>
                <div class="field" style="flex: 2;">
                    <span class="field-label">Reason for Not Attending</span>
                    <div class="field-value"><?= htmlspecialchars($y['kk_assembly_no_reason']) ?></div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Skills & Interests -->
    <?php if (!empty($y['skills']) || !empty($y['interests'])): ?>
    <div class="section">
        <div class="section-header">IV. SKILLS & INTERESTS</div>
        <div class="section-body">
            <div class="field-row">
                <div class="field">
                    <span class="field-label">Skills / Talents</span>
                    <div class="field-value"><?= htmlspecialchars($y['skills'] ?? '') ?></div>
                </div>
            </div>
            <div class="field-row">
                <div class="field">
                    <span class="field-label">Interests / Hobbies</span>
                    <div class="field-value"><?= htmlspecialchars($y['interests'] ?? '') ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Emergency Contact -->
    <div class="section">
        <div class="section-header">V. EMERGENCY CONTACT</div>
        <div class="section-body">
            <div class="field-row">
                <div class="field" style="flex: 2;">
                    <span class="field-label">Contact Person Name</span>
                    <div class="field-value"><?= htmlspecialchars($y['emergency_contact_name'] ?? '') ?></div>
                </div>
                <div class="field">
                    <span class="field-label">Contact Number</span>
                    <div class="field-value"><?= htmlspecialchars($y['emergency_contact_number'] ?? '') ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Signature Section -->
    <div class="signature-box">
        <div class="signature-line"></div>
        <div style="font-weight: bold; font-size: 11pt;"><?= $fullName ?></div>
        <div style="font-size: 9pt; color: #666;">Youth Signature Over Printed Name</div>
        <div style="margin-top: 10px; font-size: 9pt;">Date: <?= date('F d, Y') ?></div>
    </div>
    
    <!-- Footer -->
    <div class="footer-note">
        <strong>CERTIFIED TRUE AND CORRECT</strong><br>
        This profile was generated from <?= BARANGAY_NAME ?> Katipunan ng Kabataan Management System<br>
        Control No.: <?= str_pad($y['id'], 6, '0', STR_PAD_LEFT) ?> | Generated: <?= date('F d, Y h:i A') ?><br>
        <small style="color: #999;">BMS v1.0 © <?= date('Y') ?> All Rights Reserved</small>
    </div>
    
</div>

<script>
// Auto-print when page loads
setTimeout(() => {
    window.print();
}, 500);
</script>

</body>
</html>
