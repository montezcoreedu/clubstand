<?php
    include("../../dbconnection.php");
    include("../common/session.php");

    $sql = "SHOW COLUMNS FROM members";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $fields = [];
        while($row = $result->fetch_assoc()) {
            $fields[] = $row['Field'];
        }

        echo json_encode($fields);
    } else {
        echo "No fields found";
    }