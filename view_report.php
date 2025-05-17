<?php
require_once("includes/session_start.php");
require_once("includes/db_connect.php");
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page or show an error if the user is not authenticated
    header("Location: login.php"); // Adjust login page path if necessary
    exit();
}

// Get the incident ID from the URL
$incident_id = $_GET["id"] ?? null; // Use ?? null for safety
if (!$incident_id || !filter_var($incident_id, FILTER_VALIDATE_INT)) {
    // Handle case where no valid ID is provided
    $_SESSION['error_message'] = "Invalid incident ID provided.";
    header("Location: view_reports.php"); // Redirect to the reports list
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Formdan gelen status ve comment verilerini alın
    $status_id = $_POST["status"] ?? null;
    $comment_text = trim($_POST['comment_text'] ?? '');

    // Validate status ID
    if (!$status_id || !filter_var($status_id, FILTER_VALIDATE_INT)) {
        $_SESSION['error_message'] = "Invalid status selected.";
        header("Location: view_report.php?id=" . $incident_id);
        exit();
    }


    // --- File Upload Validation (Check before DB insertion) ---
    // Store file details temporarily for the *new* status update
    $file_upload_success_for_new_status = false;
    $new_status_file_details = null;

    if (isset($_FILES['uploaded_file']) && $_FILES['uploaded_file']['error'] === UPLOAD_ERR_OK) {
        $allowed_ext = ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg', 'txt', 'zip', 'tar', 'gz', 'log']; // Added more common types
        $file_info = pathinfo($_FILES['uploaded_file']['name']);
        $ext = strtolower($file_info['extension'] ?? '');

        if (!in_array($ext, $allowed_ext)) {
            $_SESSION['error_message'] = "Invalid file type for attachment. Allowed types: " . implode(', ', $allowed_ext);
            // Don't exit here immediately, proceed without file if it's invalid
            // Or handle this differently depending on if attachment is mandatory
            $file_upload_success_for_new_status = false; // Ensure it's false
        } elseif ($_FILES['uploaded_file']['size'] > 15 * 1024 * 1024) { // Increased max size slightly
            $_SESSION['error_message'] = "File is too large. Max 15MB allowed for attachment.";
            $file_upload_success_for_new_status = false; // Ensure it's false
        } else {
            // Store details to handle later
            $new_status_file_details = $_FILES['uploaded_file'];
            $file_upload_success_for_new_status = true; // Mark that a valid file is ready
        }
    } elseif (isset($_FILES['uploaded_file']) && $_FILES['uploaded_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Handle other potential upload errors
        $_SESSION['error_message'] = "File upload error for attachment: Code " . $_FILES['uploaded_file']['error'];
        $file_upload_success_for_new_status = false; // Ensure it's false
    }


    // --- Start Transaction ---
    $pdo->beginTransaction();

    try {
        // 1. irp_incident_status kaydını ekleyin
        // Ensure status_id is a valid integer
        $stmtStatusInsert = $pdo->prepare("INSERT INTO irp_incident_status (status_id, incident_id, updated_by) VALUES (:status_id, :incident_id, :updated_by);");
        $stmtStatusInsert->execute([
            ":status_id" => (int)$status_id,
            ":incident_id" => $incident_id,
            ":updated_by" => $_SESSION["user_id"]
        ]);

        // 2. Yeni eklenen status kaydının ID'sini alın
        $new_incident_status_id = $pdo->lastInsertId();

        if (!$new_incident_status_id) {
            throw new Exception("Failed to insert new incident status.");
        }

        // 3. Eğer yorum alanı doluysa, irp_comment tablosuna kaydı ekleyin
        if (!empty($comment_text)) {
            $stmtCommentInsert = $pdo->prepare("INSERT INTO irp_comment (incident_status_id, comment_text) VALUES (:incident_status_id, :comment_text);");
            $stmtCommentInsert->execute([
                ":incident_status_id" => $new_incident_status_id, // Yeni aldığımız ID'yi kullanıyoruz
                ":comment_text" => $comment_text
            ]);
            // Optional: Check $pdo->lastInsertId() for comment insertion success
        }

        // 4. Handle File Upload for the NEW status update (if a valid file was provided)
        if ($file_upload_success_for_new_status && $new_status_file_details) {
            $base_upload_dir = "uploads/";
            // Create the incident-specific directory using the incident_id from GET/URL
            $incident_upload_dir = $base_upload_dir . $incident_id . "/";

            // Ensure the base upload directory exists
            if (!is_dir($base_upload_dir)) {
                mkdir($base_upload_dir, 0775, true);
            }

            // Ensure the incident-specific directory exists
            if (!is_dir($incident_upload_dir)) {
                if (!mkdir($incident_upload_dir, 0775, true)) {
                    throw new Exception("Failed to create incident upload directory for new status: " . $incident_upload_dir);
                }
            }

            // Generate a unique filename within the incident directory
            $file_info = pathinfo($new_status_file_details['name']);
            $ext = strtolower($file_info['extension'] ?? '');
            $file_name = uniqid('status_attachment_') . '.' . $ext; // Prefix for clarity
            $target_file_path = $incident_upload_dir . $file_name;

            // Move the uploaded file
            if (!move_uploaded_file($new_status_file_details['tmp_name'], $target_file_path)) {
                throw new Exception("Failed to move uploaded file for new status.");
            }

            // Insert the file path into the irp_attachment table
            // Link it to the NEWLY created incident_status_id
            $stmtAttachmentInsert = $pdo->prepare("INSERT INTO irp_attachment (incident_status_id, file_path) VALUES (?, ?)");
            $stmtAttachmentInsert->execute([$new_incident_status_id, $target_file_path]);

            // Optional: Check $pdo->lastInsertId() for attachment insertion success
        }


        // İşlemi onaylayın (Commit)
        $pdo->commit();

        // Başarılı olduğunda bir mesaj gösterebilir
        $_SESSION['success_message'] = "Incident status updated successfully.";
    } catch (\Throwable $th) {
        // Bir hata olursa işlemi geri alın (Rollback)
        $pdo->rollBack();

        // Hata yönetimi
        $error_message = "An error occurred while updating the status: " . $th->getMessage();
        error_log("Error updating status or adding comment/attachment: " . $th->getMessage());
        $_SESSION['error_message'] = $error_message;
        // For debugging, you might want to re-throw or display detailed error
        // echo $error_message; exit();
    }

    // redirect the browser to the same page to prevent form resubmission
    header("Location: view_report.php?id=" . $incident_id);
    exit();
}


// --- Fetch Incident Details ---
$getReportInfoStmt = $pdo->prepare("SELECT
    i.incident_id,
    i.description,
    i.incident_date,
    t.type_name,
    s.severity_name,
    GROUP_CONCAT(DISTINCT a.asset_name ORDER BY a.asset_name SEPARATOR ', ') AS affected_assets,
    -- Subquery to get info of the FIRST status update (considered as 'created')
    (
        SELECT CONCAT(u.fname, ' ', u.lname)
        FROM irp_incident_status ist_first
        JOIN irp_user u ON ist_first.updated_by = u.user_id
        WHERE ist_first.incident_id = i.incident_id
        ORDER BY ist_first.updated_at ASC, ist_first.incident_status_id ASC
        LIMIT 1
    ) AS created_by_name,
     (
        SELECT u.user_id
        FROM irp_incident_status ist_first
        JOIN irp_user u ON ist_first.updated_by = u.user_id
        WHERE ist_first.incident_id = i.incident_id
        ORDER BY ist_first.updated_at ASC, ist_first.incident_status_id ASC
        LIMIT 1
    ) AS created_by_id,
    (
        SELECT ist_first.updated_at
        FROM irp_incident_status ist_first
        WHERE ist_first.incident_id = i.incident_id
        ORDER BY ist_first.updated_at ASC, ist_first.incident_status_id ASC
        LIMIT 1
    ) AS created_at,
    -- Subquery to get info of the LATEST status update
    (
        SELECT st.status_name
        FROM irp_incident_status ist_latest
        JOIN irp_status st ON ist_latest.status_id = st.status_id
        WHERE ist_latest.incident_id = i.incident_id
        ORDER BY ist_latest.updated_at DESC, ist_latest.incident_status_id DESC
        LIMIT 1
    ) AS latest_status_name,
     (
        SELECT ist_latest.incident_status_id
        FROM irp_incident_status ist_latest
        WHERE ist_latest.incident_id = i.incident_id
        ORDER BY ist_latest.updated_at DESC, ist_latest.incident_status_id DESC
        LIMIT 1
    ) AS latest_incident_status_id -- Get ID of latest status
FROM irp_incident i
JOIN irp_type t ON i.type_id = t.type_id
JOIN irp_severity s ON i.severity_id = s.severity_id
LEFT JOIN irp_incident_asset ia ON i.incident_id = ia.incident_id
LEFT JOIN irp_asset a ON ia.asset_id = a.asset_id
WHERE i.incident_id = :incident_id
GROUP BY i.incident_id, i.description, i.incident_date, t.type_name, s.severity_name
LIMIT 1;");

try {
    $getReportInfoStmt->execute([":incident_id" => $incident_id]);
    $reportInfo = $getReportInfoStmt->fetch(PDO::FETCH_ASSOC); // Use FETCH_ASSOC for key access

    if (!$reportInfo) {
        // Handle case where incident ID does not exist
        $_SESSION['error_message'] = "Incident report not found.";
        header("Location: view_reports.php");
        exit();
    }
} catch (\Throwable $th) {
    error_log("Error fetching incident details: " . $th->getMessage());
    $_SESSION['error_message'] = "An error occurred while fetching incident details.";
    header("Location: view_reports.php");
    exit();
}


// --- Fetch ALL Status Updates for this Incident ---
// Modified query to also select incident_status_id for later attachment fetching
$getStatusStmt = $pdo->prepare("SELECT
                                        ist.incident_status_id, -- Added this
                                        c.comment_text,
                                        st.status_name,
                                        st.status_id,
                                        u.fname,
                                        u.lname,
                                        u.user_id,
                                        ist.updated_by,
                                        ist.updated_at
                                    FROM
                                        irp_incident_status ist
                                    LEFT JOIN irp_comment c
                                        ON c.incident_status_id = ist.incident_status_id
                                    JOIN irp_status st
                                        ON st.status_id = ist.status_id
                                    JOIN irp_user u
                                        ON u.user_id = ist.updated_by
                                    WHERE ist.incident_id = :incident_id ORDER BY ist.updated_at ASC, ist.incident_status_id ASC"); // Added secondary sort for consistency

try {
    $getStatusStmt->execute([":incident_id" => $incident_id]);
    $statuses = $getStatusStmt->fetchAll(PDO::FETCH_ASSOC); // Use FETCH_ASSOC
} catch (\Throwable $e) {
    error_log("Error fetching incident statuses: " . $e->getMessage());
    // Decide how to handle this - maybe show the report details but no status updates
    $statuses = []; // Ensure $statuses is an array even if query fails
}

// --- Fetch Attachments for EACH status update ---
// Prepare a statement to fetch attachments for a given incident_status_id
$getAttachmentsStmt = $pdo->prepare("SELECT attachment_id, file_path FROM irp_attachment WHERE incident_status_id = ?");

// --- Display Logic ---
require_once("includes/template_header.php"); // Assuming this includes your HTML <head> and opening <body>

// Extract values safely using fetched data
$report_incident_id = $reportInfo["incident_id"];
$report_description = $reportInfo["description"];
$report_incident_date = $reportInfo["incident_date"];
$report_type_name = $reportInfo["type_name"];
$report_severity_name = $reportInfo["severity_name"];
$report_affected_assets = $reportInfo["affected_assets"];
$report_created_by_name = $reportInfo["created_by_name"];
$report_created_by_id = $reportInfo["created_by_id"];
$report_latest_status_name = $reportInfo["latest_status_name"];
$report_created_at = $reportInfo["created_at"];

// Authorization check
// Only allow Reporter (role_id 3) to view reports they created
if ($_SESSION["role_id"] == 3 && $report_created_by_id != $_SESSION["user_id"]): ?>

    <div class="container d-flex align-items-center" style="min-height: 100dvh;">
        <div class="d-block w-100">
            <h1 class="text-center text-danger"><i class="bi bi-ban fs-1"></i></h1>
            <h3 class="text-center text-danger">You are not authorized to view this report. <br> If you think this is a mistake, please contact your administrator.</h3>
            <div class="d-flex">
                <a href="dashboard.php" class="btn btn-primary btn-lg m-auto mt-5"><i class="bi bi-arrow-left me-2"></i>Go back to Dashboard</a>
            </div>
        </div>
    </div>

<?php else:
    require_once("includes/template_navbar.php"); // Assuming this includes your navigation bar

    // Format dates
    $rawDate = $report_incident_date;
    $date = new DateTime($rawDate);
    // Removed time from incident_date as per table definition
    $formattedDate = $date->format('d.m.Y');

    $rawCreationDate = $report_created_at;
    $creationDate = new DateTime($rawCreationDate);
    $formattedCreationDate = $creationDate->format('d.m.Y | H:i'); // Use H:i for 24-hour format



?>

    <div class="container py-3">
        <?php
        // --- Display Success/Error Messages ---
        if (isset($_SESSION['success_message'])) {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['success_message']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            unset($_SESSION['success_message']);
        }
        if (isset($_SESSION['error_message'])) {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['error_message']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            unset($_SESSION['error_message']);
        } ?>
        <div class="d-flex align-items-center mb-3">
            <a href="view_reports.php" class="btn btn-secondary d-flex align-items-center me-3"><i class="bi bi-arrow-left me-2"></i>Back to Reports</a>
            <h1 class="d-inline">Incident Details #<?= htmlspecialchars($report_incident_id) ?></h1>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center mb-3">
                    <a href="profile.php?id=<?= htmlspecialchars($report_created_by_id) ?>" class="fs-5 btn btn-link p-0 me-auto text-decoration-none text-light"><i class="bi bi-person-circle me-2"></i><?= htmlspecialchars($report_created_by_name) ?></a>
                    <p class="ms-auto mb-0"><strong>Reported at: </strong><?= htmlspecialchars($formattedCreationDate) ?></p>
                </div>
                <p class="mb-0"><strong>Occurred on: </strong><?= htmlspecialchars($formattedDate) ?></p>
                <p class="mt-3 mb-0">
                    <strong>Status: </strong>
                    <span class="<?php
                                    switch ($report_latest_status_name):
                                        case "In progress":
                                            echo "text-warning";
                                            break;
                                        case "Resolved":
                                            echo "text-success";
                                            break;
                                        case "Pending":
                                            echo "text-danger";
                                            break;
                                        default:
                                            echo "text-secondary";
                                            break; // Default for unknown status
                                    endswitch;
                                    ?>">
                        <?= htmlspecialchars($report_latest_status_name) ?>
                    </span>
                </p>
                <p class="mt-3 mb-0"><strong>Type: </strong><?= htmlspecialchars($report_type_name) ?></p>
                <p class="mt-3 mb-0"><strong>Severity: </strong><?= htmlspecialchars($report_severity_name) ?></p>
                <p class="mt-3 mb-0"><strong>Affected Assets: </strong><?= htmlspecialchars($report_affected_assets ?? "No assets specified.") ?></p>

                <p class="mt-3 mb-0"><strong>Description: </strong><?= htmlspecialchars($report_description) ?></p>

                <?php
                // Find the incident_status_id of the very first status update
                // We can use the 'created_by_id' and 'created_at' from $reportInfo to help find it,
                // or just query for the first one directly. A direct query is simpler here.
                $getFirstStatusIdStmt = $pdo->prepare("SELECT incident_status_id
                                                        FROM irp_incident_status
                                                        WHERE incident_id = ?
                                                        ORDER BY updated_at ASC, incident_status_id ASC
                                                        LIMIT 1");
                $getFirstStatusIdStmt->execute([$incident_id]);
                $first_incident_status_id = $getFirstStatusIdStmt->fetchColumn();

                $initial_attachments = [];
                if ($first_incident_status_id) {
                    $getAttachmentsStmt->execute([$first_incident_status_id]);
                    $initial_attachments = $getAttachmentsStmt->fetchAll(PDO::FETCH_ASSOC);
                }
                ?>

                <?php if (!empty($initial_attachments)): ?>
                    <p class="mt-3 mb-0"><strong>Initial Attachments:</strong></p>
                    <ul>
                        <?php foreach ($initial_attachments as $attachment):
                            $fileName = basename($attachment['file_path']); // Get just the filename
                            // Ensure path is safe for display/link
                            $safeFilePath = htmlspecialchars($attachment['file_path']);
                        ?>
                            <li><a href="<?= $safeFilePath ?>" target="_blank" download><?= htmlspecialchars($fileName) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

            </div>

            <div class="card-body">
                <h5 class="card-title mb-3">Status History</h5>
                <?php if (empty($statuses)): ?>
                    <p>No status updates available yet.</p>
                <?php else: ?>
                    <?php $first_status_displayed = false; ?>
                    <?php foreach ($statuses as $status): ?>
                        <?php
                        // Skip the very first status as it's represented by the "Reported at" info in the header
                        // This check assumes the first status is always the one with the earliest timestamp/lowest ID
                        if (!$first_status_displayed && $status['user_id'] == $report_created_by_id && $status['updated_at'] == $report_created_at && ($status['status_id'] == 1 || count($statuses) == 1)) {
                            $first_status_displayed = true;
                            // Fetch and display attachments specifically for this first status here if needed,
                            // or rely on the "Initial Attachments" section above.
                            // We've already handled initial attachments above, so just continue to the next status.
                            continue;
                        }
                        $first_status_displayed = true; // Ensure flag is set after the first potential skip
                        ?>
                        <div class="card mb-3">
                            <div class="card-header <?php
                                                    switch ($status["status_id"]):
                                                        case 2:
                                                            echo "bg-warning-subtle";
                                                            break;
                                                        case 3:
                                                            echo "bg-success-subtle";
                                                            break;
                                                        case 1:
                                                            echo "bg-secondary-subtle";
                                                            break; // Style for 'Pending' if shown in history
                                                        default:
                                                            echo "";
                                                            break;
                                                    endswitch;
                                                    ?>">
                                <div class="d-flex align-items-center">
                                    <a href="profile.php?id=<?= htmlspecialchars($status["user_id"]) ?>" class="fs-6 btn btn-link p-0 me-auto text-decoration-none text-light"><i class="bi bi-person-circle me-2"></i><?= htmlspecialchars($status["fname"] . " " . $status["lname"]) ?></a>
                                    <?php
                                    $rawStatusDate = $status["updated_at"];
                                    $statusDate = new DateTime($rawStatusDate);
                                    $formattedStatusDate = $statusDate->format('d.m.Y | H:i');
                                    ?>
                                    <p class="ms-auto mb-0"><strong>Updated at: </strong><?= htmlspecialchars($formattedStatusDate) ?></p>
                                </div>
                            </div>
                            <div class="card-body">
                                <p class="mb-2 <?php
                                                switch ($status["status_id"]):
                                                    case 2:
                                                        echo "text-warning";
                                                        break;
                                                    case 3:
                                                        echo "text-success";
                                                        break;
                                                    case 1:
                                                        echo "text-danger";
                                                        break;
                                                    default:
                                                        echo "text-secondary";
                                                        break;
                                                endswitch;
                                                ?>"><span><strong class="text-light">Status: </strong></span><?= htmlspecialchars($status["status_name"]) ?></p>

                                <p class="mb-2"><strong>Comment:</strong> <?= nl2br(htmlspecialchars($status["comment_text"] ?? "**No comment**")) ?></p>

                                <?php
                                $attachments_for_this_status = [];
                                if ($status['incident_status_id']) {
                                    $getAttachmentsStmt->execute([$status['incident_status_id']]);
                                    $attachments_for_this_status = $getAttachmentsStmt->fetchAll(PDO::FETCH_ASSOC);
                                }
                                ?>
                                <?php if (!empty($attachments_for_this_status)): ?>
                                    <p class="mt-2 mb-0"><strong>Attachments:</strong></p>
                                    <ul>
                                        <?php foreach ($attachments_for_this_status as $attachment):
                                            $fileName = basename($attachment['file_path']);
                                            $safeFilePath = htmlspecialchars($attachment['file_path']);
                                        ?>
                                            <li><a href="<?= $safeFilePath ?>" target="_blank" download><?= htmlspecialchars($fileName) ?></a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <form action="view_report.php?id=<?= htmlspecialchars($incident_id) ?>" method="POST" class="d-none" id="updateStatusForm" enctype="multipart/form-data">
                    <div class="card mt-4">
                        <div class="card-header">
                            <div class="d-flex align-items-center">
                                <a href="profile.php?id=<?= htmlspecialchars($_SESSION["user_id"]) ?>" class="fs-5 btn btn-link p-0 me-auto text-decoration-none text-light"><i class="bi bi-person-circle me-2"></i><?= htmlspecialchars($_SESSION["fname"] . " " . $_SESSION["lname"]) ?></a>
                                <p class="ms-auto mb-0">Updating now...</p>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-4 col-sm-12">
                                    <label for="status" class="form-label">Status: </label>
                                    <select class="form-select" name="status" id="status" required>
                                        <?php
                                        // Fetch all statuses to populate the select box dynamically
                                        $getAllStatusesStmt = $pdo->query("SELECT status_id, status_name FROM irp_status ORDER BY status_id ASC");
                                        $allStatuses = $getAllStatusesStmt->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($allStatuses as $statusOption) {
                                            // You might want to pre-select the current latest status,
                                            // but for a new update, often selecting 'In progress' or next logical step is better.
                                            $selected = ($statusOption['status_name'] == $report_latest_status_name) ? 'selected' : ''; // Pre-select current? Or use logic?
                                            echo '<option value="' . htmlspecialchars($statusOption['status_id']) . '" ' . $selected . '>' . htmlspecialchars($statusOption['status_name']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <label for="comment" class="form-label">Comment (optional) : </label>
                                    <textarea class="form-control" placeholder="Add a comment" id="comment" name="comment_text" style="height: 100px"></textarea>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <label for="uploaded_file" class="form-label">Attachment (optional) : </label>
                                    <input type="file" class="form-control" id="uploaded_file" name="uploaded_file">
                                    <small class="form-text text-muted">Max 15MB. Allowed types: pdf, doc, docx, png, jpg, jpeg, txt, zip, tar, gz, log.</small>
                                </div>
                            </div>
                            <div class="row mt-4">
                                <div class="col-12 d-flex justify-content-end">
                                    <button type="button" class="btn btn-secondary me-2" onclick="hideForm();">Cancel</button>
                                    <button type="submit" class="btn btn-success">Submit Update</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

            </div>
            <div class="card-footer d-flex">
                <button class="btn btn-success m-auto" id="updateStatusToggler" onclick="makeFormVisible();"><i class="bi bi-plus-lg me-2"></i>Update Incident Status</button>
            </div>
        </div>

    </div>
<?php endif; ?>

<script>
    const updateStatusToggler = document.getElementById("updateStatusToggler");
    const updateStatusForm = document.getElementById("updateStatusForm");
    const cardFooter = document.querySelector('.card-footer.d-flex'); // Get the footer

    function makeFormVisible() {
        updateStatusForm.classList.remove("d-none");
        updateStatusToggler.classList.add("d-none");
        // You might want to hide the card-footer once the form is shown
        if (cardFooter) {
            cardFooter.classList.add("d-none");
        }
    }

    function hideForm() {
        updateStatusForm.classList.add("d-none");
        updateStatusToggler.classList.remove("d-none");
        // Show the card-footer again
        if (cardFooter) {
            cardFooter.classList.remove("d-none");
        }
        // Optional: Reset form fields
        updateStatusForm.reset();
    }

    // Check if there are any session messages and remove them after a few seconds
    // This requires your template_header to include Bootstrap's JS bundle
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            setTimeout(function() {
                new bootstrap.Alert(alert).close();
            }, 5000); // Close after 5 seconds
        });
    });
</script>

<?php require_once("includes/template_footer.php");
?>