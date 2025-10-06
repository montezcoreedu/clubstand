<?php
    include '../../dbconnection.php';
    include '../common/session.php';
    include '../common/membercommon.php';
    include("../common/permissions.php");

    if (!in_array("Member Membership", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    $getTerminationId = (int) $_GET['tid'];

    $updateTerminationStmt = $conn->prepare("UPDATE termination SET TerminationStatus = 2 WHERE TerminationId = ?");
    $updateMemberStmt = $conn->prepare("UPDATE members SET MemberStatus = 1 WHERE MemberId = ?");

    if ($updateTerminationStmt && $updateMemberStmt) {
        $updateTerminationStmt->bind_param("i", $getTerminationId);
        $terminationSuccess = $updateTerminationStmt->execute();

        $updateMemberStmt->bind_param("i", $getMemberId);
        $memberSuccess = $updateMemberStmt->execute();

        if ($terminationSuccess && $memberSuccess) {
            $_SESSION['successMessage'] = "<div class='message success'>Membership restored successfully!</div>";
        } else {
            $_SESSION['errorMessage'] = "<div class='message error'>Something went wrong. Please try again.</div>";
        }
    } else {
        $_SESSION['errorMessage'] = "<div class='message error'>Failed to prepare database statements.</div>";
    }

    header("Location: ../members/membership.php?id=$getMemberId");
    exit;