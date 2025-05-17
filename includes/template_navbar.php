<?php
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$role_id = $_SESSION["role_id"];
?>
<nav class="navbar navbar-expand-lg bg-body-tertiary fixed-top">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Incident Response Portal</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" id="navItemDashboard" href="dashboard.php"><i class="bi bi-house-door me-2"></i>Dashboard</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" id="navItemIncidentReport" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Incident Report
                    </a>

                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="view_reports.php"><i class="bi bi-eyeglasses me-2"></i>View Reports</a></li>
                        <li><a class="dropdown-item" href="report_incident.php"><i class="bi bi-plus-lg me-2"></i>Create New</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="navItemAnalytics" href="analytics.php"><i class="bi bi-bar-chart me-2"></i>Analytics</a>
                </li>
                <?php if ($role_id == 1): ?>
                    <li class="nav-item">
                        <a class="nav-link" id="navItemPageVisits" href="view_page_visits.php"><i class="bi bi-journal-text me-2"></i>Page Visits</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="navItemManageUsers" href="manage_users.php"><i class="bi bi-person-lines-fill me-2"></i>Manage Users</a>
                    </li>
                <?php endif; ?>

            </ul>
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle fs-3 me-2"></i>
                        <?= htmlspecialchars($_SESSION["fname"]) . " " . htmlspecialchars($_SESSION["lname"]) ?>
                    </a>

                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="profile.php?id=<?= $_SESSION["user_id"] ?>"><i class="bi bi-person-lines-fill me-2"></i>View profile</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </li>
            </ul>

        </div>
    </div>
</nav>
<div style="height: 74px; display: block;"></div>