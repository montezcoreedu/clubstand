<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    if (!in_array("Assign Officers", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['positions'])) {
        foreach ($_POST['positions'] as $memberId => $position) {
            $memberId = intval($memberId);
            $position = mysqli_real_escape_string($conn, $position);

            $updateQuery = "UPDATE members SET Position = '$position' WHERE MemberId = $memberId";
            mysqli_query($conn, $updateQuery);
        }

        $_SESSION['successMessage'] = "<div class='message success'>Officer positions saved successfully!</div>";
    } else {
        $_SESSION['errorMessage'] = "<div class='message error'>No positions to save.</div>";
    }

    header("Location: index.php");
    exit;