<?php
require_once 'includes/db_connect.php';
header('Content-Type: application/json');

$stmt = $pdo->query("SELECT severity_id, severity_name FROM irp_severity ORDER BY severity_name ASC");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
