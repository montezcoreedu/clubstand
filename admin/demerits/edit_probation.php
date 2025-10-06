<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/checkmemberurl.php");
    include("../common/permissions.php");

    if (!in_array("Demerits", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    if (!empty($_GET['pid']) && !empty($_GET['id']) && $check_url) {
        include("../common/membercommon.php");

        $probationId = isset($_GET['pid']) ? $_GET['pid'] : 0;

        if (!empty($probationId)) {
            $stmt = $conn->prepare("SELECT * FROM probation WHERE ProbationId = ?");
            $stmt->bind_param("i", $probationId);
            $stmt->execute();
            $probationResult = $stmt->get_result();
            $probationData = $probationResult->fetch_assoc();
            $stmt->close();

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $probationLevel = $_POST['ProbationLevel'] ?? $probationData['ProbationLevel'];
                $conditionsMet = isset($_POST['ConditionMet']) ? json_encode($_POST['ConditionMet']) : "[]";
            
                $updateSql = "UPDATE probation SET ProbationLevel = ?, ConditionsMet = ? WHERE ProbationId = ?";
                $stmt = $conn->prepare($updateSql);
                $stmt->bind_param("ssi", $probationLevel, $conditionsMet, $probationId);
                $stmt->execute();
                $stmt->close();
            
                if (isset($_POST['saveAndEnd'])) {
                    $updateMemberSql = "UPDATE members SET MemberStatus = 1 WHERE MemberId = ?";
                    $stmt = $conn->prepare($updateMemberSql);
                    $stmt->bind_param("i", $getMemberId);
                    $stmt->execute();
                    $stmt->close();
            
                    $endProbationSql = "UPDATE probation SET ProbationStatus = 2 WHERE ProbationId = ?";
                    $stmt = $conn->prepare($endProbationSql);
                    $stmt->bind_param("i", $probationId);
                    $stmt->execute();
                    $stmt->close();
            
                    $_SESSION['successMessage'] = "<div class='message success'>Probation has officially been lifted successfully!</div>";
                    header("Location: ../members/demerits.php?id=$getMemberId");
                    exit();
                } else {
                    $_SESSION['successMessage'] = "<div class='message success'>Probation status changes successfully recorded!</div>";
                }
            
                $stmt = $conn->prepare("SELECT * FROM probation WHERE ProbationId = ?");
                $stmt->bind_param("i", $probationId);
                $stmt->execute();
                $probationResult = $stmt->get_result();
                $probationData = $probationResult->fetch_assoc();
                $stmt->close();
            }            

            // Fetch condition data
            $startDate = $probationData['StartDate'];
            $endDate = $probationData['EndDate'];
            $memberId = $_GET['id'];

            // Attendance
            $attendanceQuery = "SELECT COUNT(*) as total FROM attendance WHERE MemberId = ? AND MeetingDate BETWEEN ? AND ?";
            $stmt = $conn->prepare($attendanceQuery);
            $stmt->bind_param("iss", $memberId, $startDate, $endDate);
            $stmt->execute();
            $stmt->bind_result($totalMeetings);
            $stmt->fetch();
            $stmt->close();

            $presentQuery = "SELECT COUNT(*) as present FROM attendance WHERE MemberId = ? AND Status = 'Present' AND MeetingDate BETWEEN ? AND ?";
            $stmt = $conn->prepare($presentQuery);
            $stmt->bind_param("iss", $memberId, $startDate, $endDate);
            $stmt->execute();
            $stmt->bind_result($presentCount);
            $stmt->fetch();
            $stmt->close();

            $attendancePercent = ($totalMeetings > 0) ? ($presentCount / $totalMeetings) * 100 : 0;

            // Service Hours
            $serviceQuery = "SELECT SUM(ServiceHours) as totalHours FROM memberservicehours m INNER JOIN communityservices s ON m.ServiceId = s.ServiceId WHERE MemberId = ? AND ServiceDate BETWEEN ? AND ?";
            $stmt = $conn->prepare($serviceQuery);
            $stmt->bind_param("iss", $memberId, $startDate, $endDate);
            $stmt->execute();
            $stmt->bind_result($totalHours);
            $stmt->fetch();
            $stmt->close();

            // Demerit Points
            $demeritQuery = "SELECT COALESCE(DemeritPoints, 0) as totalPoints FROM demerits WHERE MemberId = ? AND DemeritDate BETWEEN ? AND ?";
            $stmt = $conn->prepare($demeritQuery);
            $stmt->bind_param("iss", $memberId, $startDate, $endDate);
            $stmt->execute();
            $stmt->bind_result($totalPoints);
            $stmt->fetch();
            $stmt->close();

            // Conditions status
            $conditionsStatus = [
                "Attend 80% of meetings" => $attendancePercent >= 80,
                "Complete at least 3 service hours" => $totalHours >= 3,
                "No new demerits" => $totalPoints == 0,
                "End of term grade check approval" => true
            ];
        }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $LastName; ?>, <?php echo $FirstName; ?><?php echo !empty($Suffix) ? ' ' . $Suffix : ''; ?> - Edit Probation</title>
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
                    <a href="../members/demerits.php?id=<?php echo $getMemberId; ?>">Demerits</a>
                </li>
                <li>
                    <span>Edit Probation Status</span>
                </li>
            </ul>
            <h2>Edit Probation Status</h2>
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
                            <td width="220"><b>Start Date:</b></td>
                            <td><?php echo $StartDate = date("n/j/Y", strtotime($probationData['StartDate'])); ?></td>
                        </tr>
                        <tr>
                            <td width="220"><b>End Date:</b></td>
                            <td><?php echo $EndDate = date("n/j/Y", strtotime($probationData['EndDate'])); ?></td>
                        </tr>
                        <tr>
                            <td width="220"><b>Level:</b></td>
                            <td>
                                <select name="ProbationLevel">
                                    <option value="Warning" <?php if ($probationData['ProbationLevel'] == 'Warning') echo 'selected'; ?>>Warning</option>
                                    <option value="Strict" <?php if ($probationData['ProbationLevel'] == 'Strict') echo 'selected'; ?>>Strict</option>
                                    <option value="Final" <?php if ($probationData['ProbationLevel'] == 'Final') echo 'selected'; ?>>Final</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td width="220" valign="top"><b>Conditions for Removal (system run):</b></td>
                            <td>
                                <?php
                                    $conditions = json_decode($probationData['ConditionsForRemoval'], true);
                                    if (!is_array($conditions)) {
                                        $conditions = [];
                                    }
                                    
                                    foreach ($conditions as $condition) {
                                        $met = $conditionsStatus[$condition] ?? false;
                                        $icon = $met
                                            ? "<img src='../images/icon-check.svg' alt='Condition has been met' title='Condition has been met'>"
                                            : "<img src='../images/icon-caution.svg' alt='Condition has not met' title='Condition has not met'>";
                                        echo "<div style='display: flex; align-items: center; padding-bottom: 4px;'>$icon&nbsp; $condition</div>";
                                    }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td width="220" valign="top"><b>Conditions Met (approval):</b></td>
                            <td>
                                <?php
                                    $conditionsMet = json_decode($probationData['ConditionsMet'], true);
                                    if (!is_array($conditionsMet)) {
                                        $conditionsMet = [];
                                    }

                                    foreach ($conditions as $condition) {
                                        $isChecked = in_array($condition, $conditionsMet) ? "checked" : "";
                                        echo "<label><input type='checkbox' name='ConditionMet[]' value='$condition' $isChecked> $condition</label><br>";
                                    }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <button type="submit" name="saveOnly">Save changes</button>
                                &nbsp;
                                <button type="submit" name="saveAndEnd">Save & End Probation</button>
                            </td>
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