<?php
require_once("includes/session_start.php");
require_once("includes/db_connect.php");

if ($_SESSION["role_id"] != 1) {
    header("Location: index.php");
    exit;
}

if ($_GET["deleter"] == $_SESSION["user_id"]) {
    if (isset($_GET["deleting"])) {
        $deleting = htmlspecialchars($_GET["deleting"]);
        $deleteUserStmt = $pdo->prepare("DELETE FROM irp_user WHERE user_id = :user_id;");
        try {
            $deleteUserStmt->execute([":user_id" => $deleting]);
            header("Location: manage_users.php?deleteSuccess=1");
            exit;
        } catch (PDOException $e) {
            header("Location: manage_users.php?deleteSuccess=0");
            exit;
        }
    } else {
        header("Location: manage_users.php?deleteSuccess=0");
        exit;
    }
} else {
    header("Location: dashboard.php");
    exit;
}
