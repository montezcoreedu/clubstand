<?php
    include("../../dbconnection.php");
    include("../common/session.php");

    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        header("Location: index.php");
        exit;
    }

    $adminId = $_SESSION['account_id'];
    $memberId = intval($_GET['id']);

    $checkQuery = "SELECT SearchId FROM recent_searches 
        WHERE AdminId = ? AND MemberId = ? 
        AND SearchTime >= NOW() - INTERVAL 1 DAY";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ii", $adminId, $memberId);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($searchId);
        $stmt->fetch();
        $stmt->close();

        $updateQuery = "UPDATE recent_searches SET SearchTime = NOW() WHERE SearchId = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("i", $searchId);
        $updateStmt->execute();
        $updateStmt->close();
    } else {
        $stmt->close();

        $insertQuery = "INSERT INTO recent_searches (AdminId, MemberId, SearchTime) VALUES (?, ?, NOW())";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param("ii", $adminId, $memberId);
        $insertStmt->execute();
        $insertStmt->close();
    }

    header("Location: ../members/lookup.php?id=$memberId");
    exit;