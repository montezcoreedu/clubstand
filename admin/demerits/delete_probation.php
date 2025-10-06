<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/membercommon.php");
    include("../common/permissions.php");

    if (!in_array("Demerits", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    if (isset($_GET['pid'])) {
        $getProbationId = $_GET['pid'];

        $demerit_sql = "SELECT ProbationId FROM probation WHERE ProbationId = ?";
        if ($stmt = $conn->prepare($demerit_sql)) {
            $stmt->bind_param("i", $getProbationId);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $delete_sql = "DELETE FROM probation WHERE ProbationId = ?";
                if ($stmt_delete = $conn->prepare($delete_sql)) {
                    $stmt_delete->bind_param("i", $getProbationId);
                    if ($stmt_delete->execute()) {
                        $_SESSION['successMessage'] = "<div class='message success'>Probation successfully removed.</div>";
                    } else {
                        $_SESSION['errorMessage'] = "<div class='message error'>Failed to remove probation period. Please try again.</div>";
                    }
                    $stmt_delete->close();
                } else {
                    $_SESSION['errorMessage'] = "<div class='message error'>Failed to prepare deletion statement.</div>";
                }
            
                $updateMemberSql = "UPDATE members SET MemberStatus = 1 WHERE MemberId = ?";
                if ($stmt_update = $conn->prepare($updateMemberSql)) {
                    $stmt_update->bind_param("i", $getMemberId);
                    $stmt_update->execute();
                    $stmt_update->close();
                } else {
                    $_SESSION['errorMessage'] = "<div class='message error'>Failed to update member status.</div>";
                }
            } else {
                $_SESSION['errorMessage'] = "<div class='message error'>Probation period not found.</div>";
            }
            $stmt->close();
        }

        header("Location: ../members/demerits.php?id=$getMemberId");
        exit();
    } else {
        $_SESSION['errorMessage'] = "<div class='message error'>Invalid probation ID.</div>";
        header("Location: ../members/demerits.php?id=$getMemberId");
        exit();
    }