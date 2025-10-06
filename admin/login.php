<?php
    session_start();

    if (isset($_SESSION['AccountId'])) {
        header('Location: home/index.php');
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClubStand Membership Administrator Login</title>
    <link rel="stylesheet" href="css/fonts.css">
    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
    <style>
        body {
            font-family: avenir-regular, sans-serif;
            font-size: 0.920rem;
            color: rgb(48, 48, 48);
            line-height: 1.4;
            padding: 0;
            margin: 0;
        }

        *, ::after, ::before {
            box-sizing: border-box;
            -webkit-box-sizing: border-box;
            -moz-box-sizing: border-box;
            font-synthesis: none;
            text-rendering: optimizeLegibility;
        }

        h1, h2, h3, h4, h5, h6, th, b {
            margin-top: 0;
            margin-bottom: 0;
        }

        .message {
            border-left: 4px solid;
            padding: 6px 10px;
            margin-bottom: 1rem;
        }

        .error {
            border-left-color: rgb(148, 104, 3);
            background-color: rgb(243, 236, 187);
        }

        .auth-form {
            width: 380px;
            padding-top: 4rem;
            padding-bottom: 1.5rem;
            padding-left: 1rem;
            padding-right: 1rem;
            margin: 0 auto;
        }

        .auth-form .img-header {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            margin-bottom: 0.8rem;
        }

        .auth-form .img-header img {
            max-width: 120px;
        }

        .auth-form h2 {
            letter-spacing: -0.8px;
            text-align: center;
            margin-bottom: 1rem;
        }

        .auth-form form {
            padding: 1rem;
            background-color: #f6f8fa;
            border: 0.0625rem solid rgba(209, 217, 224, 0.7);
            border-radius: 0.375rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
        }

        .form-group input {
            display: block;
            width: 100%;
            font-family: inherit;
            font-size: 14px;
            line-height: 20px;
            padding: 5px 12px;
            border: 1px solid rgb(209, 217, 224);
            border-radius: 6px;
            margin-top: 0.25rem;
            margin-bottom: 1rem;
        }

        .auth-form button {
            display: block;
            width: 100%;
            font-family: inherit;
            font-size: 14px;
            color: rgb(255, 255, 255);
            line-height: 20px;
            padding: 8px 1rem;
            background-color: rgb(29, 82, 188);
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .auth-form button:hover {
            background-color: rgb(10, 62, 166);
        }
    </style>
</head>
<body>
    <div class="auth-form">
        <div class="img-header">
            <img src="images/FBLAStagLogo.png" alt="" class="img-header">
        </div>
        <h2>Login to the Admin Cloud</h2>
        <?php
            if (isset($_SESSION['error_message'])) {
                echo '<div class="message error">' . $_SESSION['error_message'] . '</div>';
                unset($_SESSION['error_message']);
            }
        ?>
        <?php if (isset($_GET['timeout'])): ?>
            <div class="message error">You’ve been logged out due to inactivity.</div>
        <?php endif; ?>
        <form action="authenticate.php" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>
            </div>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>