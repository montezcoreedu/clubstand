<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    if (!in_array("Community Services", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }
    
    $serviceId = isset($_GET['id']) ? $_GET['id'] : 0;
    
    if ($serviceId) {
        $stmt = $conn->prepare("SELECT * FROM communityservices WHERE ServiceId = ?");
        $stmt->bind_param("i", $serviceId);
        $stmt->execute();
        $serviceResult = $stmt->get_result();
        $serviceData = $serviceResult->fetch_assoc();
    
        if ($serviceData) {
            $stmt2 = $conn->prepare("SELECT ms.MemberId, m.FirstName, m.LastName, m.Suffix, m.MemberPhoto, ms.ServiceHours
                                            FROM memberservicehours ms
                                            JOIN members m ON ms.MemberId = m.MemberId
                                            WHERE ms.ServiceId = ? ORDER BY LastName asc, FirstName asc");
            $stmt2->bind_param("i", $serviceId);
            $stmt2->execute();
            $membersResult = $stmt2->get_result();
        } else {
            header("HTTP/1.0 404 Not Found");
            exit();
        }
    } else {
        header("HTTP/1.0 404 Not Found");
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $serviceData['ServiceName']; ?> - View Community Service</title>
    <?php include("../common/head.php"); ?>
    <script>
        $( function() {
            $( "#ServiceDate" ).datepicker({
                dateFormat: 'm/d/yy'
            });
        } );
    </script>
</head>
<body>
    <?php include("../common/loading.php"); ?>
    <?php include("../common/top-navbar.php"); ?>
    <div id="wrapper">
        <ul class="breadcrumbs">
            <li>
                <a href="index.php">Community Services</a>
            </li>
            <li>
                <span><?php echo $serviceData['ServiceName']; ?></span>
            </li>
        </ul>
        <h2><?php echo $serviceData['ServiceName']; ?></h2>
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
        <form action="update_service.php" method="POST">
            <input type="hidden" name="ServiceId" value="<?php echo $serviceData['ServiceId']; ?>">
            <table class="form-table">
                <tbody>
                    <tr>
                        <td width="180"><b>Service Name:</b></td>
                        <td><input type="text" name="ServiceName" value="<?php echo $serviceData['ServiceName']; ?>" required></td>
                    </tr>
                    <tr>
                        <td width="180"><b>Service Date:</b></td>
                        <td><input type="text" name="ServiceDate" id="ServiceDate" value="<?php echo $ServiceDate = date("m/d/Y", strtotime($serviceData['ServiceDate'])); ?>" required></td>
                    </tr>
                    <tr>
                        <td width="180"><b>Service Type:</b></td>
                        <td>
                            <select name="ServiceType" required>
                                <option value="Community Outreach" <?php if ($serviceData['ServiceType'] == 'Community Outreach') echo 'selected'; ?>>Community Outreach</option>
                                <option value="Donation" <?php if ($serviceData['ServiceType'] == 'Donation') echo 'selected'; ?>>Donation</option>
                                <option value="Environmental Work" <?php if ($serviceData['ServiceType'] == 'Environmental Work') echo 'selected'; ?>>Environmental Work</option>
                                <option value="Fundraising" <?php if ($serviceData['ServiceType'] == 'Fundraising') echo 'selected'; ?>>Fundraising</option>
                                <option value="Sports & Recreation" <?php if ($serviceData['ServiceType'] == 'Sports & Recreation') echo 'selected'; ?>>Sports & Recreation</option>
                                <option value="Volunteer Work" <?php if ($serviceData['ServiceType'] == 'Volunteer Work') echo 'selected'; ?>>Volunteer Work</option>
                                <option value="Other" <?php if ($serviceData['ServiceType'] == 'Other') echo 'selected'; ?>>Other</option>
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>
            <h3>Members and Hours</h3>
            <table class="members-table" style="margin-bottom: 1rem;">
                <thead>
                    <th align="left">Member Name</th>
                    <th align="left">Hours</th>
                </thead>
                <tbody>
                    <?php
                        while ($row = $membersResult->fetch_assoc()) {
                            $memberPhoto = !empty($row['MemberPhoto']) 
                                ? "<img src='../../MemberPhotos/{$row['MemberPhoto']}' alt='Member Photo' class='member-photo'>" 
                                : "<img src='../images/noprofilepic.jpeg' alt='Member Photo' class='member-photo'>";
                        
                            echo "<tr>
                                <td>
                                    <a href='../members/services.php?id={$row['MemberId']}' class='member-name'>{$memberPhoto} {$row['LastName']}, {$row['FirstName']} {$row['Suffix']}</a>
                                </td>
                                <td><input type='number' name='ServiceHours[{$row['MemberId']}]' value='{$row['ServiceHours']}' step='0.5' min='0.5'></td>
                            </tr>";
                        }
                    ?>
                </tbody>
            </table>
            <div style="margin-bottom: 0.5rem;">
                <button type="submit" name="update_service">Save changes</button>
            </div>
        </form>
    </div>
</body>
</html>