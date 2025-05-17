<?php
require_once("includes/session_start.php");
require_once("includes/db_connect.php");

$user_id = $_SESSION['user_id'] ?? null;
$current_url = $_SERVER['REQUEST_URI'];
$ip_address = $_SERVER['REMOTE_ADDR'];
$user_agent = $_SERVER['HTTP_USER_AGENT'];

$stmt = $pdo->prepare("SELECT page_id FROM irp_page WHERE url = ?");
$stmt->execute([$current_url]);
$page_id = $stmt->fetchColumn();

if (!$page_id) {
    $stmt = $pdo->prepare("INSERT INTO irp_page (url) VALUES (?)");
    $stmt->execute([$current_url]);
    $page_id = $pdo->lastInsertId();
}

$browser = "Other";
if (strpos($user_agent, 'Chrome') !== false && strpos($user_agent, 'Edg') === false) $browser = "Chrome";
elseif (strpos($user_agent, 'Firefox') !== false) $browser = "Firefox";
elseif (strpos($user_agent, 'Safari') !== false && strpos($user_agent, 'Chrome') === false) $browser = "Safari";
elseif (strpos($user_agent, 'Edg') !== false) $browser = "Microsoft Edge";
elseif (strpos($user_agent, 'OPR') !== false || strpos($user_agent, 'Opera') !== false) $browser = "Opera";
elseif (strpos($user_agent, 'Brave') !== false) $browser = "Brave";

$stmt = $pdo->prepare("SELECT browser_id FROM irp_browser WHERE browser_name = ?");
$stmt->execute([$browser]);
$browser_id = $stmt->fetchColumn() ?? 7; 

$stmt = $pdo->prepare("INSERT INTO irp_visit_log (page_id, ip_address, browser_id) VALUES (?, ?, ?)");
$stmt->execute([$page_id, $ip_address, $browser_id]);
$visit_id = $pdo->lastInsertId();

if ($user_id !== null) {
    $stmt = $pdo->prepare("INSERT INTO irp_user_page_visit (user_id, visit_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $visit_id]);
}
