<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/checkmemberurl.php");
    include("../common/permissions.php");

    if (!in_array("Community Services", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    if (!empty($_GET['id']) && $check_url) {
        include("../common/membercommon.php");

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $organizationName = $_POST['OrganizationName'];
            $serviceDate = date('Y-m-d', strtotime($_POST['ServiceDate']));
            $serviceName = $_POST['ServiceName'];
            $serviceHours = intval($_POST['ServiceHours']);
        
            $stmt = $conn->prepare("INSERT INTO membertransferhours (MemberId, OrganizationName, ServiceDate, ServiceName, ServiceHours) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isssi", $getMemberId, $organizationName, $serviceDate, $serviceName, $serviceHours);
        
            if ($stmt->execute()) {
                $_SESSION['successMessage'] = "<div class='message success'>Community service hours successfully transferred!</div>";
            } else {
                $_SESSION['errorMessage'] = "<div class='message error'>Something went wrong. Please try again.</div>";
            }
        
            $stmt->close();
            header("Location: ../members/services.php?id=$getMemberId#transfer");
            exit();
        }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $LastName; ?>, <?php echo $FirstName; ?><?php echo !empty($Suffix) ? ' ' . $Suffix : ''; ?> - Add Transfer Credit</title>
    <?php include("../common/head.php"); ?>
    <script>
        $(function() {
            $("#ServiceDate").datepicker({
                dateFormat: 'm/d/yy'
            });
            $('#ServiceDate').datepicker('setDate', 'today');
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
                    <span>Add Transfer Credit</span>
                </li>
            </ul>
            <h2>Add Transfer Credit</h2>
            <form method="post">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <td width="240"><b>Assign to Member:</b></td>
                            <td><?php echo $LastName; ?>, <?php echo $FirstName; ?> <?php echo $Suffix; ?></td>
                        </tr>
                        <tr>
                            <td width="240"><b>Organization Name:</b></td>
                            <td><input type="text" name="OrganizationName" maxlength="200" required style="width: 100%; max-width: 280px;"></td>
                        </tr>
                        <tr>
                            <td width="240"><b>Date:</b></td>
                            <td><input type="text" id="ServiceDate" name="ServiceDate"></td>
                        </tr>
                        <tr>
                            <td width="240"><b>Service Name:</b></td>
                            <td><input type="text" name="ServiceName" maxlength="200" required style="width: 100%; max-width: 280px;"></td>
                        </tr>
                        <tr>
                            <td width="240"><b>Credit Hours:</b></td>
                            <td><input type="number" name="ServiceHours" min="0.5" step="0.5" required></td>
                        </tr>
                        <tr>
                            <td colspan="2"><button type="submit">Submit</button></td>
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