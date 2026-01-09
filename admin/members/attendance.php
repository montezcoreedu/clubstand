<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/checkmemberurl.php");
    include("../common/permissions.php");

    if (!in_array("Attendance", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    if (!empty($_GET['id']) && $check_url) {
        include("../common/membercommon.php");

        // Attendance DB
        $attendance_query = "SELECT MeetingDate, Status FROM attendance WHERE MemberId = $getMemberId AND Archived = 0 ORDER BY MeetingDate desc";
        $attendance_result = $conn->query($attendance_query);

        // Excuses DB
        $excuses_query = "SELECT MeetingDate, Reason, OtherExplained FROM excuse_requests WHERE MemberId = $getMemberId AND Archived = 0 ORDER BY MeetingDate desc";
        $excuses_result = $conn->query($excuses_query);

        // Attendance Percentage
        $attpercentage_query = "SELECT CASE WHEN COUNT(*) = 0 THEN 0 ELSE ROUND(SUM(CASE WHEN Status = 'Present' THEN 1 WHEN Status = 'Excused' THEN 0.5 ELSE 0 END)/COUNT(*)*100) END AS AttPercentage FROM attendance WHERE MemberId = $getMemberId AND Archived = 0";
        $attpercentage_result = $conn->query($attpercentage_query);
        $att_percent = mysqli_fetch_assoc($attpercentage_result);

        // Consecutive Absences
        $attendance_records = [];
        while ($row = $attendance_result->fetch_assoc()) {
            $attendance_records[] = $row;
        }

        $attendance_records = array_reverse($attendance_records);

        $consecutive_absences = 0;
        $current_streak_start_date = null;

        foreach ($attendance_records as $row) {
            if ($row['Status'] === 'Absent') {
                $consecutive_absences++;
                if ($consecutive_absences === 1) {
                    $current_streak_start_date = $row['MeetingDate'];
                }
            } else {
                $consecutive_absences = 0;
                $current_streak_start_date = null;
            }
        }

        // Attendance Trends
        $attendance_trends_result = $conn->query($attendance_query);
        $presentCount = 0;
        $absentCount = 0;
        $tardyCount = 0;
        $excusedCount = 0;

        // Excuse Trends
        $excuses_trends_result = $conn->query($excuses_query);
        $sickCount = 0;
        $medicalCount = 0;
        $activitiesCount = 0;
        $sportsCount = 0;
        $otherCount = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $LastName; ?>, <?php echo $FirstName; ?><?php echo !empty($Suffix) ? ' ' . $Suffix : ''; ?></title>
    <?php include("../common/head.php"); ?>
    <script>
        $( function() {
          $( "#attendance" ).tabs();
        } );
    </script>
</head>
<body>
    <?php include("../common/loading.php"); ?>
    <?php include("../common/top-navbar.php"); ?>
    <?php include("../common/memberhead.php"); ?>
    <div id="content">
        <?php include("../common/member-sidebar.php"); ?>
        <div class="toggle-content" id="main-content-wrapper">
            <h2>Attendance Records</h2>
            <div id="attendance">
                <ul class="subtabs">
                    <li>
                        <a href="#overall">Overall</a>
                    </li>
                    <li>
                        <a href="#excuses">Excuses</a>
                    </li>
                </ul>
                <div id="overall">
                    <?php
                        if ($attendance_result->num_rows) {
                            echo '<table class="general-table" style="margin: 1rem 0;">';
                                echo '<thead>';
                                    echo '<th align="left" width="180">Meeting Date</th>';
                                    echo '<th align="left">Attendance</th>';
                                echo '</thead>';
                                echo '<tbody>';
                                foreach (array_reverse($attendance_records) as $row) {
                                    $MeetingDate = date("m/d/Y", strtotime($row['MeetingDate']));
                                    echo "<tr>
                                        <td>{$MeetingDate}</td>
                                        <td>{$row['Status']}</td>
                                    </tr>";
                                }
                                echo '</tbody>';
                            echo '</table>';
                        } else {
                            echo "<p>No attendance records found.</p>";
                        }
                    ?>
                </div>
                <div id="excuses">
                    <?php
                        if ($excuses_result->num_rows) {
                            echo '<table class="general-table" style="margin: 1rem 0;">';
                                echo '<thead>';
                                    echo '<th align="left">Meeting Date</th>';
                                    echo '<th align="left">Reason</th>';
                                    echo '<th align="left" width="320">Other (Explained)</th>';
                                echo '</thead>';
                                echo '<tbody>';
                                while ($row = $excuses_result->fetch_assoc()) {
                                    $MeetingDate = date("m/d/Y", strtotime($row['MeetingDate']));
                                    echo "<tr>
                                        <td>{$MeetingDate}</td>
                                        <td>{$row['Reason']}</td>
                                        <td>{$row['OtherExplained']}</td>
                                    </tr>";
                                }
                                echo '</tbody>';
                            echo '</table>';
                        } else {
                            echo "<p>No excused records found.</p>";
                        }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <button id="toggle-drawer">
        <i id="drawer-icon" class="fas fa-chevron-right"></i>
    </button>
    <div class="open" id="member-drawer">
        <div class="drawer-content">
            <?php if ($consecutive_absences > 1 && $current_streak_start_date): ?>
            <div class="card">
                <span class="title">⚠️ Consecutive Absences</span>
                <div class="content">
                    <span><?php echo $consecutive_absences; ?> consecutive absences starting from <?php echo date("m/d/Y", strtotime($current_streak_start_date)); ?>.</span>
                </div>
            </div>
            <?php endif; ?>
            <div class="card">
                <span class="title">Daily Attendance Average</span>
                <div class="content">
                    <div class="progress-container">
                        <?php
                            $percentage = isset($att_percent['AttPercentage']) ? (int)$att_percent['AttPercentage'] : null;

                            $barColor = 'rgb(240, 240, 240)';
                            $width = '0%';
                            $message = 'No attendance records yet.';

                            if ($percentage !== null) {
                                $width = $percentage . '%';
                                $message = "Attended {$percentage}% of chapter meetings";

                                if ($percentage >= 70) {
                                    $barColor = 'rgb(40, 152, 5)';
                                } elseif ($percentage >= 50) {
                                    $barColor = 'rgb(152, 120, 5)';
                                } elseif ($percentage >= 1) {
                                    $barColor = 'rgb(144, 26, 7)';
                                }
                            }
                        ?>
                        <div class="progress-bar">
                            <div class="data"
                                role="progressbar"
                                data-aos="slide-right"
                                data-aos-delay="200"
                                data-aos-duration="1000"
                                data-aos-easing="ease-in-out"
                                style="width: <?= $width ?>; background-color: <?= $barColor ?>;">
                            </div>
                        </div>
                        <div class="progress-data">
                            <span style="padding-top: 0.8rem;">
                                <?= htmlspecialchars($message) ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <span class="title">Attendance Trends</span>
                <div class="content">
                    <?php
                        if ($attendance_result->num_rows) {
                            while ($trend = $attendance_trends_result->fetch_assoc()) {
                                $status = $trend['Status'];
                        
                                if ($status == 'Present') {
                                    $presentCount++;
                                } elseif ($status == 'Absent') {
                                    $absentCount++;
                                } elseif ($status == 'Tardy') {
                                    $tardyCount++;
                                } elseif ($status == 'Excused') {
                                    $excusedCount++;
                                }
                            }
                        
                            $statuses = [$presentCount, $absentCount, $tardyCount, $excusedCount];
                            $statusLabels = ['Present', 'Absent', 'Tardy', 'Excused'];
                            $statusesJSON = json_encode($statuses);
                            $statusLabelsJSON = json_encode($statusLabels);

                            echo '<div><canvas id="attendanceTrends"></canvas></div>';
                        } else {
                            echo '<span>No attendance records yet.</span>';
                        }
                    ?>
                </div>
            </div>
            <div class="card">
                <span class="title">Excuses Breakdown</span>
                <div class="content">
                    <?php
                        if ($excuses_result->num_rows) {
                            while ($excuse = $excuses_trends_result->fetch_assoc()) {
                                $Reason = $excuse['Reason'];
                        
                                if ($Reason == 'Feeling Sick') {
                                    $sickCount++;
                                } elseif ($Reason == 'Medical Appointments') {
                                    $medicalCount++;
                                } elseif ($Reason == 'Extracurricular Activities') {
                                    $activitiesCount++;
                                } elseif ($Reason == 'Sports') {
                                    $sportsCount++;
                                } elseif ($Reason == 'Other (explain below)') {
                                    $otherCount++;
                                }
                            }
                        
                            $reasons = [$sickCount, $medicalCount, $activitiesCount, $sportsCount, $otherCount];
                            $reasonLabels = ['Sick', 'Medical Appointments', 'Extracurricular Activities', 'Sports', 'Other'];
                            $reasonsJSON = json_encode($reasons);
                            $reasonLabelsJSON = json_encode($reasonLabels);

                            echo '<div><canvas id="excusesTrend"></canvas></div>';
                        } else {
                            echo '<span>No excuse requests.</span>';
                        }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <script src="../js/script.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        AOS.init();
        
        <?php if ($attendance_result->num_rows): ?>
        // Attendance Trends
        var statuses = <?php echo $statusesJSON; ?>;
        var statusLabels = <?php echo $statusLabelsJSON; ?>;

        var ctx = document.getElementById('attendanceTrends').getContext('2d');
        var attendanceTrends = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    label: 'Attendance Status',
                    data: statuses,
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.2)', 
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(255, 159, 64, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)', 
                        'rgba(255, 99, 132, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(153, 102, 255, 1)',
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem) {
                                return tooltipItem.label + ': ' + tooltipItem.raw;
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>

        <?php if ($excuses_result->num_rows) { ?>
        // Excuses Trend
        var reasons = <?php echo $reasonsJSON; ?>;
        var reasonLabels = <?php echo $reasonLabelsJSON; ?>;

        var ctx = document.getElementById('excusesTrend').getContext('2d');
        var excusesTrend = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: reasonLabels,
                datasets: [{
                    label: 'Excuse Reason',
                    data: reasons,
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.2)', 
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(255, 159, 64, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(102, 171, 255, 0.2)',
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)', 
                        'rgba(255, 99, 132, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(153, 102, 255, 1)', 
                        'rgba(102, 171, 255, 1)',
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem) {
                                return tooltipItem.label + ': ' + tooltipItem.raw;
                            }
                        }
                    }
                }
            }
        });
        <?php } ?>
    </script>
</body>
</html>
<?php } else {
    header("HTTP/1.0 404 Not Found");
    exit();
} ?>