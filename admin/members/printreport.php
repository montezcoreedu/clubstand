<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/checkmemberurl.php");
    include("../common/chapter_settings.php");
    include("../common/permissions.php");

    if (!in_array("Reports", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    if (!empty($_GET['id']) && $check_url) {
        include("../common/membercommon.php");

        $required_services = (!empty($chapter['MaxServiceHours'])) ? (int)$chapter['MaxServiceHours'] : 0;

        $membershipRiskQuery = "SELECT 
                                    m.MemberID,
                                    m.FirstName,
                                    m.LastName,
                                    COALESCE(sh.TotalServiceHours, 0) AS TotalServiceHours,
                                    COALESCE(a.AbsenceTardyCount, 0) AS AbsenceTardyCount,
                                    COALESCE(a.TotalMeetings, 0) AS TotalMeetings,
                                    ROUND(
                                        100 * (COALESCE(a.TotalMeetings, 0) - COALESCE(a.AbsenceTardyCount, 0)) / 
                                        NULLIF(COALESCE(a.TotalMeetings, 0), 0), 0
                                    ) AS AttendancePercentage
                                FROM members m
                                LEFT JOIN (
                                    SELECT MemberID, SUM(ServiceHours) AS TotalServiceHours
                                    FROM memberservicehours
                                    WHERE Archived = 0
                                    GROUP BY MemberID
                                ) sh ON m.MemberID = sh.MemberID
                                LEFT JOIN (
                                    SELECT 
                                        MemberID, 
                                        COUNT(*) AS TotalMeetings,
                                        SUM(CASE WHEN Status IN ('Absent', 'Tardy') THEN 1 ELSE 0 END) AS AbsenceTardyCount
                                    FROM attendance
                                    WHERE Archived = 0
                                    GROUP BY MemberID
                                ) a ON m.MemberID = a.MemberID
                                WHERE m.MemberID = $getMemberId
                                HAVING 
                                    TotalServiceHours <= $required_services
                                    OR AttendancePercentage < 70";
        $membershipRiskCheck = $conn->query($membershipRiskQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $LastName; ?>, <?php echo $FirstName; ?><?php echo !empty($Suffix) ? ' ' . $Suffix : ''; ?></title>
    <?php include("../common/head.php"); ?>
</head>
<body>
    <?php include("../common/loading.php"); ?>
    <?php include("../common/top-navbar.php"); ?>
    <?php include("../common/memberhead.php"); ?>
    <div id="content">
        <?php include("../common/member-sidebar.php"); ?>
        <div id="main-content-wrapper">
            <h2>Print A Report</h2>
            <form action="generate_report.php" method="post" target="_blank">
                <input type="hidden" name="MemberId" value="<?php echo $getMemberId; ?>">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <td width="180"><b>Print a report for:</b></td>
                            <td><?php echo $LastName; ?>, <?php echo $FirstName; ?> <?php echo $Suffix; ?></td>
                        </tr>
                        <tr>
                            <td width="180"><b>Report to print:</b></td>
                            <td>
                                <select name="report" required>
                                    <option value="" disabled selected>Select a report</option>

                                    <?php if (in_array($MemberStatus, [1, 2])): ?>
                                        <?php if ($membershipRiskCheck->num_rows): ?>
                                            <option value="membershipletter">At Risk Membership Letter</option>
                                        <?php endif; ?>

                                        <option value="attendance">Attendance Report</option>
                                        <option value="communityservices">Community Services Report</option>
                                        <option value="demerits">Demerits Report</option>
                                        <option value="memberprofile">Member Profile</option>
                                    <?php endif; ?>

                                    <?php if (!empty($RegistrationKey) && $MemberStatus == 1): ?>
                                        <option value="portalletter">Membership Portal Letter</option>
                                    <?php endif; ?>

                                    <?php if ($MemberStatus == 2): ?>
                                        <option value="probationletter">Probation Letter</option>
                                    <?php endif; ?>

                                    <?php if ($MemberStatus == 3): ?>
                                        <option value="terminationletter">Termination Letter</option>
                                    <?php endif; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td width="180"><b>Save as PDF file:</b></td>
                            <td>
                                <select name="save_pdf">
                                    <option value="no">No</option>
                                    <option value="yes">Yes</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td width="180"><b>Watermark text:</b></td>
                            <td>
                                <select name="watermark">
                                    <option value=""></option>
                                    <option value="draft">Draft</option>
                                    <option value="official">Official</option>
                                </select>
                            </td>
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