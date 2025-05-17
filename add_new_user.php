<?php
require_once("includes/session_start.php");
require_once("includes/db_connect.php");

var_dump($_POST);

$fname =  ucfirst(htmlspecialchars($_POST["fname"]));
$lname =  ucfirst(htmlspecialchars($_POST["lname"]));
$username =  htmlspecialchars($_POST["username"]);
$email =  htmlspecialchars($_POST["email"]);
$password =  password_hash(htmlspecialchars($_POST["password"]), PASSWORD_DEFAULT);
$role_id =  htmlspecialchars($_POST["role_id"]);

$addUserStmt = $pdo->prepare("INSERT INTO irp_user (fname, lname, username, password, email, role_id) VALUES (:fname, :lname, :username, :password, :email, :role_id);");
try {
    $addUserStmt->execute([":fname" => $fname, ":lname" => $lname, ":username" => $username, ":password" => $password, ":email" => $email, ":role_id" => $role_id,]);
    header("Location: manage_users.php?addSuccess=1");
} catch (PDOException $e) {
    header("Location: manage_users.php?addSuccess=0&error=" . urlencode($e->getMessage()));
    exit;
}
