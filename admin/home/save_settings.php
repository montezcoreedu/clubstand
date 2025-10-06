<?php
    include("../../dbconnection.php");
    include("../common/session.php");

    $fields = [
        'AdvisorEmail', 'ChapterName', 'MaxDemerits', 'MaxExcuseRequests', 
        'MaxGradeLevel', 'MaxServiceHours', 'MaxUnexcusedAbsence', 
        'MaxUnexcusedTardy', 'MinGradeLevel', 'OfficerEmail', 
        'Schools', 'Website'
    ];

    $allSuccess = true;

    foreach ($fields as $field) {
        $value = isset($_POST[$field]) && $_POST[$field] !== '' ? $_POST[$field] : null;

        $stmt = $conn->prepare("UPDATE chapter_settings SET SettingValue = ? WHERE SettingKey = ?");
        if (!$stmt) {
            $_SESSION['errorMessage'] = "<div class='message error'>Failed to prepare statement for field: $field</div>";
            $allSuccess = false;
            break;
        }

        if ($value === null) {
            $stmt->bind_param("ss", $value, $field);
        } else {
            $stmt->bind_param("ss", $value, $field);
        }

        if (!$stmt->execute()) {
            $_SESSION['errorMessage'] = "<div class='message error'>Failed to update $field: " . $stmt->error . "</div>";
            $allSuccess = false;
            break;
        }
    }

    if ($allSuccess) {
        $_SESSION['successMessage'] = "<div class='message success'>Chapter information updated successfully!</div>";
    } else {
        $_SESSION['errorMessage'] = $_SESSION['errorMessage'] ?? "<div class='message error'>Something went wrong while updating chapter info.</div>";
    }

    header("Location: settings.php");
    exit;