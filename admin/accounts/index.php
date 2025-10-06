<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    if (!in_array("Accounts Security", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    $accounts_query = "SELECT a.AccountId, a.LastName, a.FirstName, a.AccountGroup, a.Username, 
            a.LockAccess, ag.GroupName
        FROM accounts a
        LEFT JOIN account_groups ag ON a.AccountGroup = ag.GroupId
        ORDER BY LastName asc, FirstName asc";
    $accounts_result = $conn->query($accounts_query);

    $groups_query = "SELECT GroupId, GroupName
        FROM account_groups
        ORDER BY GroupName asc";
    $groups_result = $conn->query($groups_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Accounts & Security</title>
    <?php include("../common/head.php"); ?>
    <script>
        $( function() {
            var icons = {
                header: "iconClosed",
                activeHeader: "iconOpen"
            };
            $( "#accordion" ).accordion({
                icons: icons,
                collapsible: true,
                heightStyle: "content"
            });
        } );

        $(document).ready(function () {  
            Array.from(document.querySelectorAll('#accountstable')).forEach(function(menu_side) {
                menu_side.onclick = ({
                    target
                }) => {
                    if (!target.classList.contains('action_button')) return
                    document.querySelectorAll('.actions.active').forEach(
                        (d) => d !== target.parentElement && d.classList.remove('active')
                    )
                    target.parentElement.classList.toggle('active');
                }
            });
        });

        $(document).ready(function () {  
            Array.from(document.querySelectorAll('#groupstable')).forEach(function(menu_side) {
                menu_side.onclick = ({
                    target
                }) => {
                    if (!target.classList.contains('action_button')) return
                    document.querySelectorAll('.actions.active').forEach(
                        (d) => d !== target.parentElement && d.classList.remove('active')
                    )
                    target.parentElement.classList.toggle('active');
                }
            });
        });

        function deleteAccount(AccountId) {
            if (confirm("Are you sure you want to delete this account?")) {
                window.location.href='delete.php?id='+AccountId;
                return true;
            }
        }

        function deleteGroup(GroupId) {
            if (confirm("Are you sure you want to delete this group?")) {
                window.location.href='delete.php?id='+GroupId;
                return true;
            }
        }
    </script>
</head>
<body>
    <?php include("../common/top-navbar.php"); ?>
    <?php include("../common/loading.php"); ?>
    <div id="wrapper">
        <ul class="breadcrumbs">
            <li>
                <a href="../home/">Member Search</a>
            </li>
            <li>
                <span>Accounts & Security</span>
            </li>
        </ul>
        <h2>Accounts & Security</h2>
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
        <div id="accordion" style="margin-bottom: 1rem;">
            <h3>Accounts</h3>
            <div>
                <div style="margin-bottom: 1rem;">
                    <a href="add.php" class="btn-link">Add Account</a>
                </div>
                <?php
                    if ($accounts_result->num_rows) {
                        echo '<table class="general-table" id="accountstable">';
                            echo '<thead>';
                                echo '<th align="left">Name</th>';
                                echo '<th align="left">Account Type</th>';
                                echo '<th align="left">Username</th>';
                                echo '<th>Lock Access</th>';
                                echo '<th align="left">Actions</th>';
                            echo '</thead>';
                            echo '<tbody>';
                            while ($row = $accounts_result->fetch_assoc()) {
                                $accountGroup = isset($row['GroupName']) && $row['GroupName'] !== null ? htmlspecialchars($row['GroupName']) : 'Advisor';
                                if ($row['LockAccess'] == 1) {
                                    $lockAccess = "<img src='../images/icon-check.svg' alt='Account access locked icon' title='Account access locked'>";
                                } else {
                                    $lockAccess = "<img src='../images/dot.gif' class='icon16 lock-open-icon' alt='Account access unlocked icon' title='Account access unlocked'>";
                                }
                                
                                echo "<tr>
                                    <td><a href='edit.php?id={$row['AccountId']}'>{$row['LastName']}, {$row['FirstName']}</a></td>
                                    <td>{$accountGroup}</td>
                                    <td>{$row['Username']}</td>
                                    <td align='center'>{$lockAccess}</td>
                                    <td>
                                        <div class='actions-dropdown'>
                                            <button type='button' class='action_button'>Actions&nbsp;&nbsp;<i class='fa-solid fa-caret-right'></i></button>
                                            <div class='action_menu'>
                                                <a onclick='deleteAccount({$row['AccountId']})'><img src='../images/dot.gif' class='icon14 delete-icon' alt='Delete icon'>&nbsp;Delete</a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>";
                            }
                            echo '</tbody>';
                        echo '</table>';
                    } else {
                        echo '<div class="message error" style="margin-bottom: 0;">No accounts found.</div>';
                    }
                ?>
            </div>
            <h3>Security Groups</h3>
            <div>
                <div style="margin-bottom: 1rem;">
                    <a href="add_group.php" class="btn-link">Add Group</a>
                </div>
                <?php
                    if ($groups_result->num_rows) {
                        echo '<table class="general-table" id="groupstable">';
                            echo '<thead>';
                                echo '<th align="left">Group Name</th>';
                                echo '<th align="left">Actions</th>';
                            echo '</thead>';
                            echo '<tbody>';
                            while ($row = $groups_result->fetch_assoc()) {
                                echo "<tr>
                                    <td><a href='edit_group.php?id={$row['GroupId']}'>{$row['GroupName']}</a></td>
                                    <td>
                                        <div class='actions-dropdown'>
                                            <button type='button' class='action_button'>Actions&nbsp;&nbsp;<i class='fa-solid fa-caret-right'></i></button>
                                            <div class='action_menu'>
                                                <a onclick='deleteGroup({$row['GroupId']})'><img src='../images/dot.gif' class='icon14 delete-icon' alt='Delete icon'>&nbsp;Delete</a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>";
                            }
                            echo '</tbody>';
                        echo '</table>';
                    } else {
                        echo '<div class="message error" style="margin-bottom: 0;">No security groups found.</div>';
                    }
                ?>
            </div>
            <h3>Security Settings</h3>
            <div>
                <form method="post" action="save_settings.php">
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <td width="220"><b>Enable Admin Login:</b></td>
                                <td><input type="checkbox" name="EnableAdminLogin" value="1" <?= $settings['EnableAdminLogin'] ? 'checked' : '' ?>></td>
                            </tr>
                            <tr>
                                <td width="220"><b>Enable Member Login:</b></td>
                                <td><input type="checkbox" name="EnableMemberLogin" value="1" <?= $settings['EnableMemberLogin'] ? 'checked' : '' ?>></td>
                            </tr>
                            <tr>
                                <td width="220"><b>Max Login Attempts:</b></td>
                                <td><input type="number" name="MaxLoginAttempts" min="1" value="<?= $settings['MaxLoginAttempts']; ?>" required></td>
                            </tr>
                            <tr>
                                <td width="220"><b>Lockout Duration (minutes):</b></td>
                                <td><input type="number" name="LockoutDuration" min="1" value="<?= $settings['LockoutDuration']; ?>" required></td>
                            </tr>
                            <tr>
                                <td width="220"><b>Auto Logout (minutes):</b></td>
                                <td><input type="number" name="AutoLogoutMinutes" min="1" value="<?= $settings['AutoLogoutMinutes']; ?>" required></td>
                            </tr>
                            <tr>
                                <td colspan="2"><button type="submit">Save Settings</button></td>
                            </tr>
                        </tbody>
                    </table>
                </form>
            </div>
        </div>
    </div>
</body>
</html>