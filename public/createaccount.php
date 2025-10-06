<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FBLA Membership Member Create Account</title>
    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="css/fonts.css">
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="auth-form">
        <div class="img-header">
            <img src="images/FBLAStagLogo.png" alt="" class="img-header">
        </div>
        <a href="login.php" style="display: block; width: 100%; text-align: center; margin: 1rem 0;">< Login to the Member Cloud</a>
        <h2>Create Account</h2>
        <form action="create_auth.php" method="post">
        <div class="form-group">
                <label for="registrationKey">Registration Key</label>
                <input type="text" name="registrationKey" id="registrationKey" required autofocus>
            </div>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>
            </div>
            <button type="submit">Create Account</button>
        </form>
    </div>
</body>
</html>