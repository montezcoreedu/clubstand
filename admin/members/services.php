<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/chapter_settings.php");
    include("../common/checkmemberurl.php");
    include("../common/permissions.php");

    if (!in_array("Community Services", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    if (!empty($_GET['id']) && $check_url) {
        include("../common/membercommon.php");

        // Community Services DB
        $services_query = "SELECT c.ServiceName, c.ServiceDate, c.ServiceType, ms.EntryId, ms.ServiceHours FROM communityservices c JOIN memberservicehours ms ON c.ServiceId = ms.ServiceId WHERE ms.MemberId = $getMemberId AND ms.Archived = 0 ORDER BY c.ServiceDate desc";
        $services_result = $conn->query($services_query);

        // Transfer Services DB
        $transfer_query = "SELECT TransferId, OrganizationName, ServiceDate, ServiceName, ServiceHours FROM membertransferhours WHERE MemberId = $getMemberId AND Archived = 0 ORDER BY ServiceDate desc";
        $transfer_result = $conn->query($transfer_query);

        // Cumulative Service Hours
        $internal_progress_sql = "SELECT SUM(ServiceHours) AS ServiceHours FROM memberservicehours WHERE MemberId = $getMemberId AND Archived = 0";
        $internal_progress_query = $conn->query($internal_progress_sql);
        $internal_progress = mysqli_fetch_assoc($internal_progress_query);
        $internal_hours = $internal_progress['ServiceHours'] ?? 0;

        $transfer_progress_sql = "SELECT SUM(ServiceHours) AS ServiceHours FROM membertransferhours WHERE MemberId = $getMemberId AND Archived = 0";
        $transfer_progress_query = $conn->query($transfer_progress_sql);
        $transfer_progress = mysqli_fetch_assoc($transfer_progress_query);
        $transfer_hours = $transfer_progress['ServiceHours'] ?? 0;

        $current_hours = $internal_hours + $transfer_hours;
        $required_services = (!empty($chapter['MaxServiceHours'])) ? (int)$chapter['MaxServiceHours'] : 0;
        $calc_services = ($required_services > 0) ? ceil(($current_hours / $required_services) * 100) : 0;

        // Participation Breakdown
        $services_participation_result = $conn->query($services_query);
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
    <title><?php echo $LastName; ?>, <?php echo $FirstName; ?><?php echo !empty($Suffix) ? ' ' . $Suffix : ''; ?></title>
    <?php include("../common/head.php"); ?>
    <script>
        $( function() {
          $( "#services" ).tabs();
        } );

        $(document).ready(function () {
            Array.from(document.querySelectorAll('#servicestable')).forEach(function(menu_side) {
                menu_side.addEventListener('click', function({ target }) {
                    if (!target.classList.contains('action_button')) return;
                    document.querySelectorAll('.actions.active').forEach(
                        (d) => d !== target.parentElement && d.classList.remove('active')
                    );
                    target.parentElement.classList.toggle('active');
                });
            });
        });

        $(document).ready(function () {
            Array.from(document.querySelectorAll('#transfertable')).forEach(function(menu_side) {
                menu_side.addEventListener('click', function({ target }) {
                    if (!target.classList.contains('action_button')) return;
                    document.querySelectorAll('.actions.active').forEach(
                        (d) => d !== target.parentElement && d.classList.remove('active')
                    );
                    target.parentElement.classList.toggle('active');
                });
            });
        });

        function deleteService(ServiceId) {
            if (confirm("Are you sure you want to delete this service?")) {
                window.location.href='../services/delete.php?sid='+ServiceId+'&id=<?php echo $getMemberId; ?>';
                return true;
            }
        }

        function deleteTransfer(TransferId) {
            if (confirm("Are you sure you want to delete this transfer?")) {
                window.location.href='../services/delete_transfer.php?tid='+TransferId+'&id=<?php echo $getMemberId; ?>';
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
            <h2>Services & Fundraising</h2>
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
            <div id="services">
                <ul class="subtabs">
                    <li>
                        <a href="#internal">Internal Services</a>
                    </li>
                    <li>
                        <a href="#transfer">Transfer Hours</a>
                    </li>
                </ul>
                <div id="internal">
                    <div>
                        <a href="../services/assign_service.php?id=<?php echo $getMemberId; ?>" class="btn-link">Add</a>
                    </div>
                    <?php
                        if ($services_result->num_rows) {
                            echo '<table class="general-table" id="servicestable" style="margin: 2rem 0;">';
                                echo '<thead>';
                                    echo '<th align="left">Date</th>';
                                    echo '<th align="left">Service Name</th>';
                                    echo '<th align="left">Type</th>';
                                    echo '<th align="left">Credit Hours</th>';
                                    echo '<th align="left">Actions</th>';
                                echo '</thead>';
                                echo '<tbody>';
                                while ($row = $services_result->fetch_assoc()) {
                                    $ServiceDate = date("n/j/Y", strtotime($row['ServiceDate']));

                                    echo "<tr>
                                        <td>{$ServiceDate}</td>
                                        <td>{$row['ServiceName']}</td>
                                        <td>{$row['ServiceType']}</td>
                                        <td>{$row['ServiceHours']}</td>
                                        <td>
                                            <div class='actions-dropdown'>
                                                <button type='button' class='action_button'>Actions&nbsp;&nbsp;<i class='fa-solid fa-caret-right'></i></button>
                                                <div class='action_menu'>
                                                    <a href='../services/edit_service.php?sid={$row['EntryId']}&id=$getMemberId'><img src='../images/dot.gif' class='icon14 edit-icon' alt='Edit icon'>&nbsp;Edit</a>
                                                    <a onclick='deleteService({$row['EntryId']})'><img src='../images/dot.gif' class='icon14 delete-icon' alt='Delete icon'>&nbsp;Delete</a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>";
                                }
                                echo '</tbody>';
                            echo '</table>';
                        } else {
                            echo "<p>No community service records found.</p>";
                        }
                    ?>
                </div>
                <div id="transfer">
                    <div>
                        <a href="../services/assign_transfer.php?id=<?php echo $getMemberId; ?>" class="btn-link">Add</a>
                    </div>
                    <div>
                        <?php
                            if ($transfer_result->num_rows) {
                                echo '<table class="general-table" id="transfertable" style="margin: 2rem 0;">';
                                    echo '<thead>';
                                        echo '<th align="left">Organization</th>';
                                        echo '<th align="left">Date</th>';
                                        echo '<th align="left">Service Name</th>';
                                        echo '<th align="left">Credit Hours</th>';
                                        echo '<th align="left">Actions</th>';
                                    echo '</thead>';
                                    echo '<tbody>';
                                    while ($row = $transfer_result->fetch_assoc()) {
                                        $ServiceDate = date("n/j/Y", strtotime($row['ServiceDate']));

                                        echo "<tr>
                                            <td>{$row['OrganizationName']}</td>
                                            <td>{$ServiceDate}</td>
                                            <td>{$row['ServiceName']}</td>
                                            <td>{$row['ServiceHours']}</td>
                                            <td>
                                                <div class='actions-dropdown'>
                                                    <button type='button' class='action_button'>Actions&nbsp;&nbsp;<i class='fa-solid fa-caret-right'></i></button>
                                                    <div class='action_menu'>
                                                        <a href='../services/edit_transfer.php?tid={$row['TransferId']}&id=$getMemberId'><img src='../images/dot.gif' class='icon14 edit-icon' alt='Edit icon'>&nbsp;Edit</a>
                                                        <a onclick='deleteTransfer({$row['TransferId']})'><img src='../images/dot.gif' class='icon14 delete-icon' alt='Delete icon'>&nbsp;Delete</a>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>";
                                    }
                                    echo '</tbody>';
                                echo '</table>';
                            } else {
                                echo "<p>No transfer hours found.</p>";
                            }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <button id="toggle-drawer">
        <i id="drawer-icon" class="fas fa-chevron-right"></i>
    </button>
    <div class="open" id="member-drawer">
        <div class="drawer-content">
            <div class="card">
                <span class="title">Cumulative Service Hours</span>
                <div class="content">
                    <div class="progress-container">
                        <?php
                            $current_hours = isset($current_hours) ? (float)$current_hours : 0;

                            $barColor = 'rgb(240, 240, 240)';
                            $width = '0%';
                            $message = 'No service hours recorded yet.';

                            if ($required_services === 0) {
                                if ($current_hours > 0) {
                                    $width = '100%';
                                    $barColor = 'rgb(40, 152, 5)';
                                    $message = "{$current_hours} service hours recorded";
                                } else {
                                    $message = "No service hours recorded";
                                }
                            } else {
                                $calc_services = ($current_hours / $required_services) * 100;
                                $calc_services = min($calc_services, 100);

                                if ($current_hours >= $required_services) {
                                    $width = '100%';
                                    $barColor = 'rgb(40, 152, 5)';
                                    $message = "{$current_hours} out of {$required_services} hours completed";
                                } elseif ($current_hours >= 0.5) {
                                    $width = $calc_services . '%';
                                    $barColor = 'rgb(152, 120, 5)';
                                    $message = "{$current_hours} out of {$required_services} hours completed";
                                } elseif ($current_hours > 0) {
                                    $width = $calc_services . '%';
                                    $barColor = 'rgb(144, 26, 7)';
                                    $message = "{$current_hours} out of {$required_services} hours completed";
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
                <span class="title">Participation Breakdown</span>
                <div class="content">
                    <?php
                        if ($services_result->num_rows) {
                            while ($service = $services_participation_result->fetch_assoc()) {
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

                            echo '<div><canvas id="participationBreakdown" style="height: 400px;"></canvas></div>';
                        } else {
                            echo '<span>No service hours recorded yet.</span>';
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

        <?php if ($services_result->num_rows) { ?>
        var types = <?php echo $typesJSON; ?>;
        var typeLabels = <?php echo $typeLabelsJSON; ?>;

        var ctx = document.getElementById('participationBreakdown').getContext('2d');
        var participationBreakdown = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: typeLabels,
                datasets: [{
                    label: 'Participation Breakdown',
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
        <?php } ?>
    </script>
</body>
</html>
<?php } else {
    header("HTTP/1.0 404 Not Found");
    exit();
} ?>