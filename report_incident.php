<?php
require_once("includes/session_start.php");
require_once("includes/template_header.php");
require_once("includes/template_navbar.php");

require_once 'includes/db_connect.php';
require_once 'includes/log_visit.php';
$username = $_SESSION["username"];
$user_id = $_SESSION["user_id"];
$role_id = $_SESSION["role_id"];
$fname = $_SESSION["fname"];
$lname = $_SESSION["lname"];
$email = $_SESSION["email"];

?>
<div class="d-flex mt-5 mt-sm-0">
    <div class="container">
        <div class="row">
            <h1 class="text-center pt-3">Submit an incident report</h1>
        </div>
        <hr>
        <form action="submit_report.php" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="type" class="form-label">Type</label>
                <select name="type" id="type" class="form-control" required>
                    <option value="">-- Select Type --</option>
                    <option value="Denial of service">Denial of service</option>
                    <option value="Insider threats">Insider threats</option>
                    <option value="Man-in-the-middle">Man-in-the-middle</option>
                    <option value="Password attack">Password attack</option>
                    <option value="Phishing attacks">Phishing attacks</option>
                    <option value="Privilege escalation">Privilege escalation</option>
                    <option value="Ransomware">Ransomware</option>
                    <option value="Unauthorized access attacks">Unauthorized access attacks</option>
                    <option value="Theft">Theft</option>
                </select>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="severity" class="form-label">Severity</label>
                    <select name="severity" id="severity" class="form-control" required>
                        <option value="">-- Select Severity --</option>
                        <option value="Low">Low (minimal impact)</option>
                        <option value="Medium">Medium (compromise some confidentiality, integrity or availability)</option>
                        <option value="High">High (partial loss of confidentiality, integrity or availability)</option>
                        <option value="Critical">Critical (catastrophic consequences)</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <h6>Date</h6>
                    <input type="text" id="datepicker" name="incident_date" class="form-control" placeholder="Select Date" required>
                </div>
            </div>

            <div class="mb-3">
                <div class="mb-3" id="assets-checkbox-group"> <label class="form-label">Affected Assets</label><br>
                    <?php
                    $getAssetsStmt = $pdo->prepare("SELECT * FROM irp_asset ORDER BY asset_name");
                    $getAssetsStmt->execute();
                    $assets = $getAssetsStmt->fetchAll();
                    foreach ($assets as $asset): ?>
                        <div class="form-check form-check-inline"> <input class="form-check-input asset-checkbox" type="checkbox" name="assets[]" value="<?= htmlspecialchars($asset["asset_id"]) ?>" id="asset_<?= htmlspecialchars($asset["asset_id"]) ?>">
                            <label class="form-check-label" for="asset_<?= htmlspecialchars($asset["asset_id"]) ?>"><?= htmlspecialchars($asset["asset_name"]) ?></label>
                        </div>
                    <?php endforeach; ?>

                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" style="resize: none;" rows="4" required></textarea>
                </div>
                <div class="mb-3">
                    <label for="inputGroupFile02" class="form-label"><strong>File Upload (Optional)</strong></label>
                    <div class="input-group">
                        <input type="file" class="form-control" id="inputGroupFile02" name="uploaded_file"
                            accept=".pdf,.doc,.docx,.png,.jpg,.jpeg">
                        <label class="input-group-text" for="inputGroupFile02">Upload</label>
                    </div>
                    <div class="form-text">Accepted formats: PDF, DOC, DOCX, PNG, JPG (max 5MB)</div>
                </div>

                <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>
</div>

<script>
    $(document).ready(function() {
        $("#myInput").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("#myTable tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
    });
</script>

<script>
    $(document).ready(function() {
        var oneYearAgo = new Date();
        oneYearAgo.setFullYear(oneYearAgo.getFullYear() - 1); // Set date to one year ago

        flatpickr("#datepicker", {
            theme: "dark",
            dateFormat: "Y-m-d",
            minDate: oneYearAgo,
            maxDate: 'today',
        });
    });
</script>

<script>
    document.querySelector('form').addEventListener('submit', function(e) {
        var dateInput = document.getElementById("datepicker");
        // Check if the date is empty
        if (!dateInput.value) {
            alert("Please select a date.");
            e.preventDefault(); // Prevent form submission
        }
    });
</script>


<?php
require_once("includes/template_footer.php");
?>