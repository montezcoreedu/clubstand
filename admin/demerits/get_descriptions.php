<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/chapter_settings.php");
    include("../common/permissions.php");

    if (!in_array("Demerits", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    if (!empty($_GET['CategoryName'])) {
        $catName = $_GET['CategoryName'];

        $stmt = $conn->prepare("SELECT d.DescriptionId, d.Description  
            FROM demerit_descriptions d
            INNER JOIN demerit_categories c ON d.CategoryId = c.CategoryId
            WHERE c.CategoryName = ?
            ORDER BY d.Description");
        $stmt->bind_param("s", $catName);
        $stmt->execute();
        $result = $stmt->get_result();

        $descriptions = [];
        while ($row = $result->fetch_assoc()) {
            $descriptions[] = $row;
        }

        echo json_encode($descriptions);
    }
