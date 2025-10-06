<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    if (!in_array("Accounts Security", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    $fields = ['EnableAdminLogin','EnableMemberLogin','MaxLoginAttempts','LockoutDuration','AutoLogoutMinutes'];

    foreach ($fields as $field) {
        $value = isset($_POST[$field]) ? $_POST[$field] : '0';
        $stmt = $conn->prepare("UPDATE settings SET SettingValue = ? WHERE SettingKey = ?");
        $stmt->bind_param("ss", $value, $field);
        $stmt->execute();
    }

    $_SESSION['successMessage'] = "<div class='message success'>Security settings updated successfully!</div>";
    header("Location: ../accounts/");
    exit;