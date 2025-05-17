<?php
require_once("includes/session_start.php");
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
require_once("includes/template_header.php");
require_once 'includes/db_connect.php';
require_once("includes/template_navbar.php");
require_once 'includes/log_visit.php';
$username = $_SESSION["username"];
$user_id = $_SESSION["user_id"];
$role_id = $_SESSION["role_id"];
$fname = $_SESSION["fname"];
$lname = $_SESSION["lname"];
$email = $_SESSION["email"];
include_once("includes/fetch_reports.php");

?>
<div class="container pb-3">
    <div class="row m-3">
        <h1 class="text-center">View Reports</h1>
    </div>
    <div class="row overflow-auto">
        <div class="col">
            <div class="card">
                <div class="card-header d-flex">
                    <h3 class="me-auto mb-0 py-1">All Incident Reports</h3>
                    <div class="ms-auto" id="incidentActionButtons">
                        <a href="report_incident.php" class="btn btn-success"><i class="bi bi-plus-lg me-2"></i>Add New Report</a>

                    </div>
                </div>
                <div class="card-body">
                    <table id="allIncidents" class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>Incident ID</th>
                                <th>Description</th>
                                <th>Incident Date</th>
                                <th>Type</th>
                                <th>Severity</th>
                                <th>Created By</th>
                                <th>Latest Status</th>
                            </tr>
                        </thead>
                        <tbody id="myTable">
                            <?php if (empty($incidents)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No incidents reported yet.</td>
                                </tr>
                            <?php else: ?>

                                <?php foreach ($incidents as $incident): ?>
                                    <tr>
                                        <td><a href="view_report.php?id=<?= $incident["incident_id"] ?>" class="d-block text-decoration-none text-light"><?= htmlspecialchars($incident['incident_id']) ?></a></td>
                                        <td><a href="view_report.php?id=<?= $incident["incident_id"] ?>" class="d-block text-decoration-none text-light"><?= htmlspecialchars($incident['description']) ?></a></td>
                                        <td><a href="view_report.php?id=<?= $incident["incident_id"] ?>" class="d-block text-decoration-none text-light"><?= htmlspecialchars($incident['incident_date']) ?></a></td>
                                        <td><a href="view_report.php?id=<?= $incident["incident_id"] ?>" class="d-block text-decoration-none text-light"><?= htmlspecialchars($incident['type_name']) ?></a></td>
                                        <td><a href="view_report.php?id=<?= $incident["incident_id"] ?>" class="d-block text-decoration-none text-light"><?= htmlspecialchars($incident['severity_name']) ?></a></td>
                                        <td><a href="view_report.php?id=<?= $incident["incident_id"] ?>" class="d-block text-decoration-none text-light"><?= htmlspecialchars($incident['created_by_name']) ?></a></td>
                                        <td><a href="view_report.php?id=<?= $incident["incident_id"] ?>" class="d-block text-decoration-none <?php switch (htmlspecialchars($incident['latest_status_name'])):
                                                                                                                                                    case "In progress": ?> text-warning <?php break; ?> <?php
                                                                                                                                                                                                    case "Resolved": ?> text-success<?php break; ?><?php
                                                                                                                                                                                                                                                case "Pending": ?> text-danger<?php break; ?><?php endswitch; ?> "><?= htmlspecialchars($incident['latest_status_name']) ?></a></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let table = new DataTable('#allIncidents');
</script>

<?php
require_once("includes/template_footer.php");
?>