<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    if (!in_array("Returning Members Form", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    $query = "SELECT SettingValue FROM settings WHERE SettingKey = 'RegistrationForm' LIMIT 1";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();

    if ($row) {
        $currentStatus = $row['SettingValue'];

        $newStatus = $currentStatus == 1 ? 0 : 1;

        $update = "UPDATE settings SET SettingValue = ? WHERE SettingKey = 'RegistrationForm'";
        $stmt = $conn->prepare($update);
        $stmt->bind_param("i", $newStatus);
        $stmt->execute();

        if ($newStatus == 0) {
            $updateMembers = "UPDATE members SET MemberStatus = 4 WHERE MemberStatus = 6";
            if ($conn->query($updateMembers)) {
                echo json_encode(['status' => $newStatus, 'membersUpdated' => true]);
            } else {
                echo json_encode(['status' => $newStatus, 'membersUpdated' => false, 'error' => $conn->error]);
            }
        } else {
            echo json_encode(['status' => $newStatus]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Setting not found']);
    }