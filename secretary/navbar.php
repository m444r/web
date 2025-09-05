<?php require_once 'auth.php'; ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="secretary.php">Thesis Support System</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'secretary.php' ? 'active' : '' ?>" 
                       href="secretary.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'view_theses.php' ? 'active' : '' ?>" 
                       href="view_theses.php">Theses</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'thesis_announcements.php' ? 'active' : '' ?>" 
                       href="thesis_announcements.php">Announcements</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'generate_reports.php' ? 'active' : '' ?>" 
                       href="generate_reports.php">Reports</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="manageDropdown" role="button" data-bs-toggle="dropdown">
                        Manage
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="add_student.php">Add Student</a></li>
                        <li><a class="dropdown-item" href="add_professor.php">Add Professor</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="import_data.php">Import Data</a></li>
                    </ul>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                        <li><a class="dropdown-item" href="settings.php">Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
