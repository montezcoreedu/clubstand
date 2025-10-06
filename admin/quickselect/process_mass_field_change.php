<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $fieldName = $_POST['FieldName'];
        $newFieldValue = $_POST['NewFieldValue'];
        $overwriteData = $_POST['OverwriteData'] ?? 0;

        if (empty($fieldName) || empty($newFieldValue)) {
            echo "Field name and new value are required!";
            exit;
        }

        $adminId = $_SESSION['account_id'];

        if ($overwriteData) {
            $updateQuery = "
                UPDATE members m
                INNER JOIN quick_select q ON q.MemberId = m.MemberId
                SET m.`$fieldName` = ?
                WHERE q.AdminId = ?
                AND q.AddedOn >= NOW() - INTERVAL 1 DAY
                AND m.`$fieldName` IS NOT NULL
            ";
        } else {
            $updateQuery = "
                UPDATE members m
                INNER JOIN quick_select q ON q.MemberId = m.MemberId
                SET m.`$fieldName` = ?
                WHERE q.AdminId = ?
                AND q.AddedOn >= NOW() - INTERVAL 1 DAY
                AND m.`$fieldName` IS NULL
            ";
        }

        if ($stmt = $conn->prepare($updateQuery)) {
            $stmt->bind_param("si", $newFieldValue, $adminId);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                echo "Field updated successfully!";
            } else {
                echo "No changes were made.";
            }
            $stmt->close();
        } else {
            echo "Error preparing the query: " . $conn->error;
        }
    }