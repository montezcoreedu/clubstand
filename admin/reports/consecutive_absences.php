<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    if (!in_array("Reports", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    $filters = [];

    $filters[] = "m.MemberStatus IN (1, 2)";

    if (!empty($_GET['grade'])) {
        $grade = mysqli_real_escape_string($conn, $_GET['grade']);
        $filters[] = "m.GradeLevel = '$grade'";
    }

    if (!empty($_GET['min_streak'])) {
        $min_streak = (int)$_GET['min_streak'];
    } else {
        $min_streak = 2;
    }

    $filter_sql = count($filters) ? 'WHERE ' . implode(' AND ', $filters) : '';

    $absences_query = "SELECT 
                        m.MemberId AS MemberId,
                        m.FirstName,
                        m.LastName,
                        m.Suffix,
                        m.GradeLevel,
                        m.MemberPhoto,
                        COUNT(*) AS CurrentStreak,
                        GROUP_CONCAT(DATE_FORMAT(recent_absences.MeetingDate, '%c/%e/%Y') ORDER BY recent_absences.MeetingDate DESC SEPARATOR ', ') AS AbsenceDates
                    FROM (
                        SELECT a1.*
                        FROM attendance a1
                        JOIN (
                            SELECT MemberId, MAX(MeetingDate) AS LastDate
                            FROM attendance
                            WHERE Archived = 0
                            GROUP BY MemberId
                        ) latest ON latest.MemberId = a1.MemberId
                        WHERE a1.Status = 'Absent'
                        AND NOT EXISTS (
                            SELECT 1 FROM attendance a2 
                            WHERE a2.MemberId = a1.MemberId
                            AND a2.MeetingDate > a1.MeetingDate
                            AND a2.Status != 'Absent'
                        )
                        ORDER BY a1.MemberId, a1.MeetingDate desc
                    ) recent_absences
                    JOIN members m ON m.MemberId = recent_absences.MemberId
                    $filter_sql
                    GROUP BY recent_absences.MemberId
                    HAVING CurrentStreak >= $min_streak
                    ORDER BY CurrentStreak desc";
    $absences_result = mysqli_query($conn, $absences_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Consecutive Absences</title>
    <?php include("../common/head.php"); ?>
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
                <span>Consecutive Absences</span>
            </li>
        </ul>
        <h2>Consecutive Absences</h2>
        <form method="GET" style="margin-bottom: 1.5rem;">
            <table class="form-table">
                <tbody>
                    <tr>
                        <td width="180"><b>Grade Level:</b></td>
                        <td>
                            <select name="grade">
                                <option value="">All</option>
                                <option value="9" <?= isset($_GET['grade']) && $_GET['grade'] === '9' ? 'selected' : '' ?>>9</option>
                                <option value="10" <?= isset($_GET['grade']) && $_GET['grade'] === '10' ? 'selected' : '' ?>>10</option>
                                <option value="11" <?= isset($_GET['grade']) && $_GET['grade'] === '11' ? 'selected' : '' ?>>11</option>
                                <option value="12" <?= isset($_GET['grade']) && $_GET['grade'] === '12' ? 'selected' : '' ?>>12</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td width="180"><b>Min. Absences:</b></td>
                        <td><input type="number" name="min_streak" value="<?= htmlspecialchars($_GET['min_streak'] ?? 2) ?>" min="2"></td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <button type="submit">Search</button>
                            &nbsp;
                            <a href="consecutive_absences.php" class="btn-link">Reset</a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>
        <?php
            if ($absences_result->num_rows) {
                echo '<table class="members-table" style="margin-bottom: 2rem;">';
                    echo '<thead>';
                        echo '<th align="left">Member Name</th>';
                        echo '<th>Grade Level</th>';
                        echo '<th>Current Streak</th>';
                        echo '<th align="left">Absence Dates</th>';
                    echo '</thead>';
                    echo '<tbody>';
                    while ($row = $absences_result->fetch_assoc()) {
                        if (!empty($row['MemberPhoto'])) {
                            $memberPhoto = "<img src='../../MemberPhotos/{$row['MemberPhoto']}' alt='Member Photo' class='member-photo'>";
                        } else {
                            $memberPhoto = "<img src='../images/noprofilepic.jpeg' alt='Member Photo' class='member-photo'>";
                        }

                        echo "<tr>
                            <td>
                                <a href='../members/lookup.php?id={$row['MemberId']}' target='_blank' class='member-name'>
                                    {$memberPhoto}
                                    {$row['FirstName']} {$row['LastName']} {$row['Suffix']}
                                </a>
                            </td>
                            <td align='center'>{$row['GradeLevel']}</td>
                            <td align='center'>{$row['CurrentStreak']}</td>
                            <td>{$row['AbsenceDates']}</td>
                        </tr>";
                    }
                    echo '</tbody>';
                echo '</table>';
            } else {
                echo '<div class="message comment">No members with current consecutive absences.</div>';
            }
        ?>
    </div>
</body>
</html>