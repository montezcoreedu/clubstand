<?php
    session_start();
    include('../dbconnection.php');
    include('settings.php');

    if (!$enable_member_login) {
        $_SESSION['error_message'] = 'Membership login has been disabled by your chapter advisors and officers. If you have any questions, please contact your advisor.';
        header('Location: login.php');
        exit();
    }

    if (isset($_POST['username']) && isset($_POST['password'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $sql = "SELECT * FROM members WHERE Username = ?";
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
                $update_sql = "UPDATE members SET FailedAttempts = 0, LockUntil = NULL, LastLogin = NOW() WHERE MemberId = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param('i', $account['MemberId']);
                $update_stmt->execute();

                $_SESSION['account_id'] = $account['MemberId'];
                $_SESSION['account_username'] = $account['Username'];
                $_SESSION['LastName'] = $account['LastName'];
                $_SESSION['FirstName'] = $account['FirstName'];
                $_SESSION['MemberStatus'] = $account['MemberStatus'];

                header('Location: home.php');
                exit();
            } else {
                // Incorrect password
                $failed_attempts = $account['FailedAttempts'] + 1;

                $update_sql = "UPDATE members SET FailedAttempts = ?, LastFailedLogin = NOW() WHERE MemberId = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param('ii', $failed_attempts, $account['MemberId']);
                $update_stmt->execute();

                if ($failed_attempts >= $max_login_attempts) {
                    $lock_until = date('Y-m-d H:i:s', strtotime("+{$lockout_duration} minutes"));
                    $update_sql = "UPDATE members SET LockUntil = ? WHERE MemberId = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param('si', $lock_until, $account['MemberId']);
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