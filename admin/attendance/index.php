<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    if (!in_array("Attendance", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    // Meetings DB
    $attendance_query = "SELECT DISTINCT MeetingDate FROM attendance WHERE Archived = 0 ORDER BY MeetingDate desc";
    $attendance_result = $conn->query($attendance_query);

    // Excuses DB & Filter Query
    $where = [];
    if (!empty($_GET['name'])) {
        $name = $conn->real_escape_string($_GET['name']);
        $where[] = "(m.FirstName LIKE '%$name%' OR m.LastName LIKE '%$name%')";
    }

    if (!empty($_GET['date'])) {
        $date = $conn->real_escape_string($_GET['date']);
        $where[] = "e.MeetingDate = '$date'";
    }

    if (!empty($_GET['reason'])) {
        $reason = $conn->real_escape_string($_GET['reason']);
        $where[] = "e.Reason = '$reason'";
    }

    if (empty($_GET['show_past'])) {
        $where[] = "e.MeetingDate >= CURDATE()";
    }

    $where[] = "e.Archived = 0";

    $where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

    $excuses_query = "SELECT e.MeetingDate, e.Reason, e.OtherExplained, m.MemberId, m.LastName, m.FirstName, m.Suffix, m.MemberPhoto 
                    FROM excuse_requests e 
                    INNER JOIN members m ON e.MemberId = m.MemberId 
                    $where_sql
                    ORDER BY e.MeetingDate desc, m.LastName asc, m.FirstName asc";
    $excuses_result = $conn->query($excuses_query);

    // Members Perfect Attendance DB
    $perfectAttendanceQuery = "SELECT 
                                m.MemberId, 
                                m.FirstName, 
                                m.LastName, 
                                m.Suffix, 
                                m.MemberPhoto
                            FROM members m
                            JOIN attendance a ON a.MemberId = m.MemberId
                            WHERE a.Status = 'Present'
                            AND m.MemberStatus IN (1, 2)
                            AND Archived = 0
                            GROUP BY m.MemberId, m.FirstName, m.LastName, m.Suffix, m.MemberPhoto
                            HAVING COUNT(*) = (
                                SELECT COUNT(*) 
                                FROM attendance a2 
                                WHERE a2.MemberId = m.MemberId
                            )
                            ORDER BY m.LastName asc, m.FirstName asc";
    $perfectAttendanceResult = $conn->query($perfectAttendanceQuery);

    // Last Meeting Attendance
    $latestMeetingQuery = "SELECT MeetingDate FROM attendance WHERE Archived = 0 ORDER BY MeetingDate DESC LIMIT 1";
    $latestResult = $conn->query($latestMeetingQuery);

    $latestDate = null;
    $latestDateFormatted = '';
    $latestAttendanceCounts = [
        'Present' => 0,
        'Absent' => 0,
        'Tardy' => 0,
        'Excused' => 0
    ];

    if ($latestResult && $latestRow = $latestResult->fetch_assoc()) {
        $latestDate = $latestRow['MeetingDate'];
        $latestDateFormatted = date("m/d/Y", strtotime($latestDate));

        $meetingStatsQuery = "SELECT Status, COUNT(*) as Count FROM attendance WHERE MeetingDate = '$latestDate' AND Archived = 0 GROUP BY Status";
        $meetingStatsResult = $conn->query($meetingStatsQuery);

        while ($row = $meetingStatsResult->fetch_assoc()) {
            $status = $row['Status'];
            if (isset($latestAttendanceCounts[$status])) {
                $latestAttendanceCounts[$status] = (int)$row['Count'];
            }
        }
    }

    $latestLabelsJSON = json_encode(array_keys($latestAttendanceCounts));
    $latestValuesJSON = json_encode(array_values($latestAttendanceCounts));

    // All-Time Attendance
    $attendanceCounts = [
        'Present' => 0,
        'Absent' => 0,
        'Tardy' => 0,
        'Excused' => 0
    ];

    $all_time_query = "SELECT Status, COUNT(*) as Count FROM attendance WHERE Archived = 0 GROUP BY Status";
    $all_time_result = $conn->query($all_time_query);

    while ($row = $all_time_result->fetch_assoc()) {
        $status = $row['Status'];
        if (isset($attendanceCounts[$status])) {
            $attendanceCounts[$status] = (int)$row['Count'];
        }
    }

    $attendanceLabels = json_encode(array_keys($attendanceCounts));
    $attendanceValues = json_encode(array_values($attendanceCounts));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Attendance</title>
    <?php include("../common/head.php"); ?>
    <script>
        $(function() {
            const hash = window.location.hash;
            const $tabs = $("#attendance").tabs();

            if (hash && $(hash).length) {
                const index = $("#attendance ul.subtabs a").index($("a[href='" + hash + "']"));
                if (index !== -1) {
                    $tabs.tabs("option", "active", index);
                }
            }

            $("#attendance ul.subtabs a").on("click", function() {
                window.location.hash = this.hash;
            });
        });

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
        <h2>Attendance Report</h2>
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
        <div id="attendance">
            <ul class="subtabs">
                <li>
                    <a href="#meetings">Meetings</a>
                </li>
                <li>
                    <a href="#excuses">Excuse Requests</a>
                </li>
            </ul>
            <div id="meetings">
                <div style="margin-bottom: 1rem;">
                    <a href="add.php" class="btn-link">Add</a>
                </div>
                <?php
                    if ($attendance_result->num_rows) {
                        echo '<table class="general-table">';
                            echo '<thead>';
                                echo '<th align="left">Meeting Date</th>';
                                echo '<th>Present</th>';
                                echo '<th>Tardy</th>';
                                echo '<th>Absent</th>';
                            echo '</thead>';
                            echo '<tbody>';
                            while ($row = $attendance_result->fetch_assoc()) {
                                $MeetingDate = date("m/d/Y", strtotime($row['MeetingDate']));

                                $present_sql = "SELECT AttendanceId FROM attendance WHERE MeetingDate = '".$row['MeetingDate']."' AND Status = 'Present'";
                                $present_query = $conn->query($present_sql);
                                $present_members = mysqli_num_rows($present_query);

                                $tardy_sql = "SELECT AttendanceId FROM attendance WHERE MeetingDate = '".$row['MeetingDate']."' AND Status = 'Tardy'";
                                $tardy_query = $conn->query($tardy_sql);
                                $tardy_members = mysqli_num_rows($tardy_query);

                                $absent_sql = "SELECT AttendanceId FROM attendance WHERE MeetingDate = '".$row['MeetingDate']."' AND Status = 'Absent' OR MeetingDate = '".$row['MeetingDate']."' AND Status = 'Excused'";
                                $absent_query = $conn->query($absent_sql);
                                $absent_members = mysqli_num_rows($absent_query);

                                echo "<tr>
                                    <td><a href='edit.php?date={$row['MeetingDate']}'>{$MeetingDate}</a></td>
                                    <td align='center'>$present_members</td>
                                    <td align='center'>$tardy_members</td>
                                    <td align='center'>$absent_members</td>
                                </tr>";
                            }
                            echo '</tbody>';
                        echo '</table>';
                    } else {
                        echo '<p>No attendance records found.</p>';
                    }
                ?>
            </div>
            <div id="excuses">
                <form method="GET" action="#excuses" class="excuse-filters" style="margin-bottom: 2rem;">
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <td width="180"><b>Member Name:</b></td>
                                <td><input type="text" name="name" placeholder="Search by last or first name" value="<?= htmlspecialchars($_GET['name'] ?? '') ?>" style="width: 100%; max-width: 280px;"></td>
                            </tr>
                            <tr>
                                <td width="180"><b>Meeting Date:</b></td>
                                <td><input type="date" name="date" value="<?= htmlspecialchars($_GET['date'] ?? '') ?>"></td>
                            </tr>
                            <tr>
                                <td width="180"><b>Reason:</b></td>
                                <td>
                                    <select name="reason">
                                        <option value="">All Reasons</option>
                                        <option value="Feeling Sick" <?= ($_GET['reason'] ?? '') == 'Feeling Sick' ? 'selected' : '' ?>>Feeling Sick</option>
                                        <option value="Medical Appointments" <?= ($_GET['reason'] ?? '') == 'Medical Appointments' ? 'selected' : '' ?>>Medical Appointments</option>
                                        <option value="Other Extracurricular Activities" <?= ($_GET['reason'] ?? '') == 'Other Extracurricular Activities' ? 'selected' : '' ?>>Other Extracurricular Activities</option>
                                        <option value="Sports" <?= ($_GET['reason'] ?? '') == 'Sports' ? 'selected' : '' ?>>Sports</option>
                                        <option value="Other (explain below)" <?= ($_GET['reason'] ?? '') == 'Other (explain below)' ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td width="180"><b>Show Past Excuses:</b></td>
                                <td><input type="checkbox" name="show_past" value="1" <?= isset($_GET['show_past']) ? 'checked' : '' ?>></td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <button type="submit">Search</button>
                                    &nbsp;
                                    <a href="index.php#excuses" class="btn-link">Reset</a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </form>
                <?php if ($excuses_result->num_rows): ?>
                    <form id="addForm" action="../quickselect/add_selected.php" method="post">
                        <div style="margin-bottom: 1rem;">
                            <button type="submit">Make Quick Select</button>
                        </div>
                        <table class="members-table" style="margin-bottom: 2rem;">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAll"></th>
                                    <th align="left">Member Name</th>
                                    <th align="left">Meeting Date</th>
                                    <th align="left">Reason</th>
                                    <th align="left" width="320">Other (please explain)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $excuses_result->fetch_assoc()): ?>
                                    <?php
                                        $memberPhoto = !empty($row['MemberPhoto'])
                                            ? "<img src='../../MemberPhotos/{$row['MemberPhoto']}' alt='Member Photo' class='member-photo'>"
                                            : "<img src='../images/noprofilepic.jpeg' alt='Member Photo' class='member-photo'>";

                                        $MeetingDate = date("m/d/Y", strtotime($row['MeetingDate']));
                                    ?>
                                    <tr>
                                        <td align="center">
                                            <input type="checkbox" class="member-checkbox" name="selected_ids[]" value="<?= $row['MemberId'] ?>">
                                        </td>
                                        <td>
                                            <a href="../members/attendance.php?id=<?= $row['MemberId'] ?>" class="member-name">
                                                <?= $memberPhoto ?>
                                                <?= htmlspecialchars($row['LastName']) . ", " . htmlspecialchars($row['FirstName']) . " " . htmlspecialchars($row['Suffix']) ?>
                                            </a>
                                        </td>
                                        <td><?= $MeetingDate ?></td>
                                        <td><?= htmlspecialchars($row['Reason']) ?></td>
                                        <td><?= htmlspecialchars($row['OtherExplained']) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </form>
                <?php else: ?>
                    <div class="message comment" style="margin-top: 1rem;">No excuse records found.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div id="side-drawer">
        <div class="drawer-content">
            <div class="card">
                <span class="title">Perfect Meeting Attendance</span>
                <div class="content">
                    <?php
                        if ($perfectAttendanceResult->num_rows) {
                            echo '<table>';
                                echo '<tbody>';
                                $memberCount = 1;
                                while ($member = $perfectAttendanceResult->fetch_assoc()) {
                                    if (!empty($member['MemberPhoto'])) {
                                        $memberPhoto = "<img src='../../MemberPhotos/{$member['MemberPhoto']}' alt='Member Photo' class='member-photo'>";
                                    } else {
                                        $memberPhoto = "<img src='../images/noprofilepic.jpeg' alt='Member Photo' class='member-photo'>";
                                    }
                                
                                    echo "<tr>
                                            <td align='center'>{$memberCount}.</td>
                                            <td>
                                                <a href='../members/attendance.php?id={$member['MemberId']}' class='member-name'>
                                                    {$memberPhoto}
                                                    {$member['FirstName']} {$member['LastName']} {$member['Suffix']}
                                                </a>
                                            </td>
                                        </tr>";
                                    $memberCount++;
                                }
                                echo '</tbody>';
                            echo '</table>';
                        } else {
                            echo '<span>No members with perfect attendance.</span>';
                        }
                    ?>
                </div>
            </div>
            <?php if ($latestResult->num_rows) { ?>
            <div class="card">
                <span class="title">Latest Meeting (<?= $latestDateFormatted ?>)</span>
                <div class="content">
                    <?php if ($latestDate): ?>
                        <div><canvas id="latestMeetingChart"></canvas></div>
                    <?php else: ?>
                        <span>No attendance data available yet.</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php } ?>
            <div class="card">
                <span class="title">All-Time Attendance Overview</span>
                <div class="content">
                    <?php
                        if ($all_time_result->num_rows) {
                            echo '<div><canvas id="allTimeAttendanceChart"></canvas></div>';
                        } else {
                            echo '<span>No attendance records.</span>';
                        }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        <?php if ($excuses_result->num_rows): ?>
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
        <?php endif; ?>

        <?php if ($latestResult->num_rows) { ?>
        // Latest Meeting Attendance
        var latestLabels = <?= $latestLabelsJSON ?>;
        var latestValues = <?= $latestValuesJSON ?>;

        var ctxLatest = document.getElementById('latestMeetingChart').getContext('2d');
        var latestMeetingChart = new Chart(ctxLatest, {
            type: 'doughnut',
            data: {
                labels: latestLabels,
                datasets: [{
                    label: 'Latest Attendance',
                    data: latestValues,
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.2)',  // Present
                        'rgba(255, 99, 132, 0.2)',  // Absent
                        'rgba(255, 206, 86, 0.2)',  // Tardy
                        'rgba(153, 102, 255, 0.2)'  // Excused
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 99, 132, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(153, 102, 255, 1)'
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
        
        <?php if ($all_time_result->num_rows) { ?>
        // All-Time Attendance Overview
        var attendanceValues = <?= $attendanceValues ?>;
        var attendanceLabels = <?= $attendanceLabels ?>;

        var ctx = document.getElementById('allTimeAttendanceChart').getContext('2d');
        var allTimeAttendanceChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: attendanceLabels,
                datasets: [{
                    label: 'Attendance',
                    data: attendanceValues,
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.2)',  // Present
                        'rgba(255, 99, 132, 0.2)',  // Absent
                        'rgba(255, 206, 86, 0.2)',  // Tardy
                        'rgba(153, 102, 255, 0.2)'  // Excused
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 99, 132, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(153, 102, 255, 1)'
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