<?php
$pageTitle = "Login";
require_once("includes/log_visit.php");
require_once("includes/db_connect.php");

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
$error = '';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM irp_user WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['fname'] = $user['fname'];
        $_SESSION['lname'] = $user['lname'];
        $_SESSION['email'] = $user['email'];


        if (!empty($_POST['remember'])) {
            setcookie("remember_username", $user['username'], time() + (86400 * 30), "/");
        } else {
            setcookie("remember_username", "", time() - 3600, "/");
        }

        header("Location: dashboard.php");
        exit;
    } else {
        $error = 'Incorrect username or password.';
    }
}

require_once("includes/template_header.php");

?>
<div class="container">
    <div class="login-wrapper">
        <div class="card shadow p-4 login-card">
            <h1 class="text-center"><i class="bi bi-speedometer2"></i></h1>

            <h3 class="text-center mb-3 fw-bold">Welcome to Incident Response Portal</h3>


            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>


            <form method="post" action="login.php" id="loginForm">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" name="username" id="username" required autofocus
                        value="<?= isset($_COOKIE['remember_username']) ? htmlspecialchars($_COOKIE['remember_username']) : '' ?>">
                </div>
                <label for="password" class="form-label">Password</label>
                <div class="input-group mb-3">
                    <input id="password" type="password" name="password" class="form-control" required autofocus>
                    <button class="btn btn-outline-secondary" type="button" id="passwordToggleButton"><i class="bi bi-eye-slash" id="passwordToggleIcon"></i></button>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" name="remember" id="remember"
                        <?= isset($_COOKIE['remember_username']) ? 'checked' : '' ?>>

                    <label class="form-check-label" for="remember">Remember Me</label>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3" id="loginBtn">Login</button>
        </div>
        </form>
    </div>
</div>

</div>



<script>
    // Otomatik odaklama ve enter tuşu kontrolü
    document.addEventListener('DOMContentLoaded', function() {
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        const form = document.getElementById('loginForm');

        usernameInput.focus();

        usernameInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                passwordInput.focus();
            }
        });

        passwordInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                form.submit();
            }
        });

    });
    const passwordToggleIcon = document
        .querySelector('#passwordToggleIcon');
    const passwordToggleButton = document.querySelector("#passwordToggleButton")
    const password = document.querySelector('#password');
    passwordToggleButton.addEventListener('click', () => {
        // Toggle the type attribute using
        // getAttribure() method
        const type = password
            .getAttribute('type') === 'password' ?
            'text' : 'password';
        password.setAttribute('type', type);
        // Toggle the eye and bi-eye icon
        if (passwordToggleIcon.classList.contains('bi-eye-slash')) {
            passwordToggleIcon.classList.replace('bi-eye-slash', 'bi-eye');
        } else {
            passwordToggleIcon.classList.replace('bi-eye', 'bi-eye-slash');
        }
    });
</script>

<?php include 'includes/template_footer.php'; ?>