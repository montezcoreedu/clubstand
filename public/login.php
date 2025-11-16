<?php
    session_start();

    if (isset($_SESSION['MemberId'])) {
        header('Location: home/index.php');
        exit();
    }

    ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClubStand Membership Member Login</title>
    <link rel="stylesheet" href="css/fonts.css">
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="auth-form">
        <div class="img-header">
            <img src="images/FBLAStagLogo.png" alt="" class="img-header">
        </div>
        <h2>Login to the Member Cloud</h2>
        <?php
            if (isset($_SESSION['success_message'])) {
                echo '<div class="message success">' . $_SESSION['success_message'] . '</div>';
                unset($_SESSION['success_message']);
            }

            if (isset($_SESSION['error_message'])) {
                echo '<div class="message error">' . $_SESSION['error_message'] . '</div>';
                unset($_SESSION['error_message']);
            }
        ?>
        <?php if (isset($_GET['timeout'])): ?>
            <div class="message error">You’ve been logged out due to inactivity.</div>
        <?php endif; ?>
        <form action="authenticate.php" method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>
            </div>
            <div class="button-group">
                <button type="submit">Login</button>
                <a href="createaccount.php" class="btn-link">Create Account</a>
            </div>
        </form>
    </div>
</body>
</html>