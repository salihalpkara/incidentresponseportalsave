<?php
require_once("includes/template_header.php");
require_once("includes/session_start.php");
require_once 'includes/db_connect.php';
require_once("includes/template_navbar.php");

include_once("includes/fetch_page_visits.php"); 
?>

<div class="container pb-3">
    <div class="row m-3">
        <h1 class="text-center">Page Visit Reports</h1>
    </div>
    <div class="row overflow-auto">
        <div class="col">
            <div class="card">
                <div class="card-header d-flex">
                    <h3 class="me-auto mb-0 py-1">Visit Logs</h3>
                    <div class="ms-auto" id="visitActionButtons">
                        <a href="view_page_visits.php?view=all" class="btn btn-primary me-2">All Visits</a>
                        <a href="view_page_visits.php?view=user" class="btn btn-secondary me-2">Per User</a>
                        <a href="view_page_visits.php?view=summary" class="btn btn-info">Page Summary</a>
                    </div>
                </div>
                <div class="card-body">
                    <?php
                    $view = $_GET['view'] ?? 'all';
                    if ($view === 'user') {
                        include("includes/view_visits_by_user.php");
                    } elseif ($view === 'summary') {
                        include("includes/view_visit_summary.php");
                    } else {
                        include("includes/view_all_visits.php");
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let table = new DataTable('#visitTable');
</script>

<?php require_once("includes/template_footer.php"); ?>