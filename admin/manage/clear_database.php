<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    if (!in_array("Clear Records", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    try {
        $conn->query("SET FOREIGN_KEY_CHECKS = 0");

        $conn->query("UPDATE attendance SET Archived = 1");
        $conn->query("UPDATE communityservices SET Archived = 1");
        $conn->query("UPDATE demerits SET Archived = 1");
        $conn->query("UPDATE excuse_requests SET Archived = 1");
        $conn->query("UPDATE memberservicehours SET Archived = 1");
        $conn->query("UPDATE membertransferhours SET Archived = 1");
        
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");

        $_SESSION['successMessage'] = "<div class='message success'>Database records have successfully been archived the new membership year.</div>";
    } catch (Exception $e) {
        $_SESSION['errorMessage'] = "<div class='message error'>Failed to clear database: " . $e->getMessage() . "</div>";
    }

    header("Location: index.php");
    exit();