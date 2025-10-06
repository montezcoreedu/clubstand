<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    if (!in_array("Reports", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    function getMonthName($monthNumber) {
        return date("F", mktime(0, 0, 0, $monthNumber, 1));
    }

    $query = "SELECT 
                YEAR(MeetingDate) AS Year, 
                MONTH(MeetingDate) AS Month, 
                COUNT(AttendanceId) AS AttendanceCount
            FROM attendance
            WHERE Status = 'Present'
            AND Archived = 0
            GROUP BY YEAR(MeetingDate), MONTH(MeetingDate)
            ORDER BY Year desc, Month desc";
    $result = $conn->query($query);

    $attendanceByMonth = [];
    $months = [];
    $attendanceCounts = [];

    while ($row = $result->fetch_assoc()) {
        $year = $row['Year'];
        $monthNumber = $row['Month'];
        $attendanceCount = $row['AttendanceCount'];

        $monthName = getMonthName($monthNumber);

        $attendanceByMonth[] = [
            'year' => $year,
            'month' => $monthName,
            'attendance' => $attendanceCount
        ];

        $months[] = "$monthName $year";
        $attendanceCounts[] = $attendanceCount;
    }

    $bestMonth = array_reduce($attendanceByMonth, function ($best, $current) {
        return ($best === null || $current['attendance'] > $best['attendance']) ? $current : $best;
    }, null);

    $worstMonth = array_reduce($attendanceByMonth, function ($worst, $current) {
        return ($worst === null || $current['attendance'] < $worst['attendance']) ? $current : $worst;
    }, null);

    $percentageChanges = [];
    for ($i = 1; $i < count($attendanceCounts); $i++) {
        $change = (($attendanceCounts[$i] - $attendanceCounts[$i - 1]) / $attendanceCounts[$i - 1]) * 100;
        $percentageChanges[] = round($change, 0);
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Best & Worst Attendance Months</title>
    <?php include("../common/head.php"); ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include("../common/loading.php"); ?>
    <?php include("../common/top-navbar.php"); ?>
    <div id="wrapper">
        <ul class="breadcrumbs">
            <li>
                <a href="index.php">Reports</a>
            </li>
            <li>
                <span>Best & Worst Attendance Months</span>
            </li>
        </ul>
        <h2>Best & Worst Attendance Months</h2>
        <div>
            <button onclick="window.print()">Print Report</button>
        </div>
        <div class="container">
            <div class="chart-container">
                <canvas id="attendanceChart"></canvas>
            </div>
            <div class="table-container">
                <table>
                    <tr>
                        <th>Month</th>
                        <th>Year</th>
                        <th>Attendance</th>
                        <th>% Change</th>
                    </tr>
                    <?php foreach ($attendanceByMonth as $index => $data): ?>
                        <tr>
                            <td align="center"><?= $data['month']; ?></td>
                            <td align="center"><?= $data['year']; ?></td>
                            <td align="center"><?= $data['attendance']; ?></td>
                            <td align="center">
                                <?php if ($index > 0): ?>
                                    <?php
                                    $change = $percentageChanges[$index - 1];
                                    if ($change > 0) {
                                        echo "<span class='up-arrow'><i class='fa-regular fa-circle-up'></i> $change%</span>";
                                    } elseif ($change < 0) {
                                        echo "<span class='down-arrow'><i class='fa-regular fa-circle-down'></i> $change%</span>";
                                    } else {
                                        echo "<span class='no-change'>↔ No Change</span>";
                                    }
                                    ?>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <h4>Best Month: <?= $bestMonth ? "{$bestMonth['month']} {$bestMonth['year']} ({$bestMonth['attendance']} attendees)" : "N/A" ?></h4>
                <h4>Worst Month: <?= $worstMonth ? "{$worstMonth['month']} {$worstMonth['year']} ({$worstMonth['attendance']} attendees)" : "N/A" ?></h4>
            </div>
        </div>
    </div>
    <script>
        const ctx = document.getElementById('attendanceChart').getContext('2d');
        const attendanceChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($months) ?>,
                datasets: [{
                    label: 'Monthly Attendance',
                    data: <?= json_encode($attendanceCounts) ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>