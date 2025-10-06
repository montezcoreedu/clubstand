<?php
    include("../../dbconnection.php");
    include("../common/session.php");

    $fields = [
        'MaxProbationDays'
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
        $_SESSION['successMessage'] = "<div class='message success'>Chapter discipline information updated successfully!</div>";
    } else {
        $_SESSION['errorMessage'] = $_SESSION['errorMessage'] ?? "<div class='message error'>Something went wrong while updating chapter discipline info.</div>";
    }

    header("Location: index.php#settings");
    exit;