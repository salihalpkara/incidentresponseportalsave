<?php
require_once("includes/session_start.php");
require_once("includes/db_connect.php");
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$role_id = $_SESSION["role_id"];
$user_id = $_SESSION["user_id"];

$sql = "SELECT
    i.incident_id,
    i.description,
    i.incident_date,
    t.type_name,
    s.severity_name,
    CONCAT(u.fname, ' ', u.lname) AS created_by_name,
    first_status.updated_at AS created_at,
    (
        SELECT st.status_name
        FROM irp_incident_status ist
        JOIN irp_status st ON ist.status_id = st.status_id
        WHERE ist.incident_id = i.incident_id
        ORDER BY ist.updated_at DESC, ist.incident_status_id DESC
        LIMIT 1
    ) AS latest_status_name
FROM irp_incident i
JOIN irp_type t ON i.type_id = t.type_id
JOIN irp_severity s ON i.severity_id = s.severity_id
JOIN (
    SELECT ist.incident_id, ist.updated_by, ist.status_id, ist.updated_at
    FROM irp_incident_status ist
    WHERE ist.updated_at = (
        SELECT MIN(sub.updated_at)
        FROM irp_incident_status sub
        WHERE sub.incident_id = ist.incident_id
    )
) first_status ON i.incident_id = first_status.incident_id
JOIN irp_user u ON first_status.updated_by = u.user_id 
WHERE first_status.status_id = 1
";

$params = [];

if ($role_id == 3) {
    $sql .= " AND first_status.updated_by = :user_id";
    $params[':user_id'] = $user_id;
}

$sql .= " ORDER BY created_at DESC;";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $incidents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
    $incidents = [];
}
