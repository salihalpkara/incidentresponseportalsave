<?php
require_once 'includes/db_connect.php';
header('Content-Type: application/json');

$stmt = $pdo->query("SELECT type_id, type_name FROM irp_type ORDER BY type_name ASC");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
