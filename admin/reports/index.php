<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    if (!in_array("Reports", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Reports</title>
    <?php include("../common/head.php"); ?>
</head>
<body>
    <?php include("../common/loading.php"); ?>
    <?php include("../common/top-navbar.php"); ?>
    <div id="wrapper">
        <h2>Reports</h2>
        <table class="general-table">
            <thead>
                <th align="left" width="320">Report Name</th>
                <th align="left">Description</th>
            </thead>
            <tbody>
                <tr>
                    <td><a href="risk_members.php">At Risk Members</a></td>
                    <td>Report members who are at risk due to excessive absences/tardies, low community service hours, or high demerit points.</td>
                </tr>
                <tr>
                    <td><a href="month_attendance.php">Best & Worst Attendance Months</a></td>
                    <td>A summary of monthly attendance trends, highlighting the months with the highest and lowest attendance.</td>
                </tr>
                <tr>
                    <td><a href="consecutive_absences.php">Consecutive Absences</a></td>
                    <td>Monitor and analyze student absence patterns to identify and address attendance concerns early.</td>
                </tr>
                <tr>
                    <td><a href="ethnicity_breakdown.php">Ethnicity Breakdown</a></td>
                    <td>View a breakdown of different ethnicities within your chapter.</td>
                </tr>
                <tr>
                    <td><a href="grade_level_breakdown.php">Grade Level Breakdown</a></td>
                    <td>View a breakdown of different grade levels within your chapter.</td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>