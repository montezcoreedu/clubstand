<?php
    include("../../dbconnection.php");
    include("../common/session.php");

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        if (isset($_POST['reorder']) && $_POST['reorder'] == 1) {
            $order = json_decode($_POST['order'], true);

            if (is_array($order)) {
                $stmt = $conn->prepare("UPDATE officer_positions SET Sort = ? WHERE PositionId = ?");
                foreach ($order as $row) {
                    $sort = intval($row['sort']);
                    $id = intval($row['id']);
                    $stmt->bind_param("ii", $sort, $id);
                    $stmt->execute();
                }
                $stmt->close();
                echo json_encode(["success" => true]);
                exit;
            } else {
                echo json_encode(["success" => false, "error" => "Invalid order payload"]);
                exit;
            }
        }

        if (isset($_POST['delete']) && $_POST['delete'] == 1) {
            $positionId = intval($_POST['PositionId']);
            $stmt = $conn->prepare("DELETE FROM officer_positions WHERE PositionId = ?");
            $stmt->bind_param("i", $positionId);
            $success = $stmt->execute();
            $stmt->close();

            if ($success) {
                $result = $conn->query("SELECT PositionId FROM officer_positions ORDER BY Sort asc");
                $i = 1;
                $stmt = $conn->prepare("UPDATE officer_positions SET Sort = ? WHERE PositionId = ?");
                while ($row = $result->fetch_assoc()) {
                    $stmt->bind_param("ii", $i, $row['PositionId']);
                    $stmt->execute();
                    $i++;
                }
                $stmt->close();
            }

            echo json_encode(["success" => $success]);
            exit;
        }

        if (!empty($_POST['PositionId'])) {
            $positionId = intval($_POST['PositionId']);
            $positionName = trim($_POST['PositionName']);
            $stmt = $conn->prepare("UPDATE officer_positions SET PositionName = ? WHERE PositionId = ?");
            $stmt->bind_param("si", $positionName, $positionId);
            $success = $stmt->execute();
            $stmt->close();
            echo json_encode(["success" => $success]);
            exit;
        }

        if (isset($_POST['PositionName']) && $_POST['PositionName'] !== '') {
            $positionName = trim($_POST['PositionName']);

            $result = $conn->query("SELECT COALESCE(MAX(Sort), 0) AS max_sort FROM officer_positions");
            $row = $result->fetch_assoc();
            $sort = $row['max_sort'] + 1;

            $stmt = $conn->prepare("INSERT INTO officer_positions (PositionName, Sort) VALUES (?, ?)");
            $stmt->bind_param("si", $positionName, $sort);
            $success = $stmt->execute();
            $newId = $stmt->insert_id;
            $stmt->close();

            echo json_encode(["success" => $success, "PositionId" => $newId, "Sort" => $sort]);
            exit;
        }
    }

    echo json_encode(["success" => false]);