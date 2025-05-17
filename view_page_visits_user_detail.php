<?php
require_once("includes/template_header.php");
require_once("includes/session_start.php");
require_once 'includes/db_connect.php';
require_once("includes/template_navbar.php");

$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>User ID missing.</div></div>";
    require_once("includes/template_footer.php");
    exit;
}

$stmt = $pdo->prepare("
SELECT v.visit_id, p.url AS page_url, v.ip_address, b.browser_name, v.visited_at
FROM irp_user_page_visit upv
JOIN irp_visit_log v ON upv.visit_id = v.visit_id
JOIN irp_page p ON v.page_id = p.page_id
JOIN irp_browser b ON v.browser_id = b.browser_id
WHERE upv.user_id = ?
ORDER BY v.visited_at DESC
");
$stmt->execute([$user_id]);
$user_visits = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container pb-3">
    <div class="row m-3">
        <h2 class="text-center">Page Visits for User ID: <?= htmlspecialchars($user_id) ?></h2>
    </div>
    <div class="card">
        <div class="card-body">
            <table id="visitTable" class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Visit ID</th>
                        <th>Page URL</th>
                        <th>IP Address</th>
                        <th>Browser</th>
                        <th>Visited At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($user_visits as $visit): ?>
                        <tr>
                            <td><?= htmlspecialchars($visit['visit_id']) ?></td>
                            <td><?= htmlspecialchars($visit['page_url']) ?></td>
                            <td><?= htmlspecialchars($visit['ip_address']) ?></td>
                            <td><?= htmlspecialchars($visit['browser_name']) ?></td>
                            <td><?= htmlspecialchars($visit['visited_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    let table = new DataTable('#visitTable');
</script>

<?php require_once("includes/template_footer.php"); ?>
