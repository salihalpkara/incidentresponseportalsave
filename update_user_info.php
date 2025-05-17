<?php
require_once("includes/session_start.php");
require_once("includes/db_connect.php");

$updateUserStmt = $pdo->prepare("UPDATE irp_user SET fname = :fname, lname = :lname, username = :username, email = :email, role_id = :role_id WHERE user_id = :user_id;");
try {
    $updateUserStmt->execute([
        ":fname" => ucfirst(htmlspecialchars($_POST["fname"])),
        ":lname" => ucfirst(htmlspecialchars($_POST["lname"])),
        ":username" => htmlspecialchars($_POST["username"]),
        ":email" => htmlspecialchars($_POST["email"]),
        ":role_id" => htmlspecialchars($_POST["role"]),
        ":user_id" => htmlspecialchars($_POST["user_id"]),
    ]);
    if (!empty($_POST["password"])) {
        $updatePasswordStmt = $pdo->prepare("UPDATE irp_user SET password = :password WHERE user_id = :user_id;");
        $updatePasswordStmt->execute([
            ":password" => password_hash(htmlspecialchars($_POST["password"]), PASSWORD_DEFAULT),
            ":user_id" => htmlspecialchars($_POST["user_id"]),
        ]);
    }
    if ($_SESSION["user_id"] != htmlspecialchars($_POST["user_id"])) {
        header("Location: manage_users.php?updateSuccess=1");
        exit;
    } else {
        header("Location: profile.php?id=" . $_SESSION["user_id"] . "&updateSuccess=1");
        exit;
    }
} catch (PDOException $e) {
    if ($_SESSION["user_id"] != htmlspecialchars($_POST["user_id"])) {
        header("Location: manage_users.php?updateSuccess=0");
        exit;
    } else {
        header("Location: profile.php?id=" . $_SESSION["user_id"] . "&updateSuccess=0");
        exit;
    }
}
