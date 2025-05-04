<?php
require_once("includes/session_start.php");
require_once("includes/template_header.php");
require_once 'includes/db_connect.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    echo '<pre>';
    var_dump($_POST); // Debugging line
    echo '</pre>';

    $type = $_POST['type'] ?? '';
    $severity = $_POST['severity'] ?? '';
    $description = htmlspecialchars($_POST['description'] ?? '');
    $date_input = $_POST['incident_date'] ?? '';
    $assets = isset($_POST['assets']) ? implode(', ', $_POST['assets']) : null;
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        die("User ID not found in session.");
    }


    // Validate the date format (Y-m-d)
    $date = DateTime::createFromFormat('Y-m-d', $date_input);
    if (!$date || $date->format('Y-m-d') !== $date_input) {
        die("Invalid date format: '$date_input'"); // Throw an error if the format is wrong
    }

    // If valid, format it for DB insertion
    $incident_date = $date->format('Y-m-d H:i:s'); // Prepare it for DB (with time)

    // Get type_id
    $stmt = $pdo->prepare("SELECT type_id FROM irp_type WHERE type_name = ?");
    $stmt->execute([$type]);
    $type_id = $stmt->fetchColumn();
    if (!$type_id) {
        die("Invalid type selected.");
    }

    // Get severity_id
    $stmt = $pdo->prepare("SELECT severity_id FROM irp_severity WHERE severity_name = ?");
    $stmt->execute([$severity]);
    $severity_id = $stmt->fetchColumn();
    if (!$severity_id) {
        die("Invalid severity selected.");
    }

    // Handle file upload (optional)
    $uploaded_path = null;
    if (isset($_FILES['uploaded_file']) && $_FILES['uploaded_file']['error'] === UPLOAD_ERR_OK) {
        $allowed_ext = ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg'];
        $file_info = pathinfo($_FILES['uploaded_file']['name']);
        $ext = strtolower($file_info['extension']);

        if (!in_array($ext, $allowed_ext)) {
            die("Invalid file type.");
        }

        if ($_FILES['uploaded_file']['size'] > 5 * 1024 * 1024) {
            die("File is too large. Max 5MB allowed.");
        }

        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        $file_name = uniqid() . "." . $ext;
        $uploaded_path = $target_dir . $file_name;

        if (!move_uploaded_file($_FILES['uploaded_file']['tmp_name'], $uploaded_path)) {
            die("File upload failed.");
        }
    }

    // Insert into incidents table (and optionally log the uploaded file path and assets)
    $stmt = $pdo->prepare("INSERT INTO irp_incident (type_id, severity_id, description, incident_date) VALUES (?, ?, ?, ?)");
    $stmt->execute([$type_id, $severity_id, $description, $incident_date]);
    $incident_id = $pdo->lastInsertId();

    // Insert into irp_incident_status table
    $stmt = $pdo->prepare("INSERT INTO irp_incident_status (status_id, incident_id, updated_by ) VALUES (?, ?, ?)");
    $stmt->execute([1, $incident_id, $user_id]);

    echo "<script>alert('Incident report submitted successfully.'); window.location.href = 'dashboard.php';</script>";
} else {
    echo "Invalid request.";
}
