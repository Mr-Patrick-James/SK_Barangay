<?php
// Resolve auth.php relative to the workspace root regardless of include depth
require_once dirname(__DIR__) . '/config/auth.php';
requireLogin();
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Barangay Management System') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $rootPath ?? '' ?>assets/css/style.css">
</head>
<body>
<?php
$currentUri = $_SERVER['REQUEST_URI'];
function isActive(string $path): string {
    return str_contains($_SERVER['REQUEST_URI'], $path) ? 'active' : '';
}
?>
<div class="wrapper d-flex">
    <!-- Sidebar -->
    <nav id="sidebar" class="sidebar d-flex flex-column flex-shrink-0 p-0">
        <div class="sidebar-header text-center py-3 px-2">
            <div class="brgy-seal mb-2">
                <i class="bi bi-shield-fill-check fs-1 text-warning"></i>
            </div>
            <div class="brgy-name fw-bold text-white lh-sm">
                <?= BARANGAY_NAME ?><br>
                <small class="fw-normal opacity-75" style="font-size:0.7rem"><?= MUNICIPALITY ?></small>
            </div>
        </div>
        <ul class="nav flex-column px-2 pb-3 mt-2">
            <?php
            $userRole = $currentUser['role'] ?? '';
            $isAdmin = $userRole === 'Admin';
            $isSK = $userRole === 'SK Official';
            $isKK = $userRole === 'Katipunan Member';
            ?>
            
            <?php if ($isAdmin): ?>
            <li class="nav-item">
                <a href="<?= $rootPath ?? '' ?>index.php" class="nav-link <?= isActive('index.php') && !isActive('modules') ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item mt-1">
                <span class="nav-section-label px-2">RECORDS</span>
            </li>
            <li class="nav-item">
                <a href="<?= $rootPath ?? '' ?>modules/residents/index.php" class="nav-link <?= isActive('residents') ? 'active' : '' ?>">
                    <i class="bi bi-people-fill me-2"></i> Residents
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= $rootPath ?? '' ?>modules/officials/index.php" class="nav-link <?= isActive('officials') ? 'active' : '' ?>">
                    <i class="bi bi-person-badge-fill me-2"></i> Officials
                </a>
            </li>
            <?php endif; ?>

            <?php if ($isAdmin || $isSK): ?>
            <?php if ($isSK): ?>
            <li class="nav-item mt-1">
                <span class="nav-section-label px-2">SK DASHBOARD</span>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a href="<?= $rootPath ?? '' ?>modules/kk_youth/index.php" class="nav-link <?= isActive('kk_youth') ? 'active' : '' ?>">
                    <i class="bi bi-stars me-2"></i> KK Youth Profiling
                </a>
            </li>
            <?php endif; ?>

            <?php if ($isAdmin || $isKK || $isSK): ?>
            <?php if ($isKK): ?>
            <li class="nav-item mt-1">
                <span class="nav-section-label px-2">KK DASHBOARD</span>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a href="<?= $rootPath ?? '' ?>modules/activities/index.php" class="nav-link <?= isActive('activities') ? 'active' : '' ?>">
                    <i class="bi bi-calendar-event me-2"></i> Ongoing Activities
                </a>
            </li>
            <?php endif; ?>

            <?php if ($isAdmin): ?>
            <li class="nav-item mt-1">
                <span class="nav-section-label px-2">SERVICES</span>
            </li>
            <li class="nav-item">
                <a href="<?= $rootPath ?? '' ?>modules/certificates/index.php" class="nav-link <?= isActive('certificates') ? 'active' : '' ?>">
                    <i class="bi bi-file-earmark-text-fill me-2"></i> Certificates
                </a>
            </li>
            <li class="nav-item mt-1">
                <span class="nav-section-label px-2">SYSTEM</span>
            </li>
            <li class="nav-item">
                <a href="<?= $rootPath ?? '' ?>modules/users/index.php" class="nav-link <?= isActive('users') ? 'active' : '' ?>">
                    <i class="bi bi-shield-lock-fill me-2"></i> User Management
                </a>
            </li>
            <?php endif; ?>
        </ul>
        <div class="mt-auto px-3 pb-3">
            <small class="text-white-50">BMS v1.0 &copy; <?= date('Y') ?></small>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content flex-grow-1 d-flex flex-column">
        <!-- Top Navbar -->
        <header class="topbar d-flex align-items-center px-4 py-2">
            <button class="btn btn-sm btn-outline-secondary me-3 d-lg-none" id="sidebarToggle">
                <i class="bi bi-list fs-5"></i>
            </button>
            <h5 class="mb-0 fw-semibold text-dark"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h5>
            <div class="ms-auto d-flex align-items-center gap-3">
                <span class="badge bg-success-subtle text-success border border-success-subtle">
                    <i class="bi bi-circle-fill me-1" style="font-size:0.5rem"></i>Online
                </span>
                <span class="text-muted small d-none d-md-inline"><?= date('F d, Y') ?></span>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i>
                        <span class="d-none d-sm-inline"><?= htmlspecialchars($currentUser['full_name'] ?? 'User') ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header">
                            <div class="fw-semibold"><?= htmlspecialchars($currentUser['full_name'] ?? 'User') ?></div>
                            <small class="text-muted"><?= htmlspecialchars($currentUser['role'] ?? 'Staff') ?></small>
                        </h6></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= $rootPath ?? '' ?>logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-grow-1 p-4">
