<?php
require_once("includes/session_start.php");
require_once("includes/db_connect.php");
require_once 'includes/log_visit.php';

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$error = "";
$success = "";

if (isset($_GET["updateSuccess"])) {
    if ($_GET["updateSuccess"] == 1) {
        $success = "User updated successfully.";
    } else {
        $error = "Error updating user";
    }
}
if (isset($_GET["deleteSuccess"])) {
    if ($_GET["deleteSuccess"] == 1) {
        $success = "User deleted successfully.";
    } else {
        $error = "Error deleting user";
    }
}
if (isset($_GET["addSuccess"])) {
    if ($_GET["addSuccess"] == 1) {
        $success = "User added successfully.";
    } else {
        $error = "Error adding user";
    }
}



if ($_SESSION["role_id"] != 1) {
    header("Location: dashboard.php");
    exit();
}
$getUsersStmt = $pdo->prepare("SELECT user_id, username, fname, lname, role_name FROM irp_user u JOIN irp_role r ON u.role_id = r.role_id ORDER BY user_id DESC");
$getUsersStmt->execute();
$users = $getUsersStmt->fetchAll();

require_once("includes/template_header.php");
require_once("includes/template_navbar.php");
?>


<div class="container py-3">
    <h1 class="text-center">Manage Users</h1>
    <div class="row">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success text-center"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
    </div>

    <div class="row">
        <div class="col-lg-6 col-sm-12">
            <div class="card m-3">
                <div class="card-header">
                    <h5 class="text-center">New User</h5>
                </div>
                <div class="card-body">
                    <form action="add_new_user.php" method="post">
                        <div class="container">
                            <form>
                                <div class="mb-3 row">
                                    <label
                                        for="fname"
                                        class="col-4 col-form-label">First Name:</label>
                                    <div
                                        class="col-8">
                                        <input
                                            style="text-transform: capitalize;"
                                            required
                                            type="text"
                                            class="form-control"
                                            name="fname"
                                            id="fname"
                                            placeholder="John" />
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label
                                        for="lname"
                                        class="col-4 col-form-label">Last Name:</label>
                                    <div
                                        class="col-8">
                                        <input
                                            style="text-transform: capitalize;"
                                            required
                                            type="text"
                                            class="form-control"
                                            name="lname"
                                            id="lname"
                                            placeholder="Doe" />
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label
                                        for="username"
                                        class="col-4 col-form-label">Username:</label>
                                    <div
                                        class="col-8">
                                        <input
                                            required
                                            type="text"
                                            class="form-control"
                                            name="username"
                                            id="username"
                                            placeholder="reporter_johndoe" />
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label
                                        for="password"
                                        class="col-4 col-form-label">Password:</label>
                                    <div
                                        class="col-8">
                                        <input
                                            required
                                            type="text"
                                            class="form-control"
                                            name="password"
                                            id="password"
                                            placeholder="j0hn_Do3s?5ecRet!p4ssWord" />
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label
                                        for="email"
                                        class="col-4 col-form-label">Email:</label>
                                    <div
                                        class="col-8">
                                        <input
                                            required
                                            type="email"
                                            class="form-control"
                                            name="email"
                                            id="email"
                                            placeholder="johndoe@irp.com" />
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label
                                        for="role_id"
                                        class="col-4 col-form-label">Role:</label>
                                    <div
                                        class="col-8">
                                        <select required class="form-select" name="role_id" id="role_id">
                                            <option value="1">System Administrator</option>
                                            <option value="2">Incident Responder</option>
                                            <option value="3" selected>Incident Reporter</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3 row d-flex">
                                    <button type="submit" class="btn btn-success m-auto"><i class="bi bi-person-add me-2"></i>Add User</button>
                                </div>

                            </form>
                        </div>

                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-sm-12">
            <div class="card m-3">
                <div class="card-header">
                    <h5 class="text-center">View and Manage Users</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="users">
                            <thead>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Role</th>
                                <th>Action</th>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= $user["fname"] ?></td>
                                        <td><?= $user["lname"] ?></td>
                                        <td><?= $user["role_name"] ?></td>
                                        <td><a href="profile.php?id=<?= $user["user_id"] ?>" class="btn btn-primary">Manage</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                </div>

            </div>

        </div>
    </div>

</div>

<?php require_once("includes/template_footer.php"); ?>