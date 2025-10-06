<?php
    session_start();
    include('../settings.php');

    if (isset($_SESSION['account_id'])) {
        $current_time = time();

        if (isset($_SESSION['last_activity'])) {
            $inactive_time = $current_time - $_SESSION['last_activity'];

            if ($inactive_time > ($auto_logout_minutes * 60)) {
                session_unset();
                session_destroy();
                $_SESSION['error_message'] = 'Your session has expired due to inactivity. Please log in again.';
                header('Location: ../login.php?timeout=1');
                exit();
            }
        }

        $_SESSION['last_activity'] = $current_time;
    } else {
        header('Location: ../login.php');
        exit();
    }
