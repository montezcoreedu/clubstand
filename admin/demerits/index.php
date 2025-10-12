<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/chapter_settings.php");
    include("../common/permissions.php");

    if (!in_array("Demerits", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    // Categories DB
    $categories_query = "SELECT CategoryId, CategoryName FROM demerit_categories ORDER BY CategoryName asc";
    $categories_result = $conn->query($categories_query);

    // This Week DB
    $thisweek_query = "SELECT d.DemeritDate, d.Demerit, d.DemeritDescription, d.DemeritPoints, m.MemberId, m.LastName, m.FirstName, m.Suffix, m.MemberPhoto FROM demerits d INNER JOIN members m ON d.MemberId = m.MemberId WHERE d.DemeritDate >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND MemberStatus IN (1, 2) AND Archived = 0 ORDER BY d.DemeritDate desc";
    $thisweek_result = mysqli_query($conn, $thisweek_query);

    // Probation DB
    $probation_query = "SELECT p.ProbationId, p.ProbationLevel, p.StartDate, p.EndDate, m.MemberId, m.LastName, m.FirstName, m.Suffix, m.MemberPhoto FROM probation p INNER JOIN members m ON p.MemberId = m.MemberId WHERE p.EndDate >= CURDATE() AND MemberStatus IN (1, 2) ORDER BY p.EndDate asc";
    $probation_result = mysqli_query($conn, $probation_query);

    // End Probation Alerts DB
    $endProbationQuery = "SELECT p.ProbationId, p.EndDate, m.MemberId, m.LastName, m.FirstName, m.Suffix, m.MemberPhoto FROM probation p INNER JOIN members m ON p.MemberId = m.MemberId WHERE p.EndDate <= CURDATE() AND p.ProbationStatus = 1 AND MemberStatus IN (1, 2) ORDER BY p.EndDate asc, m.LastName asc, m.FirstName asc";
    $endProbationResult = mysqli_query($conn, $endProbationQuery);

    // Demerit Trends
    $check_demerits_query = "SELECT * FROM demerits WHERE Archived = 0";
    $check_demerits = $conn->query($check_demerits_query);

    $demerit_trends_result = $conn->query($check_demerits_query);
    $academicCount = 0;
    $attendanceCount = 0;
    $disciplineCount = 0;
    $miscellaneousCount = 0;
    $officerCount = 0;

    // Members with 5+ Demerit Points
    $maxDemeritsQuery = "SELECT m.MemberId, m.FirstName, m.LastName, m.Suffix, m.GradeLevel, 
            m.MemberPhoto, SUM(d.DemeritPoints) AS TotalPoints
        FROM members m
        JOIN demerits d ON m.MemberId = d.MemberId
        WHERE MemberStatus IN (1, 2)
        AND Archived = 0
        GROUP BY m.MemberId, m.FirstName, m.LastName, m.Suffix, m.GradeLevel, m.MemberPhoto
        HAVING TotalPoints >= 5
        ORDER BY TotalPoints desc, m.LastName asc, m.FirstName";
    $maxDemeritsResult = $conn->query($maxDemeritsQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Demerits</title>
    <?php include("../common/head.php"); ?>
    <script>
        $( function() {
          $( "#demerits" ).tabs();
        } );

        $( function() {
            $( ".DemeritDates" ).datepicker({
                dateFormat: 'm/d/yy',
            });
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
    </script>
</head>
<body>
    <?php include("../common/loading.php"); ?>
    <?php include("../common/top-navbar.php"); ?>
    <div id="main-wrapper">
        <h2>Demerit Report</h2>
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
        <?php
            if ($endProbationResult->num_rows) {
                while ($member = $endProbationResult->fetch_assoc()) {
                    if (!empty($member['MemberPhoto'])) {
                        $memberPhoto = "<img src='../../MemberPhotos/{$member['MemberPhoto']}' alt='Member Photo' class='member-photo'>";
                    } else {
                        $memberPhoto = "<img src='../images/noprofilepic.jpeg' alt='Member Photo' class='member-photo'>";
                    }

                    $EndDate = date("n/j/Y", strtotime($member['EndDate']));

                    echo "<div class='message comment' style='display: flex; align-items: center; margin-bottom: 10px;'>
                        <a href='edit_probation.php?pid={$member['ProbationId']}&id={$member['MemberId']}' class='member-name'>{$memberPhoto} {$member['LastName']}, {$member['FirstName']} {$member['Suffix']}</a>&nbsp;probation ended on {$EndDate}
                    </div>";
                }
            }
        ?>
        <div id="demerits">
            <ul class="subtabs">
                <li>
                    <a href="#thisweek">This Week</a>
                </li>
                <li>
                    <a href="#search">Demerit Search</a>
                </li>
                <li>
                    <a href="#probation">Probation Tracker</a>
                </li>
                <li>
                    <a href="#settings">Settings</a>
                </li>
            </ul>
            <div id="thisweek">
                <?php
                    if ($thisweek_result->num_rows) {
                        echo '<table class="members-table" style="margin-bottom: 2rem;">';
                            echo '<thead>';
                                echo '<th align="left">Member Name</th>';
                                echo '<th align="left">Date</th>';
                                echo '<th align="left">Demerit</th>';
                                echo '<th align="left">Description</th>';
                                echo '<th>Points</th>';
                            echo '</thead>';
                            echo '<tbody>';
                            while ($row = $thisweek_result->fetch_assoc()) {
                                if (!empty($row['MemberPhoto'])) {
                                    $memberPhoto = "<img src='../../MemberPhotos/{$row['MemberPhoto']}' alt='Member Photo' class='member-photo'>";
                                } else {
                                    $memberPhoto = "<img src='../images/noprofilepic.jpeg' alt='Member Photo' class='member-photo'>";
                                }

                                $DemeritDate = date("n/j/Y", strtotime($row['DemeritDate']));

                                echo "<tr>
                                    <td><a href='../members/demerits.php?id={$row['MemberId']}' class='member-name'>{$memberPhoto} {$row['LastName']}, {$row['FirstName']} {$row['Suffix']}</a></td>
                                    <td>{$DemeritDate}</td>
                                    <td>{$row['Demerit']}</td>
                                    <td>{$row['DemeritDescription']}</td>
                                    <td align='center'>{$row['DemeritPoints']}</td>
                                </tr>";
                            }
                            echo '</tbody>';
                        echo '</table>';
                    } else {
                        echo '<p>No demerits issued this week.</p>';
                    }
                ?>
            </div>
            <div id="search">
                <form action="searchquery.php" method="get">
                    <table>
                        <tbody>
                            <tr>
                                <td width="180"><b>From Date:</b></td>
                                <td><input type="text" class="DemeritDates" name="FromDate"></td>
                            </tr>
                            <tr>
                                <td width="180"><b>To Date:</b></td>
                                <td><input type="text" class="DemeritDates" name="ToDate"></td>
                            </tr>
                            <tr>
                                <td width="180"><b>Demerit:</b></td>
                                <td>
                                    <select name="Demerit">
                                        <option value=""></option>
                                        <option value="Academic">Academic</option>
                                        <option value="Attendance">Attendance</option>
                                        <option value="Discipline">Discipline</option>
                                        <option value="Miscellaneous">Miscellaneous</option>
                                        <option value="Officer Incident">Officer Incident</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td width="180"><b>Member Name:</b></td>
                                <td><input type="text" name="MemberQuery" placeholder="Last, First" style="width: 100%; max-width: 280px;"></td>
                            </tr>
                            <tr>
                                <td width="180"><b>Points:</b></td>
                                <td><input type="number" name="DemeritPoints" min="0"></td>
                            </tr>
                            <tr>
                                <td width="180"><b>Sort By:</b></td>
                                <td>
                                    <select name="SortBy">
                                        <option value="lastname_asc">Last Name</option>
                                        <option value="date_desc">Newest First</option>
                                        <option value="date_asc">Oldest First</option>
                                        <option value="points_desc">Most Points</option>
                                        <option value="points_asc">Least Points</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td><button type="submit" name="search">Search</button></td>
                            </tr>
                        </tbody>
                    </table>
                </form>
            </div>
            <div id="probation">
                <?php
                    if ($probation_result->num_rows) {
                        echo '<table class="members-table">';
                            echo '<thead>';
                                echo '<th align="left">Member Name</th>';
                                echo '<th align="left">Start Date</th>';
                                echo '<th align="left">End Date</th>';
                                echo '<th align="left">Level</th>';
                                echo '<th align="left">Progress</th>';
                            echo '</thead>';
                            echo '<tbody>';
                            while ($row = $probation_result->fetch_assoc()) {
                                if (!empty($row['MemberPhoto'])) {
                                    $memberPhoto = "<img src='../../MemberPhotos/{$row['MemberPhoto']}' alt='Member Photo' class='member-photo'>";
                                } else {
                                    $memberPhoto = "<img src='../images/noprofilepic.jpeg' alt='Member Photo' class='member-photo'>";
                                }

                                $StartDate = date("n/j/Y", strtotime($row['StartDate']));
                                $EndDate = date("n/j/Y", strtotime($row['EndDate']));

                                if ($row['ProbationLevel'] == 'Warning' OR $row['ProbationLevel'] == 'Strict') {
                                    $probationLevel = "<span style='color: rgb(152, 120, 5);'>{$row['ProbationLevel']}</span>";
                                } else {
                                    $probationLevel = "<span style='color: rgb(144, 26, 7);'>{$row['ProbationLevel']}</span>";
                                }

                                echo "<tr>
                                        <td><a href='../demerits/edit_probation.php?pid={$row['ProbationId']}&id={$row['MemberId']}' target='_blank' class='member-name'>{$memberPhoto} {$row['LastName']}, {$row['FirstName']} {$row['Suffix']}</a></td>
                                        <td>{$StartDate}</td>
                                        <td>{$EndDate}</td>
                                        <td>{$probationLevel}</td>
                                        <td>";
                                            $start = strtotime($row['StartDate']);
                                            $end = strtotime($row['EndDate']);
                                            $today = strtotime(date("Y-m-d"));

                                            $totalDuration = $end - $start;
                                            $elapsed = $today - $start;

                                            if ($today < $start) {
                                                $progressPercent = 0;
                                                $daysLeft = round(($end - $start) / (60 * 60 * 24));
                                                $status = "Starts soon";
                                            } elseif ($today > $end) {
                                                $progressPercent = 100;
                                                $daysLeft = 0;
                                                $status = "Completed";
                                            } else {
                                                $progressPercent = round(($elapsed / $totalDuration) * 100);
                                                $daysLeft = round(($end - $today) / (60 * 60 * 24));
                                                $status = "$daysLeft day(s) left";
                                            }

                                            echo "<div style='width: 100%; background-color: #eee; border-radius: 50px; overflow: hidden; height: 16px;'>
                                                    <div style='width: {$progressPercent}%; background-color: " . ($progressPercent == 100 ? "rgb(40, 152, 5)" : "rgb(152, 120, 5)") . "; color: rgb(255, 255, 255); text-align: center; font-size: 12px; line-height: 16.5px;'>
                                                        <div style='padding: 0 4px;'>{$progressPercent}%</div>
                                                    </div>
                                                </div>
                                                <small style='display: block; margin-top: 4px;'>{$status}</small>";
                                        echo "</td>
                                </tr>";
                            }
                            echo '</tbody>';
                        echo '</table>';
                    } else {
                        echo '<p>No members under probation currently.</p>';
                    }
                ?>
            </div>
            <div id="settings">
                <div id="accordion">
                    <h3>Categories & Descriptions</h3>
                    <div>
                        <div style="margin-bottom: 1rem;">
                            <a href="add_category.php" class="button">Add</a>
                        </div>
                        <?php
                            if ($categories_result->num_rows) {
                                echo '<table class="general-table">';
                                    echo '<colgroup>';
                                        echo '<col style="width: 40%;">';
                                        echo '<col style="width: 60%;">';
                                    echo '</colgroup>';
                                    echo '<thead>';
                                        echo '<th align="left">Category</th>';
                                        echo '<th align="left">Edit Descriptions</th>';
                                    echo '</thead>';
                                    echo '<tbody>';
                                    while ($row = $categories_result->fetch_assoc()) {
                                        echo "<tr>
                                            <td><a href='edit_category.php?id={$row['CategoryId']}'>{$row['CategoryName']}</a></td>
                                            <td><a href='category_list.php?id={$row['CategoryId']}'>Edit List</a></td>
                                        </tr>";
                                    }
                                    echo '</tbody>';
                                echo '</table>';
                            } else {
                                echo '<p>No demerit categories found.</p>';
                            }
                        ?>
                    </div>
                    <h3>Discipline Settings</h3>
                    <div>
                        <form action="save_discipline.php" method="post">
                            <table class="form-table">
                                <tbody>
                                    <tr>
                                        <td><b>Max Probation Days:</b></td>
                                        <td><input type="number" min="1" name="MaxProbationDays" value="<?= $chapter['MaxProbationDays']; ?>" required style="width: 30%;"></td>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><button type="submit">Save changes</button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="side-drawer">
        <div class="drawer-content">
            <div class="card">
                <span class="title">Demerit Trends</span>
                <div class="content">
                    <?php
                        if ($check_demerits->num_rows) {
                            while ($demerit = $demerit_trends_result->fetch_assoc()) {
                                $demerit = $demerit['Demerit'];
                        
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
                        
                            $trends = [$academicCount, $attendanceCount, $disciplineCount, $miscellaneousCount, $officerCount];
                            $demeritLabels = ['Academic', 'Attendance', 'Discipline', 'Miscellaneous', 'Officer Incident'];
                            $trendsJSON = json_encode($trends);
                            $demeritLabelsJSON = json_encode($demeritLabels);

                            echo '<div><canvas id="demeritTrends"></canvas></div>';
                        } else {
                            echo '<span>No demerits recorded yet.</span>';
                        }
                    ?>
                </div>
            </div>
            <div class="card">
                <span class="title">🚨 Members with 5+ Demerit Points</span>
                <div class="content">
                    <?php
                        if ($maxDemeritsResult->num_rows) {
                            echo '<table>';
                                echo '<tbody>';
                                    while ($member = $maxDemeritsResult->fetch_assoc()) { 
                                        $memberPhoto = !empty($member['MemberPhoto']) 
                                        ? "<img src='../../MemberPhotos/{$member['MemberPhoto']}' alt='Member Photo' class='member-photo'>" 
                                        : "<img src='../images/noprofilepic.jpeg' alt='Member Photo' class='member-photo'>";
                                        
                                        echo "<tr>
                                                <td>
                                                    <a href='../members/demerits.php?id={$member['MemberId']}' class='member-name'>
                                                        {$memberPhoto}
                                                        {$member['FirstName']} {$member['LastName']} {$member['Suffix']}
                                                    </a>
                                                </td>
                                                <td align='center'>{$member['TotalPoints']} point(s)</td>
                                            </tr>";
                                    }
                                echo '</tbody>';
                            echo '</table>';
                        } else {
                            echo '<span>No members have 5 or more demerits.</span>';
                        }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        <?php if ($check_demerits->num_rows): ?>
        // Demerit Trends
        var trends = <?php echo $trendsJSON; ?>;
        var demeritLabels = <?php echo $demeritLabelsJSON; ?>;

        var ctx = document.getElementById('demeritTrends').getContext('2d');
        var demeritTrends = new Chart(ctx, {
            type: 'line',
            data: {
                labels: demeritLabels,
                datasets: [{
                    label: 'Demerit Trend',
                    data: trends,
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
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Demerits Based on Issued'
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
        <?php endif; ?>
    </script>
</body>
</html>