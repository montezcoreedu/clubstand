<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Membership</title>
    <?php include("../common/head.php"); ?>
    <script>
        function clearDatabase() {
            if (confirm("ARE YOU SURE YOU WANT TO ARCHIVE ALL ATTENDANCE, DEMERITS, AND SERVICE HOURS? This will close out the membership year and cannot be undone.")) {
                window.location.href = 'clear_database.php';
                return true;
            }
        }
    </script>
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
                <span>Manage Membership</span>
            </li>
        </ul>
        <h2>Manage Membership</h2>
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
        <div class="quick-links">
            <?php if (in_array('Add Member', $userPermissions)): ?>
                <a href="../members/add.php" class="quick-link">
                    <i class="fa-solid fa-user-plus"></i>
                    <span>Add Member</span>
                </a>
            <?php endif; ?>
            <?php if (in_array('Import Members', $userPermissions)): ?>
                <a href="import_members.php" class="quick-link">
                    <i class="fa-solid fa-upload"></i>
                    <span>Import Members</span>
                </a>
            <?php endif; ?>
        </div>
        <div>
            <table>
                <tbody>
                    <?php if (in_array('Assign Officers', $userPermissions)): ?>
                    <tr>
                        <td width="200" valign="top"><b>Officers</b></td>
                        <td>
                            Assign officer positions.
                            <br>
                            <a href="assign_officers.php" style="display: inline-flex; align-items: center; padding: 4px 0;"><img src="../images/dot.gif" class="icon14 user-add-icon" alt="Assign Positions icon">&nbsp;Assign Positions</a>
                            &nbsp;&nbsp;&nbsp;
                            <a href="manage_positions.php" style="display: inline-flex; align-items: center; padding: 4px 0;"><img src="../images/dot.gif" class="icon14 user-gray-icon" alt="Manage Positions icon">&nbsp;Manage Positions</a>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php if (in_array('Returning Members Form', $userPermissions)): ?>
                    <tr>
                        <td width="200" valign="top"><b>Returning Registration Form</b></td>
                        <td>
                            Open and close your chapter's registration.
                            <br>
                            <a href="registration_form.php" style="display: inline-flex; align-items: center; padding: 4px 0;"><img src="../images/dot.gif" class="icon14 application-form-edit-icon" alt="Manage Form icon">&nbsp;Manage Form</a>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php if (in_array('Store Membership Data', $userPermissions)): ?>
                    <tr>
                        <td width="200" valign="top"><b>Store Membership Year</b></td>
                        <td>
                            Store membership information for your chapter.
                            <br>
                            <a href="store_history.php" style="display: inline-inline-flex; align-items: center; padding: 4px 0;"><img src="../images/dot.gif" class="icon14 book-add-icon" alt="Store Historical Data icon">&nbsp;Store Historical Data</a>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php if (in_array('Transition Members', $userPermissions)): ?>
                    <tr>
                        <td width="200" valign="top"><b>Transition Members</b></td>
                        <td>
                            Execute pre-registration, graduation, and removal of members for the upcoming membership year.
                            <br>
                            <a href="transition_members.php" style="display: inline-flex; align-items: center; padding: 4px 0;"><img src="../images/dot.gif" class="icon14 user-go-icon" alt="Transition Statuses icon">&nbsp;Transition Statuses</a>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php if (in_array('Clear Records', $userPermissions)): ?>
                    <tr>
                        <td width="200" valign="top"><b>Reset Chapter Records</b></td>
                        <td>
                            Archive attendance, demerit, and service hour records from your system.
                            <br>
                            <a onclick="clearDatabase()" style="display: inline-flex; align-items: center; padding: 4px 0;"><img src="../images/dot.gif" class="icon14 database-delete-icon" alt="Clear Database Records icon">&nbsp;Clear Database Records</a>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>