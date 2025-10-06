<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/membercommon.php");
    include("../common/permissions.php");

    if (!in_array("Demerits", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    if (isset($_GET['did'])) {
        $getDemeritId = $_GET['did'];

        $demerit_sql = "SELECT DemeritId FROM demerits WHERE DemeritId = ?";
        if ($stmt = $conn->prepare($demerit_sql)) {
            $stmt->bind_param("i", $getDemeritId);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $delete_sql = "DELETE FROM demerits WHERE DemeritId = ?";
                if ($stmt_delete = $conn->prepare($delete_sql)) {
                    $stmt_delete->bind_param("i", $getDemeritId);
                    if ($stmt_delete->execute()) {
                        $_SESSION['successMessage'] = "<div class='message success'>Demerit successfully removed.</div>";
                    } else {
                        $_SESSION['errorMessage'] = "<div class='message error'>Failed to remove demerit. Please try again.</div>";
                    }
                    $stmt_delete->close();
                }
            } else {
                $_SESSION['errorMessage'] = "<div class='message error'>Demerit not found.</div>";
            }
            $stmt->close();
        }

        header("Location: ../members/demerits.php?id=$getMemberId");
        exit();
    } else {
        $_SESSION['errorMessage'] = "<div class='message error'>Invalid demerit ID.</div>";
        header("Location: ../members/demerits.php?id=$getMemberId");
        exit();
    }