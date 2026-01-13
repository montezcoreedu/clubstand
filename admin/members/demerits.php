<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/checkmemberurl.php");
    include("../common/chapter_settings.php");
    include("../common/permissions.php");

    if (!in_array("Demerits", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    if (!empty($_GET['id']) && $check_url) {
        include("../common/membercommon.php");

        $max_demerits = (!empty($chapter['MaxDemerits'])) ? (int)$chapter['MaxDemerits'] : 0;

        // Demerits DB
        $demerits_query = "SELECT DemeritId, DemeritDate, Demerit, DemeritDescription, DemeritPoints FROM demerits WHERE MemberId = $getMemberId AND Archived = 0 ORDER BY DemeritDate desc";
        $demerits_result = $conn->query($demerits_query);

        // Probations DB
        $probations_query = "SELECT ProbationLevel, StartDate, EndDate, ProbationReason, ProbationStatus FROM probation WHERE MemberId = $getMemberId ORDER BY EndDate desc";
        $probations_result = $conn->query($probations_query);

        // Current Probation DB
        $current_probation_query = "SELECT * FROM probation WHERE MemberId = $getMemberId AND ProbationStatus = 1";
        $current_probation_result = $conn->query($current_probation_query);
        $probation = mysqli_fetch_assoc($current_probation_result);

        // Cumulative Demerit Points DB
        $pointsdemerits_sql = "SELECT COALESCE(SUM(DemeritPoints), 0) AS CumulativePoints FROM demerits WHERE MemberId = $getMemberId AND Archived = 0";
        $pointsdemerits_query = $conn->query($pointsdemerits_sql);
        $demerit_count = mysqli_fetch_assoc($pointsdemerits_query);

        // Demerit Trends
        $demerits_trends_result = $conn->query($demerits_query);
        $categoryCounts = [
            'Academic' => 0,
            'Attendance' => 0,
            'Discipline' => 0,
            'Miscellaneous' => 0,
            'Officer Incident' => 0
        ];

        $repeatCounts = $categoryCounts;
        $isRepeatOffender = false;
        $repeatReason = '';

        while ($row = $demerits_trends_result->fetch_assoc()) {
            $category = $row['Demerit'];
            if (isset($categoryCounts[$category])) {
                $categoryCounts[$category]++;
                $repeatCounts[$category]++;
            }
        }

        foreach ($repeatCounts as $type => $count) {
            if ($count >= 3) {
                $isRepeatOffender = true;
                $repeatReason = "$type violations";
                break;
            }
        }

        $totalRepeat = array_sum($repeatCounts);
        if (!$isRepeatOffender && $totalRepeat >= 5) {
            $isRepeatOffender = true;
            $repeatReason = "multiple demerit violations";
        }

        $probationStatus = $current_probation_result->num_rows > 0;
        if ($probationStatus) {
            $probationId = $probation['ProbationId'];
            $probationIssued = $probation['IssuedBy'];
            $probationLevel = $probation['ProbationLevel'];
            $probationStartDate = $probation['StartDate'];
            $probationEndDate = $probation['EndDate'];
            $probationReason = $probation['ProbationReason'];
            $conditionsForRemoval = $probation['ConditionsForRemoval'];

            // System check for conditions
            $startDate = $probationStartDate;
            $endDate = $probationEndDate;
            $memberId = $getMemberId;

            // Attendance
            $attendanceQuery = "SELECT COUNT(*) as total FROM attendance WHERE MemberId = ? AND MeetingDate BETWEEN ? AND ? AND Archived = 0";
            $stmt = $conn->prepare($attendanceQuery);
            $stmt->bind_param("iss", $memberId, $startDate, $endDate);
            $stmt->execute();
            $stmt->bind_result($totalMeetings);
            $stmt->fetch();
            $stmt->close();

            $presentQuery = "SELECT COUNT(*) as present FROM attendance WHERE MemberId = ? AND Status = 'Present' AND MeetingDate BETWEEN ? AND ? AND Archived = 0";
            $stmt = $conn->prepare($presentQuery);
            $stmt->bind_param("iss", $memberId, $startDate, $endDate);
            $stmt->execute();
            $stmt->bind_result($presentCount);
            $stmt->fetch();
            $stmt->close();

            $attendancePercent = ($totalMeetings > 0) ? ($presentCount / $totalMeetings) * 100 : 0;

            // Service Hours
            $serviceQuery = "SELECT SUM(ServiceHours) as totalHours FROM memberservicehours m INNER JOIN communityservices s ON m.ServiceId = s.ServiceId WHERE MemberId = ? AND ServiceDate BETWEEN ? AND ? AND m.Archived = 0 AND s.Archived = 0";
            $stmt = $conn->prepare($serviceQuery);
            $stmt->bind_param("iss", $memberId, $startDate, $endDate);
            $stmt->execute();
            $stmt->bind_result($totalHours);
            $stmt->fetch();
            $stmt->close();

            // Demerits
            $demeritQuery = "SELECT COALESCE(SUM(DemeritPoints), 0) as totalPoints FROM demerits WHERE MemberId = ? AND DemeritDate BETWEEN ? AND ? AND Archived = 0";
            $stmt = $conn->prepare($demeritQuery);
            $stmt->bind_param("iss", $memberId, $startDate, $endDate);
            $stmt->execute();
            $stmt->bind_result($totalPoints);
            $stmt->fetch();
            $stmt->close();

            // Decode conditions
            $conditions = json_decode($conditionsForRemoval, true);
            if (!is_array($conditions)) {
                $conditions = [];
            }

            $conditionsStatus = [
                "Attend 80% of meetings" => $attendancePercent >= 80,
                "Complete at least 3 service hours" => $totalHours >= 3,
                "No new demerits" => $totalPoints == 0,
                "End of term grade check approval" => true
            ];
        }

        // Demerit Trends
        $demerits_trends_chart = $conn->query($demerits_query);
        $academicCount = $attendanceCount = $disciplineCount = $miscellaneousCount = $officerCount = 0;
        while ($trend = $demerits_trends_chart->fetch_assoc()) {
            switch ($trend['Demerit']) {
                case 'Academic': $academicCount++; break;
                case 'Attendance': $attendanceCount++; break;
                case 'Discipline': $disciplineCount++; break;
                case 'Miscellaneous': $miscellaneousCount++; break;
                case 'Officer Incident': $officerCount++; break;
            }
        }
        $categories = [$academicCount, $attendanceCount, $disciplineCount, $miscellaneousCount, $officerCount];
        $categoryLabels = ['Academic', 'Attendance', 'Discipline', 'Miscellaneous', 'Officer Incident'];
        $categoriesJSON = json_encode($categories);
        $categoryLabelsJSON = json_encode($categoryLabels);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $LastName; ?>, <?php echo $FirstName; ?><?php echo !empty($Suffix) ? ' ' . $Suffix : ''; ?></title>
    <?php include("../common/head.php"); ?>
    <script>
        $( function() {
          $( "#demerits" ).tabs();
        } );

        $( function() {
            var icons = {
                header: "iconClosed",
                activeHeader: "iconOpen"
            };
            $( "#accordion" ).accordion({
                icons: icons,
                collapsible: true,
                heightStyle: "content"
            });
        } );

        $(document).ready(function () {  
            Array.from(document.querySelectorAll('#demeritstable')).forEach(function(menu_side) {
                menu_side.onclick = ({
                    target
                }) => {
                    if (!target.classList.contains('action_button')) return
                    document.querySelectorAll('.actions.active').forEach(
                        (d) => d !== target.parentElement && d.classList.remove('active')
                    )
                    target.parentElement.classList.toggle('active');
                }
            });
        });

        function deleteDemerit(DemeritId) {
            if (confirm("Are you sure you want to delete this demerit?")) {
                window.location.href='../demerits/delete.php?did='+DemeritId+'&id=<?php echo $getMemberId; ?>';
                return true;
            }
        }

        function deleteProbation(ProbationId) {
            if (confirm("Are you sure you want to delete this probation period?")) {
                window.location.href='../demerits/delete_probation.php?pid='+ProbationId+'&id=<?php echo $getMemberId; ?>';
                return true;
            }
        }

        function sendWarning() {
            if (confirm("Are you sure you want to send a probation warning email?")) {
                window.location.href='../demerits/send_warning.php?id=<?php echo $getMemberId; ?>';
                return true;
            }
        }
    </script>
</head>
<body>
    <?php include("../common/loading.php"); ?>
    <?php include("../common/top-navbar.php"); ?>
    <?php include("../common/memberhead.php"); ?>
    <div id="content">
        <?php include("../common/member-sidebar.php"); ?>
        <div class="toggle-content" id="main-content-wrapper">
            <h2>Demerit Records</h2>
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
            <div id="demerits">
                <ul class="subtabs">
                    <li>
                        <a href="#issued">Issued Demerits</a>
                    </li>
                    <li>
                        <a href="#probations">Probations</a>
                    </li>
                </ul>
                <div id="issued">
                    <?php
                        if ($probationStatus) {
                            echo "<div id='accordion' style='margin-bottom: 1rem;'>
                                    <h3>Probation Status</h3>
                                    <div>
                                        <table class='data-table'>
                                            <tbody>
                                                <tr>
                                                    <td width='280' valign='top'><b>Issued by:</b></td>
                                                    <td>" . $probationIssued . "</td>
                                                </tr>
                                                <tr>
                                                    <td width='280' valign='top'><b>Level:</b></td>
                                                    <td>" . $probationLevel . "</td>
                                                </tr>
                                                <tr>
                                                    <td width='280'><b>Active until:</b></td>
                                                    <td>" . date('F j, Y', strtotime($probationEndDate)) . "</td>
                                                </tr>
                                                <tr>
                                                    <td width='280' valign='top'><b>Probation reason:</b></td>
                                                    <td>" . $probationReason . "</td>
                                                </tr>
                                                <tr>
                                                    <td width='280' valign='top'><b>Conditions for Removal (system run):</b></td>
                                                    <td>";
                        
                                                        $conditions = json_decode($conditionsForRemoval, true);
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
                        
                                            echo "</td>
                                                </tr>
                                                <tr>
                                                    <td colspan='2'>
                                                        <a href='../demerits/edit_probation.php?pid=$probationId&id=$getMemberId' class='btn-link'>Edit</a>
                                                        &nbsp;
                                                        <a href='../demerits/extend_probation.php?pid=$probationId&id=$getMemberId' class='btn-link'>Extend Probation</a>
                                                        &nbsp;
                                                        <a onclick='deleteProbation({$probationId})' class='btn-link'>Delete</a>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>";
                        }
                    ?>
                    <div>
                        <a href="../demerits/add.php?id=<?php echo $getMemberId; ?>" class="btn-link">Add</a>
                        &nbsp;
                        <?php if ($MemberStatus == 1) { ?><a href="../demerits/assign_probation.php?id=<?php echo $getMemberId; ?>" class="btn-link">Assign Probation</a><?php } ?>
                    </div>
                    <?php
                        if ($demerits_result->num_rows) {
                            echo '<table class="general-table" id="demeritstable" style="margin: 2rem 0;">';
                                echo '<thead>';
                                    echo '<th align="left">Date</th>';
                                    echo '<th align="left">Demerit</th>';
                                    echo '<th align="left">Description</th>';
                                    echo '<th align="left">Points</th>';
                                    echo '<th align="left">Actions</th>';
                                echo '</thead>';
                                echo '<tbody>';
                                while ($row = $demerits_result->fetch_assoc()) {
                                    $DemeritDate = date("m/d/Y", strtotime($row['DemeritDate']));
                                    
                                    echo "<tr>
                                        <td>{$DemeritDate}</td>
                                        <td>{$row['Demerit']}</td>
                                        <td>{$row['DemeritDescription']}</td>
                                        <td>{$row['DemeritPoints']}</td>
                                        <td>
                                            <div class='actions-dropdown'>
                                                <button type='button' class='action_button'>Actions&nbsp;&nbsp;<i class='fa-solid fa-caret-right'></i></button>
                                                <div class='action_menu'>
                                                    <a href='../demerits/edit.php?did={$row['DemeritId']}&id=$getMemberId'><img src='../images/dot.gif' class='icon14 edit-icon' alt='Edit icon'>&nbsp;Edit</a>
                                                    <a onclick='deleteDemerit({$row['DemeritId']})'><img src='../images/dot.gif' class='icon14 delete-icon' alt='Delete icon'>&nbsp;Delete</a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>";
                                }
                                echo '</tbody>';
                            echo '</table>';
                        } else {
                            echo "<p>No demerit records found.</p>";
                        }
                    ?>
                </div>
                <div id="probations">
                    <?php
                        if ($probations_result->num_rows) {
                            echo '<table class="general-table" style="margin: 2rem 0;">';
                                echo '<thead>';
                                    echo '<th align="left">Level</th>';
                                    echo '<th align="left">Start Date</th>';
                                    echo '<th align="left">End Date</th>';
                                    echo '<th align="left" width="320">Reason</th>';
                                    echo '<th align="left">Status</th>';
                                echo '</thead>';
                                echo '<tbody>';
                                while ($row = $probations_result->fetch_assoc()) {
                                    $StartDate = date("m/d/Y", strtotime($row['StartDate']));
                                    $EndDate = date("m/d/Y", strtotime($row['EndDate']));

                                    if ($row['ProbationStatus'] == 1) {
                                        $ProbationStatus = 'Active';
                                    } elseif ($row['ProbationStatus'] == 2) {
                                        $ProbationStatus = 'Past';
                                    }
                                    
                                    echo "<tr>
                                        <td valign='top'>{$row['ProbationLevel']}</td>
                                        <td valign='top'>{$StartDate}</td>
                                        <td valign='top'>{$EndDate}</td>
                                        <td>{$row['ProbationReason']}</td>
                                        <td valign='top'>{$ProbationStatus}</td>
                                    </tr>";
                                }
                                echo '</tbody>';
                            echo '</table>';
                        } else {
                            echo "<p>No probation periods found.</p>";
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
            <?php if ($demerit_count['CumulativePoints'] == $max_demerits AND $MemberStatus == 1) { ?>
                <div class="card">
                    <span class="title">✉️ Probation Warning</span>
                    <div class="content">
                        <span><a onclick="sendWarning()">Send out a message</a> to warn member of a possible probation period.</span>
                    </div>
                </div>
            <?php } ?>
            <?php if ($isRepeatOffender) { ?>
                <div class="card">
                    <span class="title">⚠️ Repeat Offender</span>
                    <div class="content">
                        <span>Repeat offender due to <?php echo $repeatReason; ?>.</spa>
                    </div>
                </div>
            <?php } ?>
            <div class="card">
                <span class="title">Cumulative Demerit Points</span>
                <div class="content">
                    <div class="progress-container">
                        <div class="progress-bar">
                            <?php
                                $demerit_percent = ($demerit_count['CumulativePoints'] / 10) * 100;
                                $rounded_demerit_percent = round($demerit_percent);
                                
                                if ($rounded_demerit_percent >= 100) {
                                    echo '<div class="data" role="progressbar" data-aos="slide-right" data-aos-delay="200" data-aos-duration="1000" data-aos-easing="ease-in-out" style="width: 100%; background-color: rgb(144, 26, 7);"></div>';
                                } elseif ($rounded_demerit_percent >= 70) {
                                    echo '<div class="data" role="progressbar" data-aos="slide-right" data-aos-delay="200" data-aos-duration="1000" data-aos-easing="ease-in-out" style="width: '.$rounded_demerit_percent.'%; background-color: rgb(144, 26, 7);"></div>';
                                } elseif ($rounded_demerit_percent >= 50) {
                                    echo '<div class="data" role="progressbar" data-aos="slide-right" data-aos-delay="200" data-aos-duration="1000" data-aos-easing="ease-in-out" style="width: '.$rounded_demerit_percent.'%; background-color: rgb(152, 120, 5);"></div>';
                                } elseif ($rounded_demerit_percent <= 40) {
                                    echo '<div class="data" role="progressbar" data-aos="slide-right" data-aos-delay="200" data-aos-duration="1000" data-aos-easing="ease-in-out" style="width: '.$rounded_demerit_percent.'%; background-color: rgb(40, 152, 5);"></div>';
                                }
                            ?>
                        </div>
                        <div class="progress-data">
                            <span style="padding-top: 0.8rem;">
                            <?php echo $demerit_count['CumulativePoints']. ' accumulated demerit point(s)'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <?php if ($demerits_result->num_rows) { ?>
            <div class="card">
                <span class="title">Demerit Trends</span>
                <div class="content">
                    <?php
                        while ($trend = $demerits_trends_result->fetch_assoc()) {
                            $demerit = $trend['Demerit'];
                    
                            if ($demerit == 'Academic') {
                                $academicCount++;
                            } elseif ($demerit == 'Attendance') {
                                $attendanceCount++;
                            } elseif ($demerit == 'Discipline') {
                                $disciplineCount++;
                            } elseif ($demerit == 'Miscellaneous') {
                                $miscellaneousCount++;
                            } elseif ($demerit == 'Officer Incident') {
                                $officerCount++;
                            }
                        }
                    
                        $categories = [$academicCount, $attendanceCount, $disciplineCount, $miscellaneousCount, $officerCount];
                        $categoryLabels = ['Academic', 'Attendance', 'Discipline', 'Miscellaneous', 'Officer Incident'];
                        $categoriesJSON = json_encode($categories);
                        $categoryLabelsJSON = json_encode($categoryLabels);

                        echo '<div><canvas id="demeritTrends"></canvas></div>';
                    ?>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
    <script src="../js/script.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        AOS.init();

        <?php if ($demerits_result->num_rows): ?>
        // Demerits Trends
        var categories = <?php echo $categoriesJSON; ?>;
        var categoryLabels = <?php echo $categoryLabelsJSON; ?>;

        var ctx = document.getElementById('demeritTrends').getContext('2d');
        var demeritTrends = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: categoryLabels,
                datasets: [{
                    label: 'Demerit Category',
                    data: categories,
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.2)', 
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(255, 159, 64, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(31, 157, 12, 0.2)',
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)', 
                        'rgba(255, 99, 132, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(31, 157, 12, 1)',
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
    </script>
</body>
</html>
<?php } else {
    header("HTTP/1.0 404 Not Found");
    exit();
} ?>