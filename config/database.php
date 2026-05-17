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
            -- Name
            first_name TEXT NOT NULL,
            middle_name TEXT,
            last_name TEXT NOT NULL,
            suffix TEXT,
            -- Location (I. PROFILE)
            region TEXT,
            province TEXT,
            city_municipality TEXT,
            barangay TEXT,
            purok TEXT,
            -- Personal
            birthdate TEXT NOT NULL,
            gender TEXT NOT NULL,
            age INTEGER,
            civil_status TEXT DEFAULT 'Single',
            email TEXT,
            contact TEXT,
            home_address TEXT,
            -- II. DEMOGRAPHIC CHARACTERISTICS
            youth_classification TEXT,
            youth_age_group TEXT,
            -- Education
            educational_attainment TEXT,
            school_name TEXT,
            -- Work
            work_status TEXT,
            occupation TEXT,
            -- Voter & Participation
            registered_sk_voter TEXT DEFAULT 'No',
            voted_last_sk_election TEXT DEFAULT 'No',
            registered_national_voter TEXT DEFAULT 'No',
            attended_kk_assembly TEXT DEFAULT 'No',
            kk_assembly_times TEXT,
            kk_assembly_no_reason TEXT,
            -- Legacy / Extra fields
            skills TEXT,
            interests TEXT,
            emergency_contact_number TEXT,
            is_archived INTEGER DEFAULT 0,
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

        CREATE TABLE IF NOT EXISTS activities (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            description TEXT,
            activity_date TEXT NOT NULL,
            status TEXT DEFAULT 'Ongoing',
            created_at TEXT DEFAULT (datetime('now','localtime'))
        );
    ");

    // ── Migrate existing kk_youth table: add new columns if they don't exist ──
    $existingCols = array_column(
        $pdo->query("PRAGMA table_info(kk_youth)")->fetchAll(),
        'name'
    );

    $migrations = [
        'region'                   => "ALTER TABLE kk_youth ADD COLUMN region TEXT",
        'province'                 => "ALTER TABLE kk_youth ADD COLUMN province TEXT",
        'city_municipality'        => "ALTER TABLE kk_youth ADD COLUMN city_municipality TEXT",
        'barangay'                 => "ALTER TABLE kk_youth ADD COLUMN barangay TEXT",
        'purok'                    => "ALTER TABLE kk_youth ADD COLUMN purok TEXT",
        'age'                      => "ALTER TABLE kk_youth ADD COLUMN age INTEGER",
        'home_address'             => "ALTER TABLE kk_youth ADD COLUMN home_address TEXT",
        'youth_age_group'          => "ALTER TABLE kk_youth ADD COLUMN youth_age_group TEXT",
        'educational_attainment'   => "ALTER TABLE kk_youth ADD COLUMN educational_attainment TEXT",
        'work_status'              => "ALTER TABLE kk_youth ADD COLUMN work_status TEXT",
        'registered_sk_voter'      => "ALTER TABLE kk_youth ADD COLUMN registered_sk_voter TEXT DEFAULT 'No'",
        'voted_last_sk_election'   => "ALTER TABLE kk_youth ADD COLUMN voted_last_sk_election TEXT DEFAULT 'No'",
        'registered_national_voter'=> "ALTER TABLE kk_youth ADD COLUMN registered_national_voter TEXT DEFAULT 'No'",
        'attended_kk_assembly'     => "ALTER TABLE kk_youth ADD COLUMN attended_kk_assembly TEXT DEFAULT 'No'",
        'kk_assembly_times'        => "ALTER TABLE kk_youth ADD COLUMN kk_assembly_times TEXT",
        'kk_assembly_no_reason'    => "ALTER TABLE kk_youth ADD COLUMN kk_assembly_no_reason TEXT",
        'is_archived'              => "ALTER TABLE kk_youth ADD COLUMN is_archived INTEGER DEFAULT 0",
    ];

    foreach ($migrations as $col => $sql) {
        if (!in_array($col, $existingCols)) {
            $pdo->exec($sql);
        }
    }

    // Rename old columns via data copy for renamed fields
    // educational_status -> educational_attainment (copy data if new col is empty)
    if (in_array('educational_status', $existingCols) && in_array('educational_attainment', $existingCols)) {
        $pdo->exec("UPDATE kk_youth SET educational_attainment = educational_status WHERE educational_attainment IS NULL OR educational_attainment = ''");
    }
    // employment_status -> work_status
    if (in_array('employment_status', $existingCols) && in_array('work_status', $existingCols)) {
        $pdo->exec("UPDATE kk_youth SET work_status = employment_status WHERE work_status IS NULL OR work_status = ''");
    }
    // address -> home_address
    if (in_array('address', $existingCols) && in_array('home_address', $existingCols)) {
        $pdo->exec("UPDATE kk_youth SET home_address = address WHERE home_address IS NULL OR home_address = ''");
    }
    // sk_voter -> registered_sk_voter
    if (in_array('sk_voter', $existingCols) && in_array('registered_sk_voter', $existingCols)) {
        $pdo->exec("UPDATE kk_youth SET registered_sk_voter = sk_voter WHERE registered_sk_voter IS NULL OR registered_sk_voter = ''");
    }

    // Seed default admin user if empty
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($userCount == 0) {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT), 'System Administrator', 'Admin']);
        $stmt->execute(['sk_official', password_hash('sk123', PASSWORD_DEFAULT), 'SK Chairman', 'SK Official']);
        $stmt->execute(['kk_member', password_hash('kk123', PASSWORD_DEFAULT), 'Katipunan Member', 'Katipunan Member']);
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
