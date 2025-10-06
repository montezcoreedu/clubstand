<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    $adminId = $_SESSION['account_id'];

    $qs_query = "SELECT q.SelectId, m.MemberId, m.LastName, m.FirstName, m.Suffix, m.EmailAddress, m.GradeLevel, m.MembershipTier, m.MemberPhoto 
        FROM quick_select q 
        INNER JOIN members m ON q.MemberId = m.MemberId 
        WHERE q.AdminId = $adminId 
        AND q.AddedOn >= NOW() - INTERVAL 1 DAY 
        ORDER BY m.LastName asc, m.FirstName asc";
    $qs_result = $conn->query($qs_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Print A Report</title>
    <?php include("../common/head.php"); ?>
</head>
<body>
    <?php include("../common/loading.php"); ?>
    <?php include("../common/top-navbar.php"); ?>
    <div id="wrapper">
        <ul class="breadcrumbs">
            <li>
                <a href="../home/">Member Search</a>
            </li>
            <li>
                <a href="../quickselect/">Quick Select</a>
            </li>
            <li>
                <span>Print A Report</span>
            </li>
        </ul>
        <h2>Print A Report</h2>
        <form action="generate_report.php" method="post" target="_blank">
            <table class="form-table">
                <tbody>
                    <tr>
                        <td width="180"><b>Print a report for:</b></td>
                        <td>
                            <?php
                                $memberCount = $qs_result->num_rows;
                                echo "$memberCount quick selected member(s)";
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td width="180"><b>Report to print:</b></td>
                        <td>
                            <select name="report" required>
                                <option value="" disabled selected>Select a report</option>
                                <option value="membershipletter">At Risk Membership Letter</option>
                                <option value="attendance">Attendance Report</option>
                                <option value="communityservices">Community Services Report</option>
                                <option value="demerits">Demerits Report</option>
                                <option value="memberprofile">Member Profile</option>
                                <option value="portalletter">Membership Portal Letter</option>
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
</body>
</html>