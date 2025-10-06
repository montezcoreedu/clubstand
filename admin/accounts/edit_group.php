<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    if (!in_array("Accounts Security", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    if (!empty($_GET['id'])) {
        $groupId = $_GET['id'];

        $stmt = $conn->prepare("SELECT * FROM account_groups WHERE GroupId = ?");
        $stmt->bind_param("i", $groupId);
        $stmt->execute();
        $groupResult = $stmt->get_result();
        $groupData = $groupResult->fetch_assoc();

        $existingPerms = [];
        $sql = "SELECT PermissionId FROM group_permissions WHERE GroupId = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $groupId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $existingPerms[] = $row['PermissionId'];
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $groupName = trim($_POST['GroupName']);
            $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];

            $conn->begin_transaction();

            try {
                $stmt = $conn->prepare("UPDATE account_groups SET GroupName = ? WHERE GroupId = ?");
                $stmt->bind_param("si", $groupName, $groupId);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update account group: " . $stmt->error);
                }
                $stmt->close();

                $stmt = $conn->prepare("DELETE FROM group_permissions WHERE GroupId = ?");
                $stmt->bind_param("i", $groupId);
                $stmt->execute();
                $stmt->close();

                if (!empty($permissions)) {
                    $stmtValues = [];
                    $types = str_repeat("ii", count($permissions));
                    $params = [];

                    foreach ($permissions as $pid) {
                        $stmtValues[] = "(?, ?)";
                        $params[] = $groupId;
                        $params[] = $pid;
                    }

                    $sql = "INSERT INTO group_permissions (GroupId, PermissionId) VALUES " . implode(", ", $stmtValues);
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param($types, ...$params);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to update permissions: " . $stmt->error);
                    }
                    $stmt->close();
                }

                $conn->commit();
                $_SESSION['successMessage'] = "<div class='message success'>Account group successfully updated!</div>";

            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['errorMessage'] = "<div class='message error'>".$e->getMessage()."</div>";
            }

            header("Location: ../accounts/");
            exit();
        }
    } else {
        header("Location: ../accounts/");
        exit;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Security Group</title>
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
                <span>Edit Security Group</span>
            </li>
        </u>
        <h2>Edit Security Group</h2>
        <form method="post">
            <table class="form-table">
                <tbody>
                    <tr>
                        <td width="180"><b>Group Name:</b></td>
                        <td><input type="text" name="GroupName" value="<?php echo $groupData['GroupName']; ?>" maxlength="100" required style="width: 30%;"></td>
                    </tr>
                    <tr>
                        <td valign="top"><b>Permissions:</b></td>
                        <td>
                            <?php
                            $sql = "SELECT * FROM permissions ORDER BY Category, PermissionName";
                            $result = $conn->query($sql);

                            $lastCategory = '';
                            while ($row = $result->fetch_assoc()) {
                                if ($row['Category'] !== $lastCategory) {
                                    echo "<label style='display: block; padding-bottom: 8px;'><b>{$row['Category']}</b></label>";
                                    $lastCategory = $row['Category'];
                                }

                                $checked = in_array($row['PermissionId'], $existingPerms) ? 'checked' : '';

                                echo "<label style='display: block; padding-left: 12px; padding-bottom: 8px;'>
                                        <input type='checkbox' name='permissions[]' value='{$row['PermissionId']}' {$checked}> {$row['PermissionName']}
                                    </label>";
                            }
                            ?>
                        </td>
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