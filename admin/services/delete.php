<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/membercommon.php");
    include("../common/permissions.php");

    if (!in_array("Community Services", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    if (!empty($_GET['sid']) && is_numeric($_GET['sid'])) {
        $getServiceId = intval($_GET['sid']);
    
        $service_sql = "SELECT EntryId FROM memberservicehours WHERE EntryId = ?";
        if ($stmt = $conn->prepare($service_sql)) {
            $stmt->bind_param("i", $getServiceId);
            $stmt->execute();
            $stmt->store_result();
    
            if ($stmt->num_rows > 0) {
                $delete_sql = "DELETE FROM memberservicehours WHERE EntryId = ?";
                if ($stmt_delete = $conn->prepare($delete_sql)) {
                    $stmt_delete->bind_param("i", $getServiceId);
                    if ($stmt_delete->execute()) {
                        $_SESSION['successMessage'] = "<div class='message success'>Community service successfully removed.</div>";
                    } else {
                        $_SESSION['errorMessage'] = "<div class='message error'>Failed to remove community service. Please try again.</div>";
                    }
                    $stmt_delete->close();
                }
            } else {
                $_SESSION['errorMessage'] = "<div class='message error'>Service not found.</div>";
            }
    
            $stmt->close();
        }
    
        header("Location: ../members/services.php?id=" . urlencode($getMemberId));
        exit();
    } else {
        $_SESSION['errorMessage'] = "<div class='message error'>Invalid community service ID.</div>";
        header("Location: ../members/services.php?id=" . urlencode($getMemberId));
        exit();
    }    