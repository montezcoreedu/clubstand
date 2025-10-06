<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    $data = json_decode(file_get_contents("php://input"));
    $SelectId = $data->select_id ?? null;

    if ($SelectId) {
        $adminId = $_SESSION['account_id'];

        $stmt = $conn->prepare("DELETE FROM quick_select WHERE AdminId = ? AND SelectId = ?");
        $stmt->bind_param("ii", $adminId, $SelectId);
        $stmt->execute();

        $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM quick_select WHERE AdminId = ? AND AddedOn >= NOW() - INTERVAL 1 DAY");
        $countStmt->bind_param("i", $adminId);
        $countStmt->execute();
        $result = $countStmt->get_result();
        $count = $result->fetch_assoc()['count'];

        echo json_encode([
            'success' => true,
            'count' => $count,
            'member_id' => $SelectId
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No Select ID provided']);
    }