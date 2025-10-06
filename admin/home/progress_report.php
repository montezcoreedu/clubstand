<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/chapter_settings.php");
    include("../common/permissions.php");

    if (!in_array("Progress Report", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    $max_absences = (!empty($chapter['MaxUnexcusedAbsence'])) ? (int)$chapter['MaxUnexcusedAbsence'] : 0;
    $max_tardies = (!empty($chapter['MaxUnexcusedTardy'])) ? (int)$chapter['MaxUnexcusedTardy'] : 0;
    $max_demerits = (!empty($chapter['MaxDemerits'])) ? (int)$chapter['MaxDemerits'] : 0;
    $required_services = (!empty($chapter['MaxServiceHours'])) ? (int)$chapter['MaxServiceHours'] : 0;
    $maxGradeLevel = (!empty($chapter['MaxGradeLevel'])) ? (int)$chapter['MaxGradeLevel'] : 12;

    // Progress DB
    $progress_query = "SELECT m.MemberId, m.LastName, m.FirstName, m.Suffix, m.GradeLevel, 
            m.MemberPhoto, m.MembershipYear, m.BAA_Contributor, m.BAA_Leader, 
            m.BAA_Advocate, m.BAA_Capstone,
            COALESCE(sh.TotalServiceHours, 0) AS ServiceHours,
            COALESCE(FLOOR(100.0 * a.TotalPresentExcused / NULLIF(a.TotalAttendance, 0)), 0) AS AttendancePercentage,
            COALESCE(d.TotalDemerits, 0) AS Demerits
        FROM members m
        LEFT JOIN (
            SELECT MemberId, SUM(ServiceHours) AS TotalServiceHours
            FROM (
                SELECT MemberId, ServiceHours FROM memberservicehours
                WHERE Archived = 0
                UNION ALL
                SELECT MemberId, ServiceHours FROM membertransferhours
                WHERE Archived = 0
            ) combined_hours
            GROUP BY MemberId
        ) sh ON m.MemberId = sh.MemberId
        LEFT JOIN (
            SELECT 
                MemberId,
                COUNT(*) AS TotalAttendance,
                SUM(CASE WHEN Status IN ('Present', 'Excused') THEN 1 ELSE 0 END) AS TotalPresentExcused
            FROM attendance
            WHERE Archived = 0
            GROUP BY MemberId
        ) a ON m.MemberId = a.MemberId
        LEFT JOIN (
            SELECT MemberId, SUM(DemeritPoints) AS TotalDemerits
            FROM demerits
            WHERE Archived = 0
            GROUP BY MemberId
        ) d ON m.MemberId = d.MemberId
        WHERE m.MemberStatus IN (1, 2)
        ORDER BY m.LastName asc, m.FirstName asc";
    $progress_result = $conn->query($progress_query);

    // Progress Tracker Query
    $totalMembers = $conn->query("SELECT COUNT(*) FROM members WHERE GradeLevel != $maxGradeLevel AND MemberStatus IN (1, 2)")->fetch_row()[0];

    $eligibleMembers = $conn->query("SELECT COUNT(*) FROM (
        SELECT m.MemberId
        FROM members m
        LEFT JOIN (
            SELECT MemberId,
                SUM(CASE WHEN Status = 'Absent' THEN 1 ELSE 0 END) AS total_absent,
                SUM(CASE WHEN Status = 'Tardy' THEN 1 ELSE 0 END) AS total_tardy
            FROM attendance
            WHERE Archived = 0
            GROUP BY MemberId
        ) a ON m.MemberId = a.MemberId
        LEFT JOIN (
            SELECT MemberId, SUM(ServiceHours) AS total_hours
            FROM memberservicehours
            WHERE Archived = 0
            GROUP BY MemberId
        ) s ON m.MemberId = s.MemberId
        LEFT JOIN (
            SELECT MemberId, SUM(DemeritPoints) AS total_demerits
            FROM demerits
            WHERE Archived = 0
            GROUP BY MemberId
        ) d ON m.MemberId = d.MemberId
        WHERE 
            m.MemberStatus IN (1, 2) AND
            m.GradeLevel != $maxGradeLevel AND
            COALESCE(a.total_absent, 0) < $max_absences AND
            COALESCE(a.total_tardy, 0) < $max_tardies AND
            COALESCE(s.total_hours, 0) > $required_services AND
            COALESCE(d.total_demerits, 0) <= 6
    ) AS eligible_list")->fetch_row()[0];

    $attendanceRisk = $conn->query("SELECT COUNT(*) FROM (
        SELECT a.MemberId,
            SUM(CASE WHEN a.Status = 'Absent' THEN 1 ELSE 0 END) AS total_absent,
            SUM(CASE WHEN a.Status = 'Tardy' THEN 1 ELSE 0 END) AS total_tardy
        FROM attendance a
        JOIN members m ON a.MemberId = m.MemberId
        WHERE m.MemberStatus IN (1, 2)
        AND a.Archived = 0
        GROUP BY a.MemberId
        HAVING total_absent > $max_absences OR total_tardy > $max_tardies
    ) AS risky_attendance")->fetch_row()[0];

    $serviceRisk = $conn->query("SELECT COUNT(*) 
        FROM (
            SELECT m.MemberId, SUM(h.ServiceHours) AS total_hours
            FROM (
                SELECT MemberId, ServiceHours 
                FROM memberservicehours 
                WHERE Archived = 0
                UNION ALL
                SELECT MemberId, ServiceHours 
                FROM membertransferhours 
                WHERE Archived = 0
            ) h
            JOIN members m ON h.MemberId = m.MemberId
            WHERE m.MemberStatus IN (1, 2)
            GROUP BY m.MemberId
            HAVING total_hours < $required_services
        ) AS risky_service")->fetch_row()[0];

    $demeritRisk = $conn->query("SELECT COUNT(*) FROM (
        SELECT d.MemberId, SUM(d.DemeritPoints) AS demerit_count
        FROM demerits d
        JOIN members m ON d.MemberId = m.MemberId
        WHERE m.MemberStatus IN (1, 2)
        AND d.Archived = 0
        GROUP BY d.MemberId
        HAVING demerit_count >= $max_demerits
    ) AS risky_demerits")->fetch_row()[0];

    // Honor Roll & High Performance Members Query
    $highPerformers = [];
    $members = [];
    while ($row = $progress_result->fetch_assoc()) {
        $members[] = $row;

        $attendance = $row['AttendancePercentage'];
        $demerits = $row['Demerits'];
        $hours = $row['ServiceHours'];

        $completed = 0;
        if ($row['BAA_Contributor'] == 1) $completed += 25;
        if ($row['BAA_Leader'] == 1) $completed += 25;
        if ($row['BAA_Advocate'] == 1) $completed += 25;
        if ($row['BAA_Capstone'] == 1) $completed += 25;

        if ($attendance >= 80 && $hours >= $required_services && $demerits <= $max_demerits && $completed >= 75) {
            $row['BAA_Progress'] = $completed . '%';
            $highPerformers[] = $row;
        }
    }

    // At Risk Query
    $at_risk_query = "SELECT
                SUM(CASE
                    WHEN COALESCE(a.absent_tardy_count, 0) > 6
                    OR COALESCE(ms.total_service_hours, 0) <= $required_services
                    OR COALESCE(d.total_demerit_points, 0) >= 7
                    THEN 1 ELSE 0 END
                ) AS at_risk_count,
                COUNT(*) AS total_members
            FROM members m
            LEFT JOIN (
                SELECT MemberId, COUNT(*) AS absent_tardy_count
                FROM attendance
                WHERE Status IN ('Absent', 'Tardy')
                GROUP BY MemberId
            ) a ON m.MemberId = a.MemberId
            LEFT JOIN (
                SELECT MemberId, SUM(ServiceHours) AS total_service_hours
                FROM memberservicehours
                GROUP BY MemberId
            ) ms ON m.MemberId = ms.MemberId
            LEFT JOIN (
                SELECT MemberId, SUM(DemeritPoints) AS total_demerit_points
                FROM demerits
                GROUP BY MemberId
            ) d ON m.MemberId = d.MemberId
            WHERE m.MemberStatus IN (1, 2)";
    $at_risk_result = $conn->query($at_risk_query);
    $atRisk = $notAtRisk = 0;
    if ($at_risk_result && $row = $at_risk_result->fetch_assoc()) {
        $atRisk = (int) $row['at_risk_count'];
        $total = (int) $row['total_members'];
        $notAtRisk = $total - $atRisk;
    }

    // Count new members
    $new_query = mysqli_query($conn, "SELECT COUNT(*) AS count FROM members WHERE MembershipYear = 1 AND MemberStatus IN (1, 2)");
    $new_result = mysqli_fetch_assoc($new_query);
    $new_members = $new_result['count'];

    // Count veteran members
    $veteran_query = mysqli_query($conn, "SELECT COUNT(*) AS count FROM members WHERE MembershipYear > 1 AND MemberStatus IN (1, 2)");
    $veteran_result = mysqli_fetch_assoc($veteran_query);
    $veteran_members = $veteran_result['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Progress Report</title>
    <?php include("../common/head.php"); ?>
    <style>
        .tracker-container {
            flex: 1;
            min-width: 200px;
            padding: 1rem;
            border-radius: 0.5rem;
        }
    </style>
    <script>
        function addSelected() {
            const selected = document.querySelectorAll('.member-checkbox:checked');
            if (selected.length === 0) {
                alert("Please select at least one member to add.");
                return;
            }

            if (confirm("Are you sure you want to add the selected members from Quick Select?")) {
                document.getElementById("addForm").submit();
            }
        }
    </script>
</head>
<body>
    <?php include("../common/loading.php"); ?>
    <?php include("../common/top-navbar.php"); ?>
    <div id="main-wrapper">
        <ul class="breadcrumbs">
            <li>
                <a href="../home/">Member Search</a>
            </li>
            <li>
                <span>Progress Report</span>
            </li>
        </ul>
        <h2>Progress Report</h2>
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <div class="tracker-container" style="background:rgb(212, 237, 218);">
                <strong>Eligible Members:</strong><br>
                <?= $eligibleMembers ?> / <?= $totalMembers ?> members are eligible for next year 🎉
            </div>
            <div class="tracker-container" style="background:rgb(248, 215, 218);">
                <strong>Attendance Risk:</strong><br>
                <?= $attendanceRisk ?> members have max absences or tardies
            </div>
            <div class="tracker-container" style="background:rgb(209, 236, 241);">
                <strong>Service Hour Risk:</strong><br>
                <?= $serviceRisk ?> members have fewer than <?= $required_services ?>  hours
            </div>
            <div class="tracker-container" style="background:rgb(255, 243, 205);">
                <strong>Demerit Risk:</strong><br>
                <?= $demeritRisk ?> members have 6+ demerits
            </div>
        </div>
        <?php
            if (!empty($members)) {
                $memberCount = count($members);
                echo "<p>Current Member Count: $memberCount</p>";

                echo '<form id="addForm" action="../quickselect/add_selected.php" method="post">';
                    echo '<div style="margin-bottom: 1.5rem;">';
                        echo '<button type="submit">Make Quick Select</button>';
                        echo '&nbsp;';
                        echo '<a href="export_progress.php" class="btn-link">Export CSV File</a>';
                    echo '</div>';
                    echo '<table class="members-table" style="margin-bottom: 2rem;">';
                        echo '<thead>';
                            echo '<th><input type="checkbox" id="selectAll"></th>';
                            echo '<th align="left">Member Name</th>';
                            echo '<th align="left">Grade Level</th>';
                            echo '<th align="left">Eligibility Status</th>';
                            echo '<th>Attendance %</th>';
                            echo '<th>Demerits</th>';
                            echo '<th>Service Hours</th>';
                            echo '<th>BAA Progress</th>';
                        echo '</thead>';
                        echo '<tbody>';
                        foreach ($members as $row) {
                            $memberPhoto = !empty($row['MemberPhoto']) 
                            ? "<img src='../../MemberPhotos/{$row['MemberPhoto']}' alt='Member Photo' class='member-photo'>" 
                            : "<img src='../images/noprofilepic.jpeg' alt='Member Photo' class='member-photo'>";

                            if ($row['GradeLevel'] == $maxGradeLevel) {
                                $eligibilityStatus = "Alumni";
                            } elseif (
                                $row['AttendancePercentage'] >= 70 &&
                                $row['Demerits'] <= $max_demerits &&
                                $row['ServiceHours'] >= $required_services
                            ) {
                                $eligibilityStatus = "<span style='color: rgb(74, 132, 36)'>Eligible</span>";
                            } else {
                                $eligibilityStatus = "<span style='color: rgb(186, 18, 18)'>Ineligible</span>";
                            }

                            if ($row['AttendancePercentage'] >= 70) {
                                $attendancePercentage = "<span style='color: rgb(74, 132, 36)'>{$row['AttendancePercentage']}%</span>";
                            } else {
                                $attendancePercentage = "<span style='color: rgb(186, 18, 18)'>{$row['AttendancePercentage']}%</span>";
                            }

                            if ($row['Demerits'] < $max_demerits) {
                                $demerits = "<span style='color: rgb(74, 132, 36)'>{$row['Demerits']}</span>";
                            } elseif ($max_demerits == NULL) {
                                $demerits = "<span>{$row['Demerits']}</span>";
                            } else {
                                $demerits = "<span style='color: rgb(186, 18, 18)'>{$row['Demerits']}</span>";
                            }

                            if ($row['ServiceHours'] >= $required_services) {
                                $serviceHours = "<span style='color: rgb(74, 132, 36)'>{$row['ServiceHours']} hour(s)</span>";
                            } else {
                                $serviceHours = "<span style='color: rgb(186, 18, 18)'>{$row['ServiceHours']} hour(s)</span>";
                            }
                    
                            $year = $row['MembershipYear'];
                            $contributor = $row['BAA_Contributor'];
                            $leader = $row['BAA_Leader'];
                            $advocate = $row['BAA_Advocate'];
                            $capstone = $row['BAA_Capstone'];
                        
                            $is_behind = (
                                ($year >= 2 && $contributor == 2) ||
                                ($year >= 3 && $leader == 2) ||
                                ($year >= 4 && $advocate == 2)
                            );
                        
                            $color = $is_behind ? 'rgb(186, 18, 18)' : 'rgb(74, 132, 36)';
                        
                            if ($contributor == 1 && $leader == 1 && $advocate == 1 && $capstone == 1) {
                                $progress = '100%';
                            } elseif ($contributor == 1 && $leader == 1 && $advocate == 1 && $capstone == 2) {
                                $progress = '75%';
                            } elseif ($contributor == 1 && $leader == 1 && $advocate == 2 && $capstone == 2) {
                                $progress = '50%';
                            } elseif ($contributor == 1 && $leader == 2 && $advocate == 2 && $capstone == 2) {
                                $progress = '25%';
                            } else {
                                $progress = '0%';
                            }
                        
                            $baaProgress = "<span style='color: $color;'>$progress</span>";
                        
                            echo "<tr>
                                <td align='center'>
                                    <input type='checkbox' class='member-checkbox' name='selected_ids[]' value='{$row['MemberId']}'>
                                </td>
                                <td>
                                    <a href='../members/lookup.php?id={$row['MemberId']}' target='_blank' class='member-name'>{$memberPhoto} {$row['LastName']}, {$row['FirstName']} {$row['Suffix']}</a>
                                </td>
                                <td>{$row['GradeLevel']}</td>
                                <td>{$eligibilityStatus}</td>
                                <td align='center'>{$attendancePercentage}</td>
                                <td align='center'>{$demerits}</td>
                                <td align='center'>{$serviceHours}</td>
                                <td align='center'>{$baaProgress}</td>
                            </tr>";
                        }
                        echo '</tbody>';
                    echo '</table>';
                echo '</form>';
            } else {
                echo '<p>No members currently active in chapter.</p>';
            }
        ?>
    </div>
    <div id="side-drawer">
        <div class="drawer-content">
            <div class="card">
                <span class="title">🏅 Honor Roll / High Performers</span>
                <div class="content">
                    <?php
                        if (!empty($highPerformers)) {
                            echo '<table>';
                            $memberCount = 1;
                            foreach ($highPerformers as $member) {
                                $memberPhoto = !empty($member['MemberPhoto']) 
                                ? "<img src='../../MemberPhotos/{$member['MemberPhoto']}' alt='Member Photo' class='member-photo'>" 
                                : "<img src='../images/noprofilepic.jpeg' alt='Member Photo' class='member-photo'>";
                                
                                echo "<tr>
                                        <td align='center'>{$memberCount}.</td>
                                        <td>
                                            <a href='../members/lookup.php?id={$member['MemberId']}' target='_blank' class='member-name'>
                                                {$memberPhoto}
                                                {$member['FirstName']} {$member['LastName']} {$member['Suffix']}
                                            </a>
                                        </td>
                                    </tr>";
                                $memberCount++;
                            }
                            echo '</table>';
                        } else {
                            echo '<span>No high performing members yet.</span>';
                        }
                    ?>
                </div>
            </div>
            <div class="card">
                <span class="title">At Risk Members</span>
                <div class="content">
                    <?php
                        if ($progress_result->num_rows) {
                            echo '<div><canvas id="atRiskChart"></canvas></div>';
                        } else {
                            echo '<span>No active members.</span>';
                        }
                    ?>
                </div>
            </div>
            <div class="card">
                <span class="title">Membership Growth</span>
                <div class="content">
                    <?php
                        if ($progress_result->num_rows) {
                            echo '<div><canvas id="memberGrowthChart"></canvas></div>';
                        } else {
                            echo '<span>No active members.</span>';
                        }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        <?php if (count($members)): ?>
        // Member Select
        const selectAll = document.getElementById("selectAll");
        const checkboxes = document.querySelectorAll(".member-checkbox");

        selectAll.addEventListener("change", () => {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
        });

        checkboxes.forEach(cb => {
            cb.addEventListener("change", () => {
                const allChecked = [...checkboxes].every(cb => cb.checked);
                const noneChecked = [...checkboxes].every(cb => !cb.checked);

                if (allChecked) {
                    selectAll.checked = true;
                    selectAll.indeterminate = false;
                } else if (noneChecked) {
                    selectAll.checked = false;
                    selectAll.indeterminate = false;
                } else {
                    selectAll.indeterminate = true;
                }
            });
        });

        // At Risk
        var ctx = document.getElementById('atRiskChart').getContext('2d');
        var atRiskChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['At Risk', 'Not At Risk'],
                datasets: [{
                    label: 'Member Count',
                    data: [<?php echo $atRisk; ?>, <?php echo $notAtRisk; ?>],
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(75, 192, 192, 1)',
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'At Risk Members Overview'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        precision: 0
                    }
                }
            }
        });

        // Membership Growth
        var ctx = document.getElementById('memberGrowthChart').getContext('2d');
        var memberGrowthChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['New Members', 'Veteran Members'],
                datasets: [{
                    label: 'Membership Breakdown',
                    data: [<?= $new_members ?>, <?= $veteran_members ?>],

                    backgroundColor: [
                        'rgba(142, 181, 11, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                    ],
                    borderColor: [
                        'rgb(142, 181, 11)',
                        'rgba(75, 192, 192, 1)',
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                plugins: {
                    title: {
                        display: true,
                        text: 'New vs Veteran Members'
                    },
                    legend: {
                        display: true,
                        position: 'bottom'
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>