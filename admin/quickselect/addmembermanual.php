<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    header('Content-Type: application/json');

    $input = json_decode(file_get_contents("php://input"), true);

    if (!isset($input['id'])) {
        echo json_encode(['success' => false, 'error' => 'Missing member ID']);
        exit;
    }

    $MemberId = $input['id'];
    $adminId = $_SESSION['account_id'];

    $query = "SELECT `MemberId` FROM `members` WHERE `MemberId` = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $MemberId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($data = $result->fetch_assoc()) {
        $checkQuery = "SELECT 1 FROM quick_select
            WHERE AdminId = ? 
            AND MemberId = ? 
            AND AddedOn >= NOW() - INTERVAL 1 DAY
            LIMIT 1";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("ss", $adminId, $MemberId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows === 0) {
            $insertQuery = "INSERT INTO `quick_select` (`AdminId`, `MemberId`, `AddedOn`) VALUES (?, ?, CURRENT_TIMESTAMP())";
            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->bind_param("ss", $adminId, $MemberId);
            $insertStmt->execute();

            echo json_encode(['success' => true, 'message' => 'Member added to Quick Select!']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Member already added in the last 24 hours.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Member not found.']);
    }

    exit;