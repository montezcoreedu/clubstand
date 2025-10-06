<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    if (!in_array("Reports", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    $query = "SELECT GradeLevel, COUNT(*) AS Count FROM members WHERE MemberStatus IN (1, 2) GROUP BY GradeLevel";

    $result = $conn->query($query);

    $grades = [];
    $gradeCounts = [];

    while ($row = $result->fetch_assoc()) {
        $grades[] = $row['GradeLevel'];
        $gradeCounts[] = $row['Count'];
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Grade Level Breakdown</title>
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
                <span>Grade Level Breakdown</span>
            </li>
        </ul>
        <h2>Grade Level Breakdown</h2>
        <div>
            <button onclick="window.print()">Print Report</button>
        </div>
        <div class="container">
            <div class="chart-container">
                <canvas id="gradeChart"></canvas>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <th>Grade Level</th>
                        <th>Count</th>
                    </thead>
                    <?php foreach ($grades as $index => $grade): ?>
                        <tr>
                            <td align="center"><?= $grade; ?></td>
                            <td align="center"><?= $gradeCounts[$index]; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>
    <script>
        const ctx = document.getElementById('gradeChart').getContext('2d');
        const gradeChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: <?= json_encode($grades) ?>,
                datasets: [{
                    label: 'Grade Level',
                    data: <?= json_encode($gradeCounts) ?>,
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