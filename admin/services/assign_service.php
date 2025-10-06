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

        // Services Select DB
        $services_sql = "SELECT ServiceId, ServiceName FROM communityservices ORDER BY ServiceDate desc";
        $services_query = $conn->query($services_sql);

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $serviceId = $_POST['ServiceId'];
            $newServiceName = $_POST['NewServiceName'];
            $serviceDate = date('Y-m-d', strtotime($_POST['ServiceDate']));
            $serviceType = $_POST['ServiceType'];
            $serviceHours = $_POST['ServiceHours'];

            if (empty($serviceId) && !empty($newServiceName)) {
                $query = "INSERT INTO communityservices (ServiceName, ServiceDate, ServiceType) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sss", $newServiceName, $serviceDate, $serviceType);
                if ($stmt->execute()) {
                    $serviceId = $stmt->insert_id;
                } else {
                    $_SESSION['errorMessage'] = "<div class='message error'>Failed to create new service.</div>";
                    header("Location: ../members/services.php?id=$getMemberId");
                    exit();
                }
            }

            $check_existing_service_sql = "SELECT * FROM memberservicehours WHERE MemberId = ? AND ServiceId = ?";
            $stmt_check = $conn->prepare($check_existing_service_sql);
            $stmt_check->bind_param("ii", $getMemberId, $serviceId);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) {
                $_SESSION['errorMessage'] = "<div class='message error'>This service has already been assigned to this member.</div>";
            } else {
                $query = "INSERT INTO memberservicehours (MemberId, ServiceId, ServiceHours) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("iii", $getMemberId, $serviceId, $serviceHours);

                if ($stmt->execute()) {
                    $_SESSION['successMessage'] = "<div class='message success'>Community service successfully added!</div>";
                } else {
                    $_SESSION['errorMessage'] = "<div class='message error'>Something went wrong. Please try again.</div>";
                }
            }

            header("Location: ../members/services.php?id=$getMemberId");
            exit();
        }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $LastName; ?>, <?php echo $FirstName; ?><?php echo !empty($Suffix) ? ' ' . $Suffix : ''; ?> - Add Community Service</title>
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
                    <a href="../members/services.php?id=<?php echo $getMemberId; ?>">Services & Fundraising</a>
                </li>
                <li>
                    <span>Add Service</span>
                </li>
            </ul>
            <h2>Add Service</h2>
            <form method="post">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <td width="240"><b>Assign to Member:</b></td>
                            <td><?php echo $LastName; ?>, <?php echo $FirstName; ?> <?php echo $Suffix; ?></td>
                        </tr>
                        <tr>
                            <td><b>Select Existing Service:</b></td>
                            <td>
                                <select name="ServiceId">
                                    <option value=""></option>
                                    <?php
                                        while ($service = mysqli_fetch_assoc($services_query)) {
                                            echo '<option value="'.$service['ServiceId'].'">'.$service['ServiceName'].'</option>';
                                        }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td width="240"><b>OR Create a New Service:</b></td>
                            <td><input type="text" name="NewServiceName" maxlength="200" placeholder="Service Name" style="width: 100%; max-width: 280px;"></td>
                        </tr>
                        <tr>
                            <td width="240"><b>Date:</b></td>
                            <td><input type="text" id="ServiceDate" name="ServiceDate"></td>
                        </tr>
                        <tr>
                            <td width="240"><b>Service Type:</b></td>
                            <td>
                                <select name="ServiceType">
                                    <option value=""></option>
                                    <option value="Community Outreach">Community Outreach</option>
                                    <option value="Donation">Donation</option>
                                    <option value="Environmental Work">Environmental Work</option>
                                    <option value="Fundraising">Fundraising</option>
                                    <option value="Sports & Recreation">Sports & Recreation</option>
                                    <option value="Volunteer Work">Volunteer Work</option>
                                    <option value="Other">Other</option>
                                </select>
                            </td>
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