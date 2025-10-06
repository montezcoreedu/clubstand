<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/checkmemberurl.php");
    include("../common/permissions.php");

    if (!in_array("Member Contacts", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    if (!empty($_GET['id']) && $check_url) {
        include("../common/membercommon.php");

        if (isset($_POST['save_member'])) {
            $primaryContactName = mysqli_real_escape_string($conn, $_POST['PrimaryContactName']);
            $primaryContactPhone = mysqli_real_escape_string($conn, $_POST['PrimaryContactPhone']);
            $primaryContactEmail = mysqli_real_escape_string($conn, $_POST['PrimaryContactEmail']);
            $secondaryContactName = mysqli_real_escape_string($conn, $_POST['SecondaryContactName']);
            $secondaryContactPhone = mysqli_real_escape_string($conn, $_POST['SecondaryContactPhone']);
            $secondaryContactEmail = mysqli_real_escape_string($conn, $_POST['SecondaryContactEmail']);

            do {
                $member_sql = 'UPDATE members SET ';

                if ($primaryContactName != '') $member_sql .= '`PrimaryContactName`="'.$primaryContactName.'", ';
                if ($primaryContactPhone != '') $member_sql .= '`PrimaryContactPhone`="'.$primaryContactPhone.'", ';
                if ($primaryContactEmail != '') $member_sql .= '`PrimaryContactEmail`="'.$primaryContactEmail.'", ';
                if ($secondaryContactName != '') $member_sql .= '`SecondaryContactName`="'.$secondaryContactName.'", ';
                if ($secondaryContactName == '') $member_sql .= '`SecondaryContactName`="", ';
                if ($secondaryContactPhone != '') $member_sql .= '`SecondaryContactPhone`="'.$secondaryContactPhone.'", ';
                if ($secondaryContactPhone == '') $member_sql .= '`SecondaryContactPhone`="", ';
                if ($secondaryContactEmail != '') $member_sql .= '`SecondaryContactEmail`="'.$secondaryContactEmail.'", ';
                if ($secondaryContactEmail == '') $member_sql .= '`SecondaryContactEmail`="", ';

                $member_sql .= "`memberid` = $getMemberId WHERE `members`.`memberid` = $getMemberId";
                $member_query = $conn->query($member_sql);

                if ($member_query) {
                    $_SESSION['successMessage'] = "<div class='message success'>Profile contacts successfully saved!</div>";
                    header("Location: contacts.php?id=$getMemberId");
                    exit;
                } else {
                    $_SESSION['errorMessage'] = "<div class='message error'>Something went wrong.</div>";
                    header("Location: contacts.php?id=$getMemberId");
                    exit;
                }
            } while (false);
        }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $LastName; ?>, <?php echo $FirstName; ?><?php echo !empty($Suffix) ? ' ' . $Suffix : ''; ?></title>
    <?php include("../common/head.php"); ?>
</head>
<body>
    <?php include("../common/loading.php"); ?>
    <?php include("../common/top-navbar.php"); ?>
    <?php include("../common/memberhead.php"); ?>
    <div id="content">
        <?php include("../common/member-sidebar.php"); ?>
        <div id="main-content-wrapper">
            <h2>Contacts</h2>
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
            <form method="post">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <td width="180">
                                <label for="PrimaryContactName_id"><b>Primary Contact</b></label>
                            </td>
                            <td><input type="text" name="PrimaryContactName" placeholder="Name" value="<?php echo $PrimaryContactName; ?>" maxlength="100" required style="width: 100%; max-width: 280px;"></td>
                        </tr>
                        <tr>
                            <td width="180"></td>
                            <td><input type="text" name="PrimaryContactPhone" placeholder="Phone" value="<?php echo $PrimaryContactPhone; ?>" maxlength="200" required></td>
                        </tr>
                        <tr>
                            <td width="180"></td>
                            <td><input type="email" name="PrimaryContactEmail" placeholder="Email" value="<?php echo $PrimaryContactEmail; ?>" maxlength="100" required style="width: 100%; max-width: 320px;"></td>
                        </tr>
                        <tr>
                            <td width="180">
                                <label for="SecondaryContactName_id"><b>Secondary Contact</b></label>
                            </td>
                            <td><input type="text" name="SecondaryContactName" placeholder="Name" value="<?php echo $SecondaryContactName; ?>" maxlength="100" style="width: 100%; max-width: 280px;"></td>
                        </tr>
                        <tr>
                            <td width="180"></td>
                            <td><input type="text" name="SecondaryContactPhone" placeholder="Phone" value="<?php echo $SecondaryContactPhone; ?>" maxlength="200"></td>
                        </tr>
                        <tr>
                            <td width="180"></td>
                            <td><input type="email" name="SecondaryContactEmail" placeholder="Email" value="<?php echo $SecondaryContactEmail; ?>" maxlength="100" style="width: 100%; max-width: 320px;"></td>
                        </tr>
                        <tr>
                            <td colspan="2"><button type="submit" name="save_member">Save changes</button></td>
                        </tr>
                    </tbody>
                </table>
            </form>
        </div>
    </div>
</body>
</html>
<?php } else {
    header("HTTP/1.0 404 Not Found");
    exit();
} ?>