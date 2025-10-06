<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    if (!in_array("Import Members", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    if (!isset($_SESSION['previewData'])) {
        header("Location: import_members.php");
        exit;
    }

    $previewData = $_SESSION['previewData'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Preview CSV Imported Members Data</title>
    <?php include("../common/head.php"); ?>
</head>
<body>
    <?php include("../common/loading.php"); ?>
    <?php include("../common/top-navbar.php"); ?>
    <div id="wrapper">
        <ul class="breadcrumbs">
            <li>
                <a href="../home/index.php">Member Search</a>
            </li>
            <li>
                <a href="../manage/index.php">Manage Membership</a>
            </li>
            <li>
                <a href="import_members.php">Import Members</a>
            </li>
            <li>
                <span>Preview CSV Imported Members Data</span>
            </li>
        </ul>
        <h2>Preview CSV Imported Members Data</h2>
        <?php
            if (isset($_SESSION['successMessage'])) {
                echo $_SESSION['successMessage'];
                unset($_SESSION['successMessage']);
            }
            if (isset($_SESSION['errorMessage'])) {
                echo $_SESSION['errorMessage'];
                unset($_SESSION['errorMessage']);
            }
        ?>
        <form action="process_imports.php" method="POST" style="margin: 1rem 0;">
            <table class="general-table">
                <thead>
                    <?php
                        if (count($previewData) > 0) {
                            foreach (array_keys($previewData[0]) as $header) {
                                echo "<th>" . htmlspecialchars($header) . "</th>";
                            }
                            echo "<th>Action</th>";
                        }
                    ?>
                </thead>
                <tbody>
                    <?php
                        foreach ($previewData as $row) {
                            echo "<tr>";
                            foreach ($row as $value) {
                                echo "<td>" . htmlspecialchars($value) . "</td>";
                            }
                            echo "<td><input type='checkbox' name='confirm_import[]' value='" . htmlspecialchars(json_encode($row)) . "' checked></td>";
                            echo "</tr>";
                        }
                    ?>
                    <tr>
                        <td colspan="24">
                            <button type="submit" name="action" value="confirm">Confirm Import</button>
                            &nbsp;
                            <button type="button" onclick="window.location.href='import_members.php'">Cancel</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>
    </div>
</body>
</html>