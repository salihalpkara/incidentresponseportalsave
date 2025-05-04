<?php
require_once("includes/template_header.php");
require_once("includes/session_start.php");
require_once 'includes/db_connect.php';
require_once("includes/template_navbar.php");
$username = $_SESSION["username"];
$user_id = $_SESSION["user_id"];
$role_id = $_SESSION["role_id"];
$fname = $_SESSION["fname"];
$lname = $_SESSION["lname"];
$email = $_SESSION["email"];
include_once("includes/fetch_reports.php");

?>
<div class="container">
    <div class="row m-3">
        <h1 class="text-center">View Reports</h1>
    </div>
    <div class="row overflow-auto">
        <div class="col">
            <div class="card">
                <div class="card-header d-flex">
                    <h3>All Incident Reports</h3>

                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>Incident ID</th>
                                <th>Description</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Severity</th>
                                <th>Created By</th>
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
                                        <td><?= htmlspecialchars($incident['incident_id']) ?></td>
                                        <td><?= htmlspecialchars($incident['description']) ?></td>
                                        <td><?= htmlspecialchars($incident['incident_date']) ?></td>
                                        <td><?= htmlspecialchars($incident['type_name']) ?></td>
                                        <td><?= htmlspecialchars($incident['severity_name']) ?></td>
                                        <td><?= htmlspecialchars($incident['created_by_name']) ?></td>
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

<?php
require_once("includes/template_footer.php");
?>
