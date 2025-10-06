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

        $serviceId = isset($_GET['sid']) ? $_GET['sid'] : 0;

        if (!empty($serviceId)) {
            $stmt = $conn->prepare("SELECT * FROM memberservicehours ms INNER JOIN communityservices cs ON ms.ServiceId = cs.ServiceId WHERE EntryId = ?");
            $stmt->bind_param("i", $serviceId);
            $stmt->execute();
            $serviceResult = $stmt->get_result();
            $serviceData = $serviceResult->fetch_assoc();
            $stmt->close();

            if ($_SERVER["REQUEST_METHOD"] === "POST") {
                $serviceHours = isset($_POST['ServiceHours']) ? intval($_POST['ServiceHours']) : 0;
        
                if ($serviceHours > 0) {
                    $stmt = $conn->prepare("UPDATE memberservicehours SET ServiceHours = ? WHERE EntryId = ?");
                    $stmt->bind_param("si", $serviceHours, $serviceId);
        
                    if ($stmt->execute()) {
                        $_SESSION['successMessage'] = "<div class='message success'>Service records updated successfully!</div>";
                    } else {
                        $_SESSION['errorMessage'] = "<div class='message error'>Something went wrong. Please try again.</div>";
                    }
        
                    $stmt->close();
                    header("Location: ../members/services.php?id=" . urlencode($getMemberId) . "");
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
    <title><?php echo $LastName; ?>, <?php echo $FirstName; ?><?php echo !empty($Suffix) ? ' ' . $Suffix : ''; ?> - Edit Community Service</title>
    <?php include("../common/head.php"); ?>
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
                    <a href="../members/services.php?id=<?php echo $getMemberId; ?>">Services & Fundraising</a>
                </li>
                <li>
                    <span>Edit Service</span>
                </li>
            </ul>
            <h2>Edit Service</h2>
            <form method="post">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <td width="240"><b>Assigned to Member:</b></td>
                            <td><?php echo $LastName; ?>, <?php echo $FirstName; ?> <?php echo $Suffix; ?></td>
                        </tr>
                        <tr>
                            <td><b>Service Name:</b></td>
                            <td><?php echo $serviceData['ServiceName']; ?></td>
                        </tr>
                        <tr>
                            <td width="240"><b>Date:</b></td>
                            <td><?php echo $ServiceDate = date("n/j/Y", strtotime($serviceData['ServiceDate'])); ?></td>
                        </tr>
                        <tr>
                            <td width="240"><b>Service Type:</b></td>
                            <td><?php echo $serviceData['ServiceType']; ?></td>
                        </tr>
                        <tr>
                            <td width="240"><b>Credit Hours:</b></td>
                            <td><input type="number" name="ServiceHours" min="0.5" step="0.5" value="<?php echo $serviceData['ServiceHours']; ?>" required></td>
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