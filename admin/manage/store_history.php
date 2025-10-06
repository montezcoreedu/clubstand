<?php
    include("../../dbconnection.php");
    include("../common/session.php");

    include("../common/permissions.php");

    if (!in_array("Store Membership Data", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Store Membership Year</title>
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
                <span>Store Membership Year</span>
            </li>
        </ul>
        <h2>Store Membership Year</h2>
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
        <form action="store_membership.php" method="post" onsubmit="return validateForm();">
            <table class="form-table">
                <tbody>
                    <tr>
                        <td width="180"><b>Membership Year:</b></td>
                        <td><input type="text" name="MembershipYear" id="MembershipYear" placeholder="e.g. 2024-2025" required pattern="^[0-9]{4}-[0-9]{4}$" title="Format must be YYYY-YYYY"></td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <button type="submit">Store Membership</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php if (isset($message)) echo "<p style='color: green;'>$message</p>"; ?>
        </form>
    </div>
    <script>
        function validateForm() {
            const yearInput = document.getElementById('MembershipYear').value;
            const regex = /^[0-9]{4}-[0-9]{4}$/;
            if (!regex.test(yearInput)) {
                alert("Membership Year must be in the format YYYY-YYYY.");
                return false;
            }
            return true;
        }
    </script>
</body>
</html>