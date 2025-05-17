<?php
require_once("includes/session_start.php");
require_once 'includes/db_connect.php'; // Your database connection file
session_start();

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page or show an error if the user is not authenticated
    header("Location: login.php"); // Adjust login page path if necessary
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // echo '<pre>';
    // var_dump($_POST); // Debugging line
    // var_dump($_FILES); // Debugging line for files
    // echo '</pre>';

    $type = $_POST['type'] ?? '';
    $severity = $_POST['severity'] ?? '';
    $description = htmlspecialchars($_POST['description'] ?? '');
    $date_input = $_POST['incident_date'] ?? '';
    // Use $_POST['assets'] directly as it's an array of asset IDs (assuming this from your DB schema)
    $assets_posted = $_POST['assets'] ?? []; // Initialize as empty array if not set
    $user_id = $_SESSION['user_id']; // We already checked if user_id exists above

    // Validate the date format (Y-m-d)
    $date = DateTime::createFromFormat('Y-m-d', $date_input);
    if (!$date || $date->format('Y-m-d') !== $date_input) {
        // Consider more user-friendly error handling than die()
        $_SESSION['error_message'] = "Invalid date format: '$date_input'";
        header("Location: create_new_report.php"); // Redirect back to the form
        exit();
    }
    // The database column is DATE, so just store the date part
    $incident_date = $date->format('Y-m-d');

    // Get type_id
    $stmt = $pdo->prepare("SELECT type_id FROM irp_type WHERE type_name = ?");
    $stmt->execute([$type]);
    $type_id = $stmt->fetchColumn();
    if (!$type_id) {
        $_SESSION['error_message'] = "Invalid incident type selected.";
        header("Location: create_new_report.php");
        exit();
    }

    // Get severity_id
    $stmt = $pdo->prepare("SELECT severity_id FROM irp_severity WHERE severity_name = ?");
    $stmt->execute([$severity]);
    $severity_id = $stmt->fetchColumn();
    if (!$severity_id) {
        $_SESSION['error_message'] = "Invalid severity level selected.";
        header("Location: create_new_report.php");
        exit();
    }

    // --- File Upload Validation (Check before DB insertion) ---
    // Store file details temporarily
    $file_upload_success = false;
    $file_details = null;

    if (isset($_FILES['uploaded_file']) && $_FILES['uploaded_file']['error'] === UPLOAD_ERR_OK) {
        $allowed_ext = ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg', 'txt', 'zip', 'tar', 'gz']; // Added more common incident response file types
        $file_info = pathinfo($_FILES['uploaded_file']['name']);
        $ext = strtolower($file_info['extension'] ?? ''); // Use ?? '' for robustness

        if (!in_array($ext, $allowed_ext)) {
            $_SESSION['error_message'] = "Invalid file type. Allowed types: " . implode(', ', $allowed_ext);
            header("Location: create_new_report.php");
            exit();
        }

        // Max file size 10MB (adjusted from 5MB as 5MB can be small)
        if ($_FILES['uploaded_file']['size'] > 10 * 1024 * 1024) {
            $_SESSION['error_message'] = "File is too large. Max 10MB allowed.";
            header("Location: create_new_report.php");
            exit();
        }

        // Store details to handle later
        $file_details = $_FILES['uploaded_file'];
        $file_upload_success = true; // Mark that a valid file is ready to be processed
    } elseif (isset($_FILES['uploaded_file']) && $_FILES['uploaded_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Handle other potential upload errors
        $_SESSION['error_message'] = "File upload error: Code " . $_FILES['uploaded_file']['error'];
        header("Location: create_new_report.php");
        exit();
    }


    // --- Start Transaction (Optional but Recommended for Data Integrity) ---
    // If one step fails after another succeeds, you might get inconsistent data.
    // A transaction ensures all steps complete successfully or none do.
    $pdo->beginTransaction();

    try {
        // --- 1. Insert into incidents table ---
        $stmt = $pdo->prepare("INSERT INTO irp_incident (type_id, severity_id, description, incident_date) VALUES (?, ?, ?, ?)");
        $stmt->execute([$type_id, $severity_id, $description, $incident_date]);
        $incident_id = $pdo->lastInsertId();

        if (!$incident_id) {
            throw new Exception("Failed to insert incident.");
        }

        // --- 2. Insert into irp_incident_asset table ---
        if (!empty($assets_posted)) {
            // Prepare the insert statement outside the loop
            $asset_stmt = $pdo->prepare("INSERT INTO irp_incident_asset (incident_id, asset_id) VALUES (?, ?)");
            foreach ($assets_posted as $asset_id) {
                // Validate $asset_id if necessary (e.g., check if it exists in irp_asset)
                // For this example, we'll assume the IDs from the form are valid
                if (filter_var($asset_id, FILTER_VALIDATE_INT)) {
                    $asset_stmt->execute([$incident_id, $asset_id]);
                }
            }
        }


        // --- 3. Insert into irp_incident_status table (Initial 'Pending' status) ---
        // We need the ID of this status entry to link comments/attachments
        $stmt = $pdo->prepare("INSERT INTO irp_incident_status (status_id, incident_id, updated_by, updated_at ) VALUES (?, ?, ?, CURRENT_TIMESTAMP())");
        $stmt->execute([1, $incident_id, $user_id]);
        $incident_status_id = $pdo->lastInsertId(); // Get the ID of the status update

        if (!$incident_status_id) {
            throw new Exception("Failed to insert initial incident status.");
        }

        // --- 4. Handle File Upload (if a valid file was provided) ---
        if ($file_upload_success && $file_details) {
            $base_upload_dir = "uploads/";
            // Create the incident-specific directory
            $incident_upload_dir = $base_upload_dir . $incident_id . "/";

            // Ensure the base upload directory exists
            if (!is_dir($base_upload_dir)) {
                mkdir($base_upload_dir, 0775, true); // Create recursively with decent permissions
            }

            // Ensure the incident-specific directory exists
            if (!is_dir($incident_upload_dir)) {
                if (!mkdir($incident_upload_dir, 0775, true)) { // Create recursively
                    throw new Exception("Failed to create incident upload directory: " . $incident_upload_dir);
                }
            }

            // Generate a unique filename within the incident directory
            $file_info = pathinfo($file_details['name']);
            $ext = strtolower($file_info['extension'] ?? '');
            $file_name = uniqid('attachment_') . '.' . $ext; // Add prefix for clarity
            $target_file_path = $incident_upload_dir . $file_name;

            // Move the uploaded file
            if (!move_uploaded_file($file_details['tmp_name'], $target_file_path)) {
                throw new Exception("Failed to move uploaded file.");
            }

            // Insert the file path into the irp_attachment table
            // Link it to the initial incident_status_id
            $stmt = $pdo->prepare("INSERT INTO irp_attachment (incident_status_id, file_path) VALUES (?, ?)");
            $stmt->execute([$incident_status_id, $target_file_path]);

            // Optional: Check if attachment insertion was successful
            if ($pdo->lastInsertId() === false) {
                // This might happen if the insert query failed, though execute should throw an exception
                // Can add logging here if needed
            }
        }

        // --- 5. Commit the transaction if all steps were successful ---
        $pdo->commit();

        // --- 6. Success message and redirect ---
        echo '<script>alert("Incident report submitted successfully (ID: <?=$incident_id ?>)");</script>';
        header("Location: view_reports.php"); // Redirect to view reports page
        exit();
    } catch (Exception $e) {
        // --- Rollback transaction on failure ---
        $pdo->rollBack();
        echo '<script>alert("Cannot add incident report. Error: <?=$e?>");</script>';

        header("Location: report_incident.php"); // Redirect back to the form
        exit();
    }
} else {
    // Not a POST request
    // Consider redirecting to the form page or showing a specific message
    header("Location: report_incident.php"); // Redirect to your form page
    exit();
}
