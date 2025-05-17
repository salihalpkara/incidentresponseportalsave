<?php
require_once("includes/session_start.php");
require_once("includes/db_connect.php");
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
$queryAll = "
SELECT 
    v.visit_id, 
    p.url AS page_url, 
    v.ip_address, 
    b.browser_name, 
    v.visited_at,
    upv.user_id
FROM irp_visit_log v
JOIN irp_page p ON v.page_id = p.page_id
JOIN irp_browser b ON v.browser_id = b.browser_id
LEFT JOIN irp_user_page_visit upv ON v.visit_id = upv.visit_id
ORDER BY v.visited_at DESC
";
$visit_logs = $pdo->query($queryAll)->fetchAll(PDO::FETCH_ASSOC);

// Summary: visit count per page
$querySummary = "
SELECT p.url, COUNT(*) as visit_count
FROM irp_visit_log v
JOIN irp_page p ON v.page_id = p.page_id
GROUP BY p.url
ORDER BY visit_count DESC
";
$page_summary = $pdo->query($querySummary)->fetchAll(PDO::FETCH_ASSOC);

// Per-user visits: grouped (basic list of users and counts)
$queryUsers = "
SELECT upv.user_id, COUNT(*) AS total_visits
FROM irp_user_page_visit upv
GROUP BY upv.user_id
";
$visits_by_user = $pdo->query($queryUsers)->fetchAll(PDO::FETCH_ASSOC);
