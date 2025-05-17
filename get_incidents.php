<?php
require_once 'includes/db_connect.php'; // Your database connection
// require_once("includes/session_start.php"); // Uncomment if session/auth is needed

header('Content-Type: application/json');

// Basic security: Ensure user is logged in if this data is sensitive
// if (empty($_SESSION["user_id"])) {
//     http_response_code(403); // Forbidden
//     echo json_encode(["error" => "User not authenticated"]);
//     exit;
// }

$groupBy = $_GET['group_by'] ?? 'date'; // Default to 'date' if not provided
$validGroups = ['date', 'type', 'severity', 'month', 'asset']; // Added 'asset'

if (!in_array($groupBy, $validGroups)) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Invalid group_by parameter']);
    exit;
}

$query = ""; // Initialize query string

switch ($groupBy) {
    case 'date':
        $query = "SELECT DATE(incident_date) as label, COUNT(*) as total 
                  FROM irp_incident 
                  GROUP BY DATE(incident_date) 
                  ORDER BY label ASC";
        break;
    case 'type':
        // Query to get all types, even those with 0 incidents
        $query = "SELECT t.type_name as label, 
                         IFNULL(COUNT(i.incident_id), 0) as total
                  FROM irp_type t
                  LEFT JOIN irp_incident i ON i.type_id = t.type_id
                  GROUP BY t.type_id, t.type_name -- Group by ID as well for consistency
                  ORDER BY total DESC, t.type_name ASC"; // Order by total, then by name
        break;
    case 'severity':
        // Query to get all severities, even those with 0 incidents
        // Ordered by severity_id to maintain logical order (Low, Medium, High, Critical)
        $query = "SELECT s.severity_name as label, 
                         IFNULL(COUNT(i.incident_id), 0) as total
                  FROM irp_severity s
                  LEFT JOIN irp_incident i ON i.severity_id = s.severity_id
                  GROUP BY s.severity_id, s.severity_name -- Group by ID for correct ordering
                  ORDER BY s.severity_id ASC";
        break;
    case 'month':
        $query = "SELECT DATE_FORMAT(incident_date, '%Y-%m') as label, COUNT(*) as total 
                  FROM irp_incident 
                  GROUP BY DATE_FORMAT(incident_date, '%Y-%m') 
                  ORDER BY label ASC";
        break;
    case 'asset': // New case for fetching asset data
        $query = "SELECT a.asset_name as label, 
                         COUNT(ia.incident_id) AS total 
                  FROM irp_asset a
                  LEFT JOIN irp_incident_asset ia ON a.asset_id = ia.asset_id
                  GROUP BY a.asset_id, a.asset_name -- Group by ID as well
                  ORDER BY total DESC, a.asset_name ASC"; // Order by most incidents, then by name
        break;
}

try {
    $stmt = $pdo->query($query); // Assumes $pdo is your PDO connection object from db_connect.php
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($data);
} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Database query failed: ' . $e->getMessage()]);
    exit;
}

// $pdo = null; // Close connection if not persistent
?>
