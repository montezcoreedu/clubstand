<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    if (!in_array("Accounts Security", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    // Group Options
    $groupOptions = '';
    $group_stmt = $conn->prepare("SELECT GroupId, GroupName FROM account_groups ORDER BY GroupName asc");
    $group_stmt->execute();
    $group_result = $group_stmt->get_result();
    while ($group = $group_result->fetch_assoc()) {
        $id = htmlspecialchars($group['GroupId']);
        $name = htmlspecialchars($group['GroupName']);
        $groupOptions .= "<option value='$id'>$name</option>\n";
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $lastName = trim($_POST['LastName']);
        $firstName = trim($_POST['FirstName']);
        $accountGroup = isset($_POST['AccountGroup']) && $_POST['AccountGroup'] !== '' ? trim($_POST['AccountGroup']) : null;
        $username = $_POST['Username'];
        $password = $_POST['Password'];
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $lockAccess = isset($_POST['LockAccess']) ? 1 : 2;
    
        $stmt = $conn->prepare("INSERT INTO accounts (LastName, FirstName, AccountGroup, Username, Password, LockAccess) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssi", $lastName, $firstName, $accountGroup, $username, $hashedPassword, $lockAccess);
    
        if ($stmt->execute()) {
            $_SESSION['successMessage'] = "<div class='message success'>Account successfully created!</div>";
        } else {
            $_SESSION['errorMessage'] = "<div class='message error'>Something went wrong. Please try again.</div>";
        }
    
        $stmt->close();
        header("Location: ../accounts/");
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Account</title>
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
                <span>Add Account</span>
            </li>
        </u>
        <h2>Add Account</h2>
        <form method="post">
            <table class="form-table">
                <tbody>
                    <tr>
                        <td width="180"><b>Last Name:</b></td>
                        <td><input type="text" name="LastName" maxlength="100" required></td>
                    </tr>
                    <tr>
                        <td width="180"><b>First Name:</b></td>
                        <td><input type="text" name="FirstName" maxlength="100" required></td>
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
                        <td><input type="text" name="Username" maxlength="200" required></td>
                    </tr>
                    <tr>
                        <td width="180"><b>Password:</b></td>
                        <td><input type="password" name="Password" required></td>
                    </tr>
                    <tr>
                        <td width="180"><b>Lock Access:</b></td>
                        <td><input type="checkbox" name="LockAccess" checked></td>
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