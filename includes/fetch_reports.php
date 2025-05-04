<?php
require_once("includes/session_start.php");
require_once("includes/db_connect.php");
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$role_id = $_SESSION["role_id"];
$user_id = $_SESSION["user_id"];

// Sorgu: Incident detayları, oluşturanın adı/soyadı (ilk status kaydına göre)
// Sadece ilk status kaydı 'Pending' (status_id=1) olan incident'lar getirilir.
$sql = "SELECT
    i.incident_id,
    i.description,
    i.incident_date,
    t.type_name,
    s.severity_name,
    CONCAT(u.fname, ' ', u.lname) AS created_by_name
FROM irp_incident i
JOIN irp_type t ON i.type_id = t.type_id
JOIN irp_severity s ON i.severity_id = s.severity_id
JOIN (
    -- Bu alt sorgu 'first_status', her incident için en eski (ilk) status kaydını bulur
    -- ve bu kaydın incident_id, updated_by VE status_id değerlerini getirir.
    SELECT ist.incident_id, ist.updated_by, ist.status_id -- status_id eklendi
    FROM irp_incident_status ist
    WHERE ist.updated_at = (
        SELECT MIN(sub.updated_at)
        FROM irp_incident_status sub
        WHERE sub.incident_id = ist.incident_id
    )
) first_status ON i.incident_id = first_status.incident_id
JOIN irp_user u ON first_status.updated_by = u.user_id -- İlk status kaydını giren kullanıcıyla birleştir
WHERE first_status.status_id = 1 -- YENİ FİLTRE: Sadece ilk status kaydı 'Pending' (status_id=1) olanları seç
";

$params = [];

// Rol bazlı filtreleme (status filtresine ek olarak uygulanır)
if ($role_id == 3) {
    // Reporter (rol_id = 3): Sadece kendi oluşturduğu (ilk status kaydını girdiği)
    // VE ilk status kaydı Pending olan incident'ları görsün
    $sql .= " AND first_status.updated_by = :user_id"; // WHERE kullanıldığı için AND ile eklenir
    $params[':user_id'] = $user_id;
}
// Diğer roller (1 ve 2): Sadece ilk status kaydı Pending olan tüm incident'lar listelenir.
// WHERE first_status.status_id = 1 koşulu tüm roller için geçerlidir.

// Sıralama
$sql .= " ORDER BY i.incident_date DESC;";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $incidents = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch data as associative array
} catch (PDOException $e) {
    // Veritabanı hatası durumunda
    echo "Database error: " . $e->getMessage();
    $incidents = [];
}

// $incidents artık ilk status kaydı Pending olan ve rol bazlı filtrelenmiş incident listesini içerir.
// ... display code ...
