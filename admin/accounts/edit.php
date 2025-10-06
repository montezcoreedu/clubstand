<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    if (!in_array("Accounts Security", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    if (!empty($_GET['id'])) {
        $accountSelectedId = $_GET['id'];

        $stmt = $conn->prepare("SELECT * FROM accounts WHERE AccountId = ?");
        $stmt->bind_param("i", $accountSelectedId);
        $stmt->execute();
        $accountResult = $stmt->get_result();
        $accountData = $accountResult->fetch_assoc();

        // Group Options
        $groupOptions = '';
        $group_stmt = $conn->prepare("SELECT GroupId, GroupName FROM account_groups ORDER BY GroupName asc");
        $group_stmt->execute();
        $group_result = $group_stmt->get_result();
        while ($group = $group_result->fetch_assoc()) {
            $selected = '';
            $id = htmlspecialchars($group['GroupId']);
            $name = htmlspecialchars($group['GroupName']);
            if ($accountData['AccountGroup'] == $id) {
                $selected = 'selected';
            }
            $groupOptions .= "<option value='$id' $selected>$name</option>\n";
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $lastName = trim($_POST['LastName']);
            $firstName = trim($_POST['FirstName']);
            $accountGroup = isset($_POST['AccountGroup']) && $_POST['AccountGroup'] !== '' ? trim($_POST['AccountGroup']) : null;
            $username = $_POST['Username'];
            $lockAccess = isset($_POST['LockAccess']) ? 1 : 2;
            $password = $_POST['Password'];
        
            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE accounts 
                    SET LastName = ?, FirstName = ?, AccountGroup = ?, Username = ?, Password = ?, LockAccess = ?
                    WHERE AccountId = ?");
                $stmt->bind_param("ssssssi", $lastName, $firstName, $accountGroup, $username, $hashedPassword, $lockAccess, $accountSelectedId);
            } else {
                $stmt = $conn->prepare("UPDATE accounts SET LastName = ?, FirstName = ?, AccountGroup = ?, Username = ?, LockAccess = ? WHERE AccountId = ?");
                $stmt->bind_param("ssssii", $lastName, $firstName, $accountGroup, $username, $lockAccess, $accountSelectedId);
            }
        
            if ($stmt->execute()) {
                $_SESSION['successMessage'] = "<div class='message success'>Account successfully updated!</div>";
            } else {
                $_SESSION['errorMessage'] = "<div class='message error'>Something went wrong. Please try again.</div>";
            }
        
            $stmt->close();
            header("Location: edit.php?id=$accountSelectedId");
            exit();
        }        
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Account</title>
    <?php include("../common/head.php"); ?>
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
                <a href="../accounts/">Accounts & Security</a>
            </li>
            <li>
                <span>Edit Account</span>
            </li>
        </u>
        <h2>Edit Account</h2>
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
                        <td width="180"><b>Last Name:</b></td>
                        <td><input type="text" name="LastName" value="<?php echo $accountData['LastName']; ?>" maxlength="100" required></td>
                    </tr>
                    <tr>
                        <td width="180"><b>First Name:</b></td>
                        <td><input type="text" name="FirstName" value="<?php echo $accountData['FirstName']; ?>" maxlength="100" required></td>
                    </tr>
                    <tr>
                        <td width="180"><b>Account Group:</b></td>
                        <td>
                            <select name="AccountGroup">
                                <option value="">Advisor</option>
                                <?php echo $groupOptions; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td width="180"><b>Username:</b></td>
                        <td><input type="text" name="Username" value="<?php echo $accountData['Username']; ?>" maxlength="200" required></td>
                    </tr>
                    <tr>
                        <td width="180"><b>Password:</b></td>
                        <td>
                            <input type="password" name="Password" placeholder="Leave blank to keep current password" style="width: 100%; max-width: 280px;">
                        </td>
                    </tr>
                    <tr>
                        <td width="180"><b>Lock Access:</b></td>
                        <td><input type="checkbox" name="LockAccess" <?php echo ($accountData['LockAccess'] == "1")?"checked":"" ?>></td>
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