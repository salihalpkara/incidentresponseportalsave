<?php
require_once("includes/session_start.php");
if (empty($_SESSION["user_id"])) {
    header("Location: login.php");
}
require_once("includes/db_connect.php");
require_once("includes/template_header.php");
require_once("includes/template_navbar.php");
$user_id = intval($_GET["id"]);

$error = "";
$success = "";

if (isset($_GET["updateSuccess"])) {
    if ($_GET["updateSuccess"] == 1) {
        $success = "User updated successfully.";
    } else {
        $error = "Error updating user.<br/> Error: " . htmlspecialchars($_GET["error"]);
    }
}


$getUserInfoStmt = $pdo->prepare("SELECT u.user_id, u.username, u.fname, u.lname, u.email, r.role_name, r.role_id FROM irp_user u JOIN irp_role r ON u.role_id = r.role_id WHERE user_id = :user_id");
$getUserInfoStmt->execute([":user_id" => $user_id]);
$userInfo = $getUserInfoStmt->fetch();

?>

<var id="roleId" class="d-none"><?= $userInfo["role_id"] ?></var>
<var id="viewerRoleId" class="d-none"><?= $_SESSION["role_id"] ?></var>


<div class="container py-3" style="height: calc(100dvh - 74px);">
    <div class="row h-100">
        <div class="m-auto d-flex flex-column justify-content-center h-100" style="width: fit-content;">
            <div class="row">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success text-center"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
            </div>
            <div class="card">
                <div class="card-header">
                    <h2 class="text-center">User Details</h2>
                </div>
                <form id="userInfoForm" action="update_user_info.php" method="post" class="p-0">
                    <input type="text" name="user_id" value="<?= $userInfo["user_id"] ?>" class="d-none">
                    <div class="card-body">
                        <table class="me-5 ms-2">
                            <tr>
                                <td><label for="fname" class="form-label me-3">First Name: </label></td>
                                <td><input data-initial="<?= htmlspecialchars($userInfo["fname"]) ?>" id="fname" name="fname" type="text" class="form-control m-3 checkinitial" value="<?= $userInfo["fname"] ?>" placeholder="First Name" disabled readonly></td>
                                <td><label for="lname" class="form-label ms-5">Last Name: </label></td>
                                <td><input data-initial="<?= htmlspecialchars($userInfo["lname"]) ?>" id="lname" name="lname" type="text" class="form-control m-3 checkinitial" value="<?= $userInfo["lname"] ?>" placeholder="Last Name" disabled readonly></td>
                            </tr>
                            <tr>
                                <td><label for="username" class="form-label me-3">Username: </label></td>
                                <td><input data-initial="<?= htmlspecialchars($userInfo["username"]) ?>" id="username" name="username" type="text" class="form-control m-3 checkinitial" value="<?= $userInfo["username"] ?>" placeholder="Username" disabled readonly></td>
                                <td><label for="email" class="form-label ms-5">Email: </label></td>
                                <td><input data-initial="<?= htmlspecialchars($userInfo["email"]) ?>" id="email" name="email" type="text" class="form-control m-3 checkinitial" value="<?= $userInfo["email"] ?>" placeholder="example@irp.com" disabled readonly></td>
                            </tr>
                            <tr>
                                <td colspan="2"><label for="role" class="form-label me-3" style="display: block; text-align:right;">Role: </label></td>
                                <td colspan="2"><select data-initial="<?= htmlspecialchars($userInfo["role_id"]) ?>" class="form-select m-3 checkinitial" name="role" id="role" disabled>
                                        <option value="1">System Administrator</option>
                                        <option value="2">Incident Responder</option>
                                        <option value="3">Incident Reporter</option>
                                    </select></td>
                            </tr>

                            <tr class="d-none" id="passwordChangePart">
                                <td colspan="2">
                                    <label for="password" class="form-label me-3" style="display: block; text-align:right;">Change Password: </label>
                                </td>
                                <td colspan="2">
                                    <input id="password" name="password" type="text" class="form-control m-3 checkinitial" value="" data-initial="" placeholder="New Password">
                                </td>
                            </tr>
                        </table>
                        <div class="d-flex"></div>
                    </div>
                    <?php if ($_SESSION["user_id"] == $userInfo["user_id"] || $_SESSION["role_id"] == 1): ?>
                        <div class="card-footer d-flex">
                            <?php if ($_SESSION["user_id"] != $userInfo["user_id"]): ?>
                                <a id="deleteUser" href="delete_user.php?deleter=<?= $_SESSION["user_id"] ?>&deleting=<?= $userInfo["user_id"] ?>" class="btn btn-danger m-auto d-none"><i class="bi bi-person-fill-dash me-2"></i>Delete User</a>
                            <?php endif; ?>
                            <button type="button" id="editToggler" onclick="toggleEditing();" class="btn btn-primary m-auto"><i class="bi bi-pencil-square me-2"></i>Edit Info</button>
                            <button type="button" id="quitEdit" onclick="quitEditing();" class="d-none btn btn-secondary ms-auto me-1"><i class="bi bi-x-lg me-2"></i>Cancel</button>
                            <button type="submit" id="submitFormButton" class="d-none btn btn-success me-auto ms-1" disabled><i class="bi bi-check-lg me-2"></i>Submit</button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const options = document.getElementsByTagName("option");
    const roleId = document.getElementById("roleId");
    const viewerRoleId = document.getElementById("viewerRoleId");
    const roleDropdown = document.getElementById("role");
    const deleteUserButton = document.getElementById("deleteUser");

    if (viewerRoleId.innerText == "1") {
        if (deleteUserButton != null) {
            deleteUserButton.classList.remove("d-none");
        }
    }

    for (let i = 0; i < options.length; i++) {
        if (options[i].value == roleId.innerText) {
            options[i].selected = true;
        }
    }
    const inputsToWatch = userInfoForm.querySelectorAll('.checkinitial');
    const quitEdit = document.getElementById("quitEdit");

    function toggleEditing() {
        const editToggler = document.getElementById("editToggler");

        const inputsToWatch = userInfoForm.querySelectorAll('.checkinitial');

        inputsToWatch.forEach(input => {
            input.removeAttribute("readonly");
            input.removeAttribute("disabled");
        });
        passwordChangePart.classList.remove("d-none");
        editToggler.classList.add("d-none");
        submitFormButton.classList.remove("d-none");
        quitEdit.classList.remove("d-none");
        if (viewerRoleId.innerText != "1") {
            roleDropdown.setAttribute("disabled", "true");
        }
    }

    function quitEditing() {
        inputsToWatch.forEach(input => {
            input.setAttribute("readonly", "true");
            input.setAttribute("disabled", "true");
        });
        passwordChangePart.classList.add("d-none");
        editToggler.classList.remove("d-none");
        submitFormButton.classList.add("d-none");
        quitEdit.classList.add("d-none");
    }

    document.addEventListener("DOMContentLoaded", function() {
        const passwordChangePart = document.getElementById("passwordChangePart");
        const submitFormButton = document.getElementById("submitFormButton");
        const userInfoForm = document.getElementById("userInfoForm");

        function checkForChanges() {
            let changesDetected = false;
            for (let i = 0; i < inputsToWatch.length; i++) {
                const input = inputsToWatch[i];
                if (input.value !== input.getAttribute('data-initial')) {
                    changesDetected = true;
                }
            }
            submitFormButton.disabled = !changesDetected;
        }
        userInfoForm.addEventListener('input', function(event) {
            let isWatchedInput = false;
            for (let i = 0; i < inputsToWatch.length; i++) {
                if (inputsToWatch[i] === event.target) {
                    isWatchedInput = true;
                    break;
                }
            }
            if (isWatchedInput) {
                checkForChanges();
            }
        });
    });
</script>
<?php require_once("includes/template_footer.php"); ?>