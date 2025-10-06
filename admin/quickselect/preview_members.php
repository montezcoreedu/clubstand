<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    $fieldName = isset($_GET['FieldName']) ? $_GET['FieldName'] : '';
    $newFieldValue = isset($_GET['NewFieldValue']) ? $_GET['NewFieldValue'] : '';

    if (empty($fieldName) || empty($newFieldValue)) {
        echo json_encode(["members" => []]);
        exit;
    }

    $adminId = $_SESSION['account_id'];
    $query = "SELECT m.MemberId, m.FirstName, m.LastName, m.Suffix, `$fieldName` AS CurrentValue
            FROM quick_select q
            INNER JOIN members m ON q.MemberId = m.MemberId
            WHERE q.AdminId = $adminId AND q.AddedOn >= NOW() - INTERVAL 1 DAY AND `$fieldName` IS NOT NULL
            ORDER BY m.LastName asc, m.FirstName asc";
    $result = $conn->query($query);
    $members = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $members[] = [
                "FirstName" => $row["FirstName"],
                "LastName" => $row["LastName"],
                "Suffix" => $row["Suffix"],
                "CurrentValue" => $row["CurrentValue"]
            ];
        }
    }

    echo json_encode(["members" => $members]);