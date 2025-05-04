<?php
require_once 'includes/db_connect.php';
header('Content-Type: application/json');

$groupBy = $_GET['group_by'] ?? 'date';
$validGroups = ['date', 'type', 'severity'];

if (!in_array($groupBy, $validGroups)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid group_by parameter']);
    exit;
}

switch ($groupBy) {
    case 'date':
        $query = "SELECT DATE(incident_date) as label, COUNT(*) as total FROM irp_incident GROUP BY DATE(incident_date) ORDER BY label ASC";
        break;
    case 'type':
        $query = "SELECT t.type_name as label, 
                         IFNULL(COUNT(i.incident_id), 0) as total
                  FROM irp_type t
                  LEFT JOIN irp_incident i ON i.type_id = t.type_id
                  GROUP BY t.type_name
                  ORDER BY total DESC";
        break;
    case 'severity':
        $query = "SELECT s.severity_name as label, 
                         IFNULL(COUNT(i.incident_id), 0) as total
                  FROM irp_severity s
                  LEFT JOIN irp_incident i ON i.severity_id = s.severity_id
                  GROUP BY s.severity_name
                  ORDER BY s.severity_id ASC";
        break;
}

$stmt = $pdo->query($query);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($data);
