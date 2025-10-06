<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    function generateRandomKey($length = 5) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $key = '';
        for ($i = 0; $i < $length; $i++) {
            $key .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $key;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $overwrite = isset($_POST['OverwriteKeys']);
        $reset = isset($_POST['ResetAccounts']);

        $keysGenerated = 0;
        $keysOverwritten = 0;
        $accountsReset = 0;

        $adminId = $_SESSION['account_id'];

        $result = mysqli_query($conn, "SELECT q.SelectId, m.MemberId, m.RegistrationKey
                                                    FROM quick_select q 
                                                    INNER JOIN members m ON q.MemberId = m.MemberId 
                                                    WHERE q.AdminId = $adminId 
                                                    AND q.AddedOn >= NOW() - INTERVAL 1 DAY");

        while ($row = mysqli_fetch_assoc($result)) {
            $memberId = $row['MemberId'];
            $existingKey = $row['RegistrationKey'];

            if ($existingKey && !$overwrite) {
                continue;
            }

            $keysOverwritten += $existingKey ? 1 : 0;

            do {
                $newKey = generateRandomKey(6);
                $checkQuery = "SELECT COUNT(*) as count FROM members WHERE RegistrationKey = '$newKey'";
                $checkResult = mysqli_query($conn, $checkQuery);
                $countRow = mysqli_fetch_assoc($checkResult);
            } while ($countRow['count'] > 0);

            if ($reset) {
                $updateQuery = "
                    UPDATE members 
                    SET RegistrationKey = '$newKey',
                        Username = NULL,
                        Password = NULL,
                        RegistrationCompleted = 0
                    WHERE MemberId = $memberId
                ";
                $accountsReset++;
            } else {
                $updateQuery = "
                    UPDATE members 
                    SET RegistrationKey = '$newKey'
                    WHERE MemberId = $memberId
                ";
            }

            if (mysqli_query($conn, $updateQuery)) {
                $keysGenerated++;
            }
        }

        $_SESSION['successMessage'] = "<div class='message success'>
            Registration keys generated successfully!<br>
            🔑 <strong>$keysGenerated</strong> keys generated<br>
            🔁 <strong>$keysOverwritten</strong> existing keys overwritten<br>
            🔄 <strong>$accountsReset</strong> accounts reset
        </div>";

        header("Location: generate_registration.php");
        exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Generate Registration Keys</title>
    <?php include("../common/head.php"); ?>
</head>
<body>
    <?php include("../common/loading.php"); ?>
    <?php include("../common/top-navbar.php"); ?>
    <div id="wrapper">
        <ul class="breadcrumbs">
            <li>
                <a href="../home/">Member Search</a>
            </li>
            <li>
                <a href="../quickselect/">Quick Select</a>
            </li>
            <li>
                <span>Generate Registration Keys</span>
            </li>
        </ul>
        <h2>Generate Registration Keys</h2>
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
                        <td width="220"><b>Overwrite Existing Keys?</b></td>
                        <td><input type="checkbox" name="OverwriteKeys" id="OverwriteKeys">&nbsp;&nbsp;<small>(If checked, it's recommended to reset existing accounts as well.)</small></td>
                    </tr>
                    <tr>
                        <td width="220"><b>Reset Existing Accounts?</b></td>
                        <td><input type="checkbox" name="ResetAccounts" id="ResetAccounts"></td>
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