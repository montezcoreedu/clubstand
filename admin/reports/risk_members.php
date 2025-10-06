<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/chapter_settings.php");
    include("../common/permissions.php");
    include("../common/grade_level_entry.php");

    if (!in_array("Reports", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    $max_absences = (!empty($chapter['MaxUnexcusedAbsence'])) ? (int)$chapter['MaxUnexcusedAbsence'] : 0;
    $max_tardies = (!empty($chapter['MaxUnexcusedTardy'])) ? (int)$chapter['MaxUnexcusedTardy'] : 0;
    $max_demerits = (!empty($chapter['MaxDemerits'])) ? (int)$chapter['MaxDemerits'] : 0;
    $required_services = (!empty($chapter['MaxServiceHours'])) ? (int)$chapter['MaxServiceHours'] : 0;

    $gradeFilter = $_GET['grade'] ?? [];
    if (!is_array($gradeFilter)) {
        $gradeFilter = [$gradeFilter];
    }
    $filterAbsent = isset($_GET['risk_absent']);
    $filterHours = isset($_GET['risk_hours']);
    $filterDemerits = isset($_GET['risk_demerits']);

    $whereConditions = [];

    $whereConditions[] = "m.MemberStatus IN (1, 2)";

    if (!empty($gradeFilter)) {
        $grades = array_map('intval', $gradeFilter);
        $inClause = implode(',', $grades);
        $whereConditions[] = "m.GradeLevel IN ($inClause)";
    }

    $riskConditions = [];
    if ($filterAbsent) {
        $riskConditions[] = "(COALESCE(a.absence_count, 0) > $max_absences OR COALESCE(a.tardy_count, 0) > $max_tardies)";
    }
    if ($filterHours) {
        $riskConditions[] = "COALESCE(ms.total_service_hours, 0) <= $required_services";
    }
    if ($filterDemerits) {
        $riskConditions[] = "COALESCE(d.total_demerit_points, 0) >= $max_demerits";
    }

    if (empty($riskConditions)) {
        $riskConditions[] = "(COALESCE(a.absence_count, 0) > $max_absences OR COALESCE(a.tardy_count, 0) > $max_tardies)
                            OR COALESCE(ms.total_service_hours, 0) <= $required_services
                            OR COALESCE(d.total_demerit_points, 0) >= $max_demerits";
    }

    $whereClause = '';
    if (!empty($whereConditions) || !empty($riskConditions)) {
        $whereClause = 'WHERE ' . implode(' AND ', array_merge($whereConditions, ["(" . implode(' AND ', $riskConditions) . ")"]));
    }

    $query = "SELECT
            m.MemberId,
            m.LastName,
            m.FirstName,
            m.Suffix,
            m.GradeLevel,
            m.MemberPhoto,
            COALESCE(a.absence_count, 0) AS absence_count,
            COALESCE(a.tardy_count, 0) AS tardy_count,
            COALESCE(ms.total_service_hours, 0) AS total_service_hours,
            COALESCE(d.total_demerit_points, 0) AS total_demerit_points
        FROM members m
        LEFT JOIN (
            SELECT 
                MemberId,
                SUM(CASE WHEN Status = 'Absent' THEN 1 ELSE 0 END) AS absence_count,
                SUM(CASE WHEN Status = 'Tardy' THEN 1 ELSE 0 END) AS tardy_count
            FROM attendance
            WHERE Archived = 0
            GROUP BY MemberId
        ) a ON m.MemberId = a.MemberId
        LEFT JOIN (
            SELECT MemberId, SUM(ServiceHours) AS total_service_hours
            FROM memberservicehours
            WHERE Archived = 0
            GROUP BY MemberId
        ) ms ON m.MemberId = ms.MemberId
        LEFT JOIN (
            SELECT MemberId, SUM(DemeritPoints) AS total_demerit_points
            FROM demerits
            WHERE Archived = 0
            GROUP BY MemberId
        ) d ON m.MemberId = d.MemberId
        $whereClause
        ORDER BY m.LastName ASC, m.FirstName ASC";
    $result = $conn->query($query);

    if (isset($_GET['download_csv'])) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=At Risk Members-'.date('m-d-Y').'.csv');

        $output = fopen("php://output", "w");
        fputcsv($output, ['Last Name', 'First Name', 'Grade Level', 'Absent/Tardy Count', 'Total Service Hours', 'Total Demerit Points']);

        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['LastName'],
                $row['FirstName'],
                $row['GradeLevel'],
                $row['absent_tardy_count'],
                $row['total_service_hours'],
                $row['total_demerit_points']
            ]);
        }
        fclose($output);
        exit;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>At Risk Members</title>
    <?php include("../common/head.php"); ?>
    <style>
        #selectGradeLevels {
            width: 30%;
        }

        .form-table td {
            padding: 8px;
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
                <span>At Risk Members</span>
            </li>
        </ul>
        <h2>At Risk Members</h2>
        <form method="get">
            <table class="form-table">
                <tbody>
                    <tr>
                        <td width="220">Grade Level:</td>
                        <td>
                            <select name="grade[]" id="selectGradeLevels" multiple>
                                <?php
                                for ($i = (int)$minGrade; $i <= (int)$maxGrade; $i++) {
                                    $selected = in_array((string)$i, $gradeFilter) ? "selected" : "";
                                    echo "<option value='$i' $selected>" . getGradeLabel($i) . "</option>";
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td width="220">Low Attendance:</td>
                        <td><input type="checkbox" name="risk_absent" <?= isset($_GET['risk_absent']) ? 'checked' : '' ?>></td>
                    </tr>
                    <tr>
                        <td width="220">Low Service Hours:</td>
                        <td><input type="checkbox" name="risk_hours" <?= isset($_GET['risk_hours']) ? 'checked' : '' ?>></td>
                    </tr>
                    <tr>
                        <td width="220">High Demerits:</td>
                        <td><input type="checkbox" name="risk_demerits" <?= isset($_GET['risk_demerits']) ? 'checked' : '' ?>></td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <button type="submit">Apply Filters</button>
                            <a href="risk_members.php" class="btn-link">Reset</a>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <button type="submit" name="download_csv" value="1">Download CSV</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>
        <?php
            if ($result->num_rows) {
                echo '<table class="general-table" style="margin-top: 2rem; margin-bottom: 1.5rem;">';
                    echo '<thead>';
                        echo '<tr>';
                            echo '<th align="left">Member Name</th>';
                            echo '<th align="left">Grade Level</th>';
                            echo '<th>Absences Count</th>';
                            echo '<th>Tardies Count</th>';
                            echo '<th>Total Service Hours</th>';
                            echo '<th>Total Demerit Points</th>';
                        echo '</tr>';
                    echo '</thead>';
                    echo '<tbody>';
                    while ($row = $result->fetch_assoc()) {    
                        if (!empty($row['MemberPhoto'])) {
                            $memberPhoto = "<img src='../../MemberPhotos/{$row['MemberPhoto']}' alt='Member Photo' class='member-photo'>";
                        } else {
                            $memberPhoto = "<img src='../images/noprofilepic.jpeg' alt='Member Photo' class='member-photo'>";
                        }

                        echo "<tr>
                            <td><a href='../members/membership.php?id={$row['MemberId']}' class='member-name'>{$memberPhoto} {$row['LastName']}, {$row['FirstName']} {$row['Suffix']}</a></td>
                            <td>{$row['GradeLevel']}</td>
                            <td align='center'>{$row['absence_count']}</td>
                            <td align='center'>{$row['tardy_count']}</td>
                            <td align='center'>{$row['total_service_hours']}</td>
                            <td align='center'>{$row['total_demerit_points']}</td>
                        </tr>";
                    }
                    echo '</tbody>';
                echo '</table>';
            } else { 
                echo "<p>No at risk members found.</p>";
            }
        ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $("#selectGradeLevels").select2({});
    </script>
</body>
</html>