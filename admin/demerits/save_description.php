<?php
    include("../../dbconnection.php");
    include("../common/session.php");

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        if (isset($_POST['delete']) && $_POST['delete'] == 1) {
            $descId = intval($_POST['DescriptionId']);
            $stmt = $conn->prepare("DELETE FROM demerit_descriptions WHERE DescriptionId = ?");
            $stmt->bind_param("i", $descId);
            $success = $stmt->execute();
            $stmt->close();
            echo json_encode(["success" => $success]);
            exit;
        }

        if (!empty($_POST['DescriptionId'])) {
            $descId = intval($_POST['DescriptionId']);
            $description = trim($_POST['Description']);
            $stmt = $conn->prepare("UPDATE demerit_descriptions SET Description = ? WHERE DescriptionId = ?");
            $stmt->bind_param("si", $description, $descId);
            $success = $stmt->execute();
            $stmt->close();
            echo json_encode(["success" => $success]);
            exit;
        }

        if (!empty($_POST['CategoryId'])) {
            $categoryId = intval($_POST['CategoryId']);
            $description = trim($_POST['Description']);
            $stmt = $conn->prepare("INSERT INTO demerit_descriptions (CategoryId, Description) VALUES (?, ?)");
            $stmt->bind_param("is", $categoryId, $description);
            $success = $stmt->execute();
            $newId = $stmt->insert_id;
            $stmt->close();
            echo json_encode(["success" => $success, "DescriptionId" => $newId]);
            exit;
        }
    }

    echo json_encode(["success" => false]);