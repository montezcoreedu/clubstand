<?php
    session_start();
    include('../dbconnection.php');
    include('settings.php');

    if (!$enable_admin_login) {
        $_SESSION['error_message'] = 'Admin login is currently disabled. Please contact the system administrator.';
        header('Location: login.php');
        exit();
    }

    if (isset($_POST['username']) && isset($_POST['password'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $sql = "SELECT * FROM accounts WHERE Username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $account = $result->fetch_assoc();
            $current_time = time();

            // 1. Check for admin lock
            if ($account['LockAccess'] == 1) {
                $_SESSION['error_message'] = 'Your account has been locked by an administrator.';
                header('Location: login.php');
                exit();
            }

            // 2. Check for temporary lock due to failed attempts
            if (!empty($account['LockUntil']) && $current_time < strtotime($account['LockUntil'])) {
                $_SESSION['error_message'] = 'Your account is temporarily locked due to too many failed login attempts. Try again later.';
                header('Location: login.php');
                exit();
            }

            // 3. Check password
            if (password_verify($password, $account['Password'])) {
                // Successful login: reset attempts, record login
                $update_sql = "UPDATE accounts SET FailedAttempts = 0, LockUntil = NULL, LastLogin = NOW() WHERE AccountId = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param('i', $account['AccountId']);
                $update_stmt->execute();

                $_SESSION['account_id'] = $account['AccountId'];
                $_SESSION['account_username'] = $account['Username'];
                $_SESSION['account_group'] = $account['AccountGroup'];
                $_SESSION['LastName'] = $account['LastName'];
                $_SESSION['FirstName'] = $account['FirstName'];

                header('Location: home/');
                exit();
            } else {
                // Incorrect password
                $failed_attempts = $account['FailedAttempts'] + 1;

                $update_sql = "UPDATE accounts SET FailedAttempts = ?, LastFailedLogin = NOW() WHERE AccountId = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param('ii', $failed_attempts, $account['AccountId']);
                $update_stmt->execute();

                if ($failed_attempts >= $max_login_attempts) {
                    $lock_until = date('Y-m-d H:i:s', strtotime("+{$lockout_duration} minutes"));
                    $update_sql = "UPDATE accounts SET LockUntil = ? WHERE AccountId = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param('si', $lock_until, $account['AccountId']);
                    $update_stmt->execute();

                    $_SESSION['error_message'] = 'Too many failed login attempts. You are temporarily locked out.';
                } else {
                    $_SESSION['error_message'] = 'Invalid credentials. Please try again.';
                }

                header('Location: login.php');
                exit();
            }
        } else {
            // No such account
            $_SESSION['error_message'] = 'Invalid credentials. Please try again.';
            header('Location: login.php');
            exit();
        }
    } else {
        // Missing username or password
        $_SESSION['error_message'] = 'Please enter both username and password.';
        header('Location: login.php');
        exit();
    }