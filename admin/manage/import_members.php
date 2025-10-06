<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    if (!in_array("Import Members", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Import Members</title>
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
                <span>Import Members</span>
            </li>
        </ul>
        <h2>Import Members</h2>
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
        <form action="process_imports.php" method="post" enctype="multipart/form-data">
            <table class="form-table">
                <tbody>
                    <tr>
                        <td width="180"><b>Select CSV to Upload:</b></td>
                        <td><input type="file" name="csv_file" accept=".csv" required></td>
                    </tr>
                    <tr>
                        <td colspan="2"><button type="submit">Submit</button></td>
                    </tr>
                </tbody>
            </table>
        </form>
    </div>
</body>
</html>