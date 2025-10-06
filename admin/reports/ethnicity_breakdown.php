<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    if (!in_array("Reports", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    $query = "SELECT Ethnicity, COUNT(*) AS Count FROM members WHERE MemberStatus IN (1, 2) GROUP BY Ethnicity";

    $result = $conn->query($query);

    $ethnicities = [];
    $ethnicityCounts = [];

    while ($row = $result->fetch_assoc()) {
        $ethnicities[] = $row['Ethnicity'];
        $ethnicityCounts[] = $row['Count'];
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Ethnicity Breakdown</title>
    <?php include("../common/head.php"); ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .container {
            justify-content: center;
        }

        .chart-container {
            width: 40%;
            min-width: 400px;
        }

        .table-container {
            width: 35%;
            min-width: 300px;
        }
    </style>
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
                <span>Ethnicity Breakdown</span>
            </li>
        </ul>
        <h2>Ethnicity Breakdown</h2>
        <div>
            <button onclick="window.print()">Print Report</button>
        </div>
        <div class="container">
            <div class="chart-container">
                <canvas id="ethnicityChart"></canvas>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <th>Ethnicity</th>
                        <th>Count</th>
                    </thead>
                    <?php foreach ($ethnicities as $index => $ethnicity): ?>
                        <tr>
                            <td align="center"><?= $ethnicity; ?></td>
                            <td align="center"><?= $ethnicityCounts[$index]; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>
    <script>
        const ctx = document.getElementById('ethnicityChart').getContext('2d');
        const ethnicityChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: <?= json_encode($ethnicities) ?>,
                datasets: [{
                    label: 'Ethnicity',
                    data: <?= json_encode($ethnicityCounts) ?>,
                    backgroundColor: ['#FF5733', '#33FF57', '#3357FF', '#FF33A1', '#57FF33'],
                    borderColor: 'rgba(255, 255, 255, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
            }
        });
    </script>
</body>
</html>