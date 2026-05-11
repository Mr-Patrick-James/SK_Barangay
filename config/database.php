<?php
define('DB_PATH', __DIR__ . '/../data/barangay.db');
define('BARANGAY_NAME', 'Barangay Bacungan');
define('MUNICIPALITY', 'Municipality of Naujan');
define('PROVINCE', 'Oriental Mindoro');
define('CAPTAIN_NAME', 'Hon. Juan Dela Cruz');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dir = dirname(DB_PATH);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA journal_mode=WAL');
        $pdo->exec('PRAGMA foreign_keys=ON');
        initDB($pdo);
    }
    return $pdo;
}

function initDB(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            full_name TEXT NOT NULL,
            role TEXT DEFAULT 'Staff',
            created_at TEXT DEFAULT (datetime('now','localtime'))
        );

        CREATE TABLE IF NOT EXISTS kk_youth (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            first_name TEXT NOT NULL,
            middle_name TEXT,
            last_name TEXT NOT NULL,
            suffix TEXT,
            birthdate TEXT NOT NULL,
            gender TEXT NOT NULL,
            civil_status TEXT DEFAULT 'Single',
            address TEXT NOT NULL,
            contact TEXT,
            email TEXT,
            youth_classification TEXT,
            educational_status TEXT,
            school_name TEXT,
            employment_status TEXT,
            occupation TEXT,
            sk_voter TEXT DEFAULT 'No',
            skills TEXT,
            interests TEXT,
            emergency_contact_name TEXT,
            emergency_contact_number TEXT,
            created_at TEXT DEFAULT (datetime('now','localtime')),
            updated_at TEXT DEFAULT (datetime('now','localtime'))
        );

        CREATE TABLE IF NOT EXISTS residents (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            first_name TEXT NOT NULL,
            middle_name TEXT,
            last_name TEXT NOT NULL,
            birthdate TEXT,
            gender TEXT,
            civil_status TEXT,
            address TEXT,
            contact TEXT,
            email TEXT,
            occupation TEXT,
            voter_status TEXT DEFAULT 'No',
            created_at TEXT DEFAULT (datetime('now','localtime')),
            updated_at TEXT DEFAULT (datetime('now','localtime'))
        );

        CREATE TABLE IF NOT EXISTS officials (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            first_name TEXT NOT NULL,
            middle_name TEXT,
            last_name TEXT NOT NULL,
            position TEXT NOT NULL,
            term_start TEXT,
            term_end TEXT,
            contact TEXT,
            status TEXT DEFAULT 'Active',
            created_at TEXT DEFAULT (datetime('now','localtime'))
        );

        CREATE TABLE IF NOT EXISTS certificates (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            resident_id INTEGER,
            cert_type TEXT NOT NULL,
            purpose TEXT,
            issued_by TEXT,
            or_number TEXT,
            amount REAL DEFAULT 0,
            issued_at TEXT DEFAULT (datetime('now','localtime')),
            FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE SET NULL
        );

        CREATE TABLE IF NOT EXISTS blotter (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            incident_date TEXT,
            complainant TEXT NOT NULL,
            respondent TEXT NOT NULL,
            nature TEXT NOT NULL,
            details TEXT,
            status TEXT DEFAULT 'Pending',
            action_taken TEXT,
            recorded_by TEXT,
            created_at TEXT DEFAULT (datetime('now','localtime')),
            updated_at TEXT DEFAULT (datetime('now','localtime'))
        );
    ");

    // Seed default admin user if empty
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($userCount == 0) {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            'admin',
            password_hash('admin123', PASSWORD_DEFAULT),
            'System Administrator',
            'Admin'
        ]);
    }

    // Seed officials if empty
    $count = $pdo->query("SELECT COUNT(*) FROM officials")->fetchColumn();
    if ($count == 0) {
        $officials = [
            ['Juan', 'R.', 'Dela Cruz', 'Barangay Captain'],
            ['Maria', 'S.', 'Santos', 'Barangay Kagawad'],
            ['Pedro', 'T.', 'Reyes', 'Barangay Kagawad'],
            ['Ana', 'L.', 'Garcia', 'Barangay Kagawad'],
            ['Jose', 'M.', 'Bautista', 'Barangay Kagawad'],
            ['Rosa', 'N.', 'Mendoza', 'Barangay Kagawad'],
            ['Carlos', 'P.', 'Torres', 'Barangay Kagawad'],
            ['Elena', 'Q.', 'Flores', 'Barangay Kagawad'],
            ['Miguel', 'V.', 'Cruz', 'SK Chairman'],
            ['Liza', 'A.', 'Ramos', 'Barangay Secretary'],
            ['Roberto', 'B.', 'Villanueva', 'Barangay Treasurer'],
        ];
        $stmt = $pdo->prepare("INSERT INTO officials (first_name, middle_name, last_name, position, term_start, term_end, status) VALUES (?, ?, ?, ?, '2023-01-01', '2026-12-31', 'Active')");
        foreach ($officials as $o) {
            $stmt->execute($o);
        }
    }
}
