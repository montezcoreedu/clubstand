<?php
    session_start();
    include('../dbconnection.php');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $registrationKey = trim($_POST['registrationKey']);
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        $usernameCheckQuery = "SELECT 1 FROM members WHERE Username = ?";
        $usernameCheckStmt = $conn->prepare($usernameCheckQuery);
        $usernameCheckStmt->bind_param("s", $username);
        $usernameCheckStmt->execute();
        $usernameCheckStmt->store_result();

        if ($usernameCheckStmt->num_rows > 0) {
            $_SESSION['error_message'] = "That username is already taken. Please choose another.";
            header("Location: login.php");
            exit();
        }

        $usernameCheckStmt->close();

        $query = "SELECT MemberId, RegistrationCompleted FROM members WHERE RegistrationKey = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $registrationKey);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $row = $result->fetch_assoc()) {
            if ($row['RegistrationCompleted']) {
                $_SESSION['error_message'] = "An account has already been created with this key.";
                header("Location: login.php");
                exit();
            }

            $memberId = $row['MemberId'];

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $updateQuery = "UPDATE members SET Username = ?, Password = ?, RegistrationCompleted = 1 WHERE MemberId = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("ssi", $username, $hashedPassword, $memberId);

            if ($updateStmt->execute()) {
                $_SESSION['success_message'] = "Account created successfully! You can now log in.";
                header("Location: login.php");
                exit();
            } else {
                $_SESSION['error_message'] = "Something went wrong creating the account.";
                header("Location: login.php");
                exit();
            }

        } else {
            $_SESSION['error_message'] = "Invalid registration key.";
            header("Location: login.php");
            exit();
        }
        
        if (isset($stmt)) {
            $stmt->close();
        }
        $conn->close();
    }
    
    $_SESSION['error_message'] = "Invalid request method.";
    header("Location: login.php");
    exit();