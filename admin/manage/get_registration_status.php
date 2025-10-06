<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    if (!in_array("Returning Members Form", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    $query = "SELECT SettingValue FROM settings WHERE SettingKey = 'RegistrationForm'";
    $result = $conn->query($query);

    $status = 0;

    if ($row = $result->fetch_assoc()) {
        $status = (int)$row['SettingValue'];
    }

    header('Content-Type: application/json');
    echo json_encode(['status' => $status]);
    exit();
