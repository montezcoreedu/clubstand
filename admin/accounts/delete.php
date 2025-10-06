<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    if (!in_array("Accounts Security", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    if (isset($_GET['id'])) {
        $getAccountId = $_GET['id'];

        $account_sql = "SELECT AccountId FROM accounts WHERE AccountId = ?";
        if ($stmt = $conn->prepare($account_sql)) {
            $stmt->bind_param("i", $getAccountId);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $delete_sql = "DELETE FROM accounts WHERE AccountId = ?";
                if ($stmt_delete = $conn->prepare($delete_sql)) {
                    $stmt_delete->bind_param("i", $getAccountId);
                    if ($stmt_delete->execute()) {
                        $_SESSION['successMessage'] = "<div class='message success'>Account successfully removed.</div>";
                    } else {
                        $_SESSION['errorMessage'] = "<div class='message error'>Failed to remove account. Please try again.</div>";
                    }
                    $stmt_delete->close();
                }
            } else {
                $_SESSION['errorMessage'] = "<div class='message error'>Account not found.</div>";
            }
            $stmt->close();
        }

        header("Location: ../accounts/");
        exit();
    } else {
        $_SESSION['errorMessage'] = "<div class='message error'>Invalid account ID.</div>";
        header("Location: ../accounts/");
        exit();
    }