<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['selected_ids'])) {
        $selectedIds = $_POST['selected_ids'];

        $ids = array_map('intval', $selectedIds);
        $idList = implode(',', $ids);

        $deleteQuery = "DELETE FROM quick_select WHERE SelectId IN ($idList)";
        if ($conn->query($deleteQuery)) {
            $_SESSION['successMessage'] = "<div class='message success'>Selected members removed from Quick Select.</div>";
        } else {
            $_SESSION['errorMessage'] = "<div class='message error'>Error removing members: " . $conn->error . "</div>";
        }
    } else {
        $_SESSION['errorMessage'] = "<div class='message error'>No members selected for removal.</div>";
    }

    header("Location: ../quickselect/");
    exit;