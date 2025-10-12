<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    if (!in_array("Community Services", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    // Community Services DB
    $services_query = "SELECT ServiceId, ServiceName, ServiceDate, ServiceType FROM communityservices WHERE Archived = 0 ORDER BY ServiceDate desc";
    $services_result = $conn->query($services_query);

    // Top Service Hours DB
    $most_hours_query = "SELECT * FROM
            (SELECT m.MemberId, m.LastName, m.FirstName, m.Suffix, m.MemberPhoto, SUM(ms.ServiceHours) as 
            CumulativeServiceHours 
        FROM members m
        INNER JOIN memberservicehours ms ON m.MemberId = ms.MemberId 
        WHERE m.MemberStatus IN (1, 2) AND Archived = 0 
        GROUP BY m.MemberId, m.LastName, m.FirstName, m.Suffix, m.MemberPhoto
        ORDER BY m.LastName asc, m.FirstName asc) as A 
        ORDER BY CumulativeServiceHours desc LIMIT 5";
    $most_hours_result = $conn->query($most_hours_query);

    // Year Service Breakdown
    $services_breakdown_result = $conn->query($services_query);
    $outreachCount = 0;
    $donationCount = 0;
    $environmentalCount = 0;
    $fundraisingCount = 0;
    $sportsCount = 0;
    $volunteerCount = 0;
    $otherCount = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Community Services</title>
    <?php include("../common/head.php"); ?>
</head>
<body>
    <?php include("../common/loading.php"); ?>
    <?php include("../common/top-navbar.php"); ?>
    <div id="main-wrapper">
        <h2>Community Services</h2>
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
        <div style="margin-bottom: 1.5rem;">
            <a href="add.php" class="btn-link">Add</a>
        </div>
        <?php
            if ($services_result->num_rows) {
                echo '<table class="general-table">';
                    echo '<thead>';
                        echo '<th align="left">Date</th>';
                        echo '<th align="left">Service Name</th>';
                        echo '<th align="left">Type</th>';
                        echo '<th align="left">Participants</th>';
                    echo '</thead>';
                    echo '<tbody>';
                    while ($row = $services_result->fetch_assoc()) {
                        $ServiceDate = date("m/d/Y", strtotime($row['ServiceDate']));

                        $participants_sql = "SELECT ServiceId FROM memberservicehours WHERE ServiceId = {$row['ServiceId']}";
                        $participants_query = $conn->query($participants_sql);
                        $participants = mysqli_num_rows($participants_query);

                        echo "<tr>
                            <td>{$ServiceDate}</td>
                            <td><a href='view.php?id={$row['ServiceId']}'>{$row['ServiceName']}</a></td>
                            <td>{$row['ServiceType']}</td>
                            <td>{$participants}</td>
                        </tr>";
                    }
                    echo '</tbody>';
                echo '</table>';
            } else {
                echo '<p>No community services recorded yet.</p>';
            }
        ?>
    </div>
    <div id="side-drawer">
        <div class="drawer-content">
            <div class="card">
                <span class="title">Top Service Hours Leaderboard</span>
                <div class="content">
                    <?php
                        if ($most_hours_result->num_rows) {
                            echo '<table class="leaderboard-table">';
                                echo '<tbody>';
                                $most_service_no = 1;
                                while ($row = $most_hours_result->fetch_assoc()) {
                                    if ($most_service_no == 1) {
                                        $rankIcon = "🥇";
                                    } elseif ($most_service_no == 2) {
                                        $rankIcon = "🥈";
                                    } elseif ($most_service_no == 3) {
                                        $rankIcon = "🥉";
                                    } else {
                                        $rankIcon = $most_service_no;
                                        $rowClass = "";
                                    }

                                    if (!empty($row['MemberPhoto'])) {
                                        $memberPhoto = "<img src='../../MemberPhotos/{$row['MemberPhoto']}' alt='Member Photo' class='member-photo'>";
                                    } else {
                                        $memberPhoto = "<img src='../images/noprofilepic.jpeg' alt='Member Photo' class='member-photo'>";
                                    }
                            
                                    echo "
                                        <tr>
                                            <td align='center'>{$rankIcon}</td>
                                            <td><a href='../members/services.php?id={$row['MemberId']}' class='member-name'>{$memberPhoto} {$row['FirstName']} {$row['LastName']} {$row['Suffix']}</a></td>
                                            <td>{$row['CumulativeServiceHours']} hrs</td>
                                        </tr>
                                    ";
                                    $most_service_no++;
                                }
                                echo '</tbody>';
                            echo '</table>';
                        } else {
                            echo '<span>No members with service hours yet.</span>';
                        }                                                
                    ?>
                </div>
            </div>
            <div class="card">
                <span class="title">Year Service Breakdown</span>
                <div class="content">
                    <?php
                        if ($services_result->num_rows) {
                            while ($service = $services_breakdown_result->fetch_assoc()) {
                                $type = $service['ServiceType'];
                        
                                if ($type == 'Community Outreach') {
                                    $outreachCount++;
                                } elseif ($type == 'Donation') {
                                    $donationCount++;
                                } elseif ($type == 'Donation') {
                                    $donationCount++;
                                } elseif ($type == 'Environmental Work') {
                                    $environmentalCount++;
                                } elseif ($type == 'Fundraising') {
                                    $fundraisingCount++;
                                } elseif ($type == 'Sports & Recreation') {
                                    $sportsCount++;
                                } elseif ($type == 'Volunteer Work') {
                                    $volunteerCount++;
                                } elseif ($type == 'Other') {
                                    $otherCount++;
                                }
                            }
                        
                            $types = [$outreachCount, $donationCount, $environmentalCount, $fundraisingCount, $sportsCount, $volunteerCount, $otherCount];
                            $typeLabels = ['Community Outreach', 'Donation', 'Environmental Work', 'Fundraising', 'Sports & Recreation', 'Volunteer Work', 'Other'];
                            $typesJSON = json_encode($types);
                            $typeLabelsJSON = json_encode($typeLabels);

                            echo '<div><canvas id="typeBreakdown" style="height: 400px;"></canvas></div>';
                        } else {
                            echo '<span>No community services services recorded yet.</span>';
                        }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        <?php if ($services_result->num_rows) : ?>
        var types = <?php echo $typesJSON; ?>;
        var typeLabels = <?php echo $typeLabelsJSON; ?>;

        var ctx = document.getElementById('typeBreakdown').getContext('2d');
        var typeBreakdown = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: typeLabels,
                datasets: [{
                    label: 'Service Type',
                    data: types,
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(255, 159, 64, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(31, 157, 12, 0.2)',
                        'rgba(255, 161, 102, 0.2)',
                        'rgba(8, 147, 162, 0.2)',
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 99, 132, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgb(31, 157, 12)',
                        'rgba(255, 161, 102, 1)',
                        'rgb(8, 147, 162)',
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
                },
                layout: {
                    padding: {
                        top: 0,
                        bottom: -180,
                        left: 0,
                        right: 0
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>