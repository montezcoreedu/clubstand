<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/checkmemberurl.php");
    include("../common/permissions.php");

    if (!in_array("Community Services", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    if (!empty($_GET['tid']) && !empty($_GET['id']) && $check_url) {
        include("../common/membercommon.php");

        $transferId = isset($_GET['tid']) ? $_GET['tid'] : 0;

        if (!empty($transferId)) {
            $stmt = $conn->prepare("SELECT * FROM membertransferhours WHERE TransferId = ?");
            $stmt->bind_param("i", $transferId);
            $stmt->execute();
            $serviceResult = $stmt->get_result();
            $serviceData = $serviceResult->fetch_assoc();
            $stmt->close();
        
            if ($_SERVER["REQUEST_METHOD"] === "POST") {
                $organizationName = $_POST['OrganizationName'] ?? '';
                $serviceDate = !empty($_POST['ServiceDate']) ? date('Y-m-d', strtotime($_POST['ServiceDate'])) : null;
                $serviceName = $_POST['ServiceName'] ?? '';
                $serviceHours = isset($_POST['ServiceHours']) ? intval($_POST['ServiceHours']) : 0;
        
                if (!empty($organizationName) && !empty($serviceDate) && !empty($serviceName) && $serviceHours > 0) {
                    $stmt = $conn->prepare("UPDATE membertransferhours SET OrganizationName = ?, ServiceDate = ?, ServiceName = ?, ServiceHours = ? WHERE TransferId = ?");
                    $stmt->bind_param("ssssi", $organizationName, $serviceDate, $serviceName, $serviceHours, $transferId);
        
                    if ($stmt->execute()) {
                        $_SESSION['successMessage'] = "<div class='message success'>Service records updated successfully!</div>";
                    } else {
                        $_SESSION['errorMessage'] = "<div class='message error'>Something went wrong. Please try again.</div>";
                    }
        
                    $stmt->close();
                    header("Location: ../members/services.php?id=" . urlencode($getMemberId) . "#transfer");
                    exit();
                } else {
                    $_SESSION['errorMessage'] = "<div class='message error'>All fields are required and service hours must be greater than 0.</div>";
                }
            }
        } else {
            header("HTTP/1.0 404 Not Found");
            exit();
        }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $LastName; ?>, <?php echo $FirstName; ?><?php echo !empty($Suffix) ? ' ' . $Suffix : ''; ?> - Edit Transfer Credit</title>
    <?php include("../common/head.php"); ?>
    <script>
        $(function() {
            $("#ServiceDate").datepicker({
                dateFormat: 'm/d/yy'
            });
        });
    </script>
</head>
<body>
    <?php include("../common/loading.php"); ?>
    <?php include("../common/top-navbar.php"); ?>
    <?php include("../common/memberhead.php"); ?>
    <div id="content">
        <?php include("../common/member-sidebar.php"); ?>
        <div id="main-content-wrapper">
            <ul class="breadcrumbs">
                <li>
                    <a href="../members/services.php?id=<?php echo $getMemberId; ?>#transfer">Services & Fundraising</a>
                </li>
                <li>
                    <span>Edit Transfer Credit</span>
                </li>
            </ul>
            <h2>Edit Transfer Credit</h2>
            <form method="post">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <td width="240"><b>Assigned to Member:</b></td>
                            <td><?php echo $LastName; ?>, <?php echo $FirstName; ?> <?php echo $Suffix; ?></td>
                        </tr>
                        <tr>
                            <td width="240"><b>Organization Name:</b></td>
                            <td><input type="text" name="OrganizationName" value="<?php echo $serviceData['OrganizationName']; ?>" maxlength="200" required style="width: 100%; max-width: 280px;"></td>
                        </tr>
                        <tr>
                            <td width="240"><b>Date:</b></td>
                            <td><input type="text" id="ServiceDate" name="ServiceDate" value="<?php echo $ServiceDate = date("n/j/Y", strtotime($serviceData['ServiceDate'])); ?>"></td>
                        </tr>
                        <tr>
                            <td width="240"><b>Service Name:</b></td>
                            <td><input type="text" name="ServiceName" value="<?php echo $serviceData['ServiceName']; ?>"  maxlength="200" required style="width: 100%; max-width: 280px;"></td>
                        </tr>
                        <tr>
                            <td width="240"><b>Credit Hours:</b></td>
                            <td><input type="number" name="ServiceHours" value="<?php echo $serviceData['ServiceHours']; ?>"  min="0.5" step="0.5" required></td>
                        </tr>
                        <tr>
                            <td colspan="2"><button type="submit">Save changes</button></td>
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