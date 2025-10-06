<?php
    include ("../../dbconnection.php");
    include ("../common/session.php");
    include("../common/permissions.php");

    if (!in_array("Demerits", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    $where = [];
    $order = "ORDER BY LastName asc, FirstName asc";

    // Helper function to sanitize inputs
    function clean($value, $conn) {
        return mysqli_real_escape_string($conn, trim($value));
    }

    // Handle and convert date inputs
    $fromDateRaw = isset($_GET['FromDate']) ? clean($_GET['FromDate'], $conn) : '';
    $toDateRaw = isset($_GET['ToDate']) ? clean($_GET['ToDate'], $conn) : '';
    $fromDate = '';
    $toDate = '';

    if (!empty($fromDateRaw)) {
        $dateObj = DateTime::createFromFormat('m/d/Y', $fromDateRaw);
        if ($dateObj) {
            $fromDate = $dateObj->format('Y-m-d');
        }
    }
    if (!empty($toDateRaw)) {
        $dateObj = DateTime::createFromFormat('m/d/Y', $toDateRaw);
        if ($dateObj) {
            $toDate = $dateObj->format('Y-m-d');
        }
    }

    // Date range logic
    if (!empty($fromDate) && !empty($toDate)) {
        $where[] = "DemeritDate BETWEEN '$fromDate' AND '$toDate'";
    } else {
        if (!empty($fromDate)) {
            $where[] = "DemeritDate >= '$fromDate'";
        }
        if (!empty($toDate)) {
            $where[] = "DemeritDate <= '$toDate'";
        }
    }

    // Other filters
    $demeritCategory = isset($_GET['Demerit']) ? clean($_GET['Demerit'], $conn) : '';
    $demeritPoints = isset($_GET['DemeritPoints']) ? intval($_GET['DemeritPoints']) : '';
    $memberQuery = isset($_GET['MemberQuery']) ? clean($_GET['MemberQuery'], $conn) : '';
    $sortBy = isset($_GET['SortBy']) ? clean($_GET['SortBy'], $conn) : '';
    $status = isset($_GET['Status']) ? clean($_GET['Status'], $conn) : '';

    // Apply filters if values are present
    if (!empty($demeritCategory)) {
        $where[] = "Demerit = '$demeritCategory'";
    }
    if (!empty($demeritPoints)) {
        $where[] = "DemeritPoints = '$demeritPoints'";
    }
    if (!empty($memberQuery)) {
        $where[] = "(CONCAT(m.LastName, ', ', m.FirstName, ' ', m.Suffix) LIKE '%$memberQuery%')";
    }

    // Always limit to active members
    $where[] = "(m.MemberStatus IN (1, 2))";

    // Sorting options
    switch ($sortBy) {
        case "date_asc":
            $order = "ORDER BY DemeritDate asc";
            break;
        case "points_desc":
            $order = "ORDER BY DemeritPoints desc";
            break;
        case "points_asc":
            $order = "ORDER BY DemeritPoints asc";
            break;
    }

    // Build WHERE clause
    $whereClause = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

    // Final SQL query
    $sql = "SELECT * FROM demerits d INNER JOIN members m ON d.MemberId = m.MemberId $whereClause $order";

    // Debug: Optional
    // error_log("Demerit SQL: " . $sql);

    // Run the query
    $result = $conn->query($sql);

    // CSV Export
    if (isset($_POST['export_csv'])) {
        $csvResult = $conn->query($sql);

        if ($csvResult->num_rows > 0) {
            $csvOutput = "Member Name,Date,Demerit,Description,Points\n";

            while ($row = $csvResult->fetch_assoc()) {
                $memberName = "{$row['LastName']}, {$row['FirstName']} {$row['Suffix']}";
                $demeritDate = date("n/j/Y", strtotime($row['DemeritDate']));
                $demeritPoints = $row['DemeritPoints'];

                $csvOutput .= "\"$memberName\",\"$demeritDate\",\"{$row['Demerit']}\",\"{$row['DemeritDescription']}\",\"$demeritPoints\"\n";
            }

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="Demerit Search Report of ' . date('M-d-Y h:i A') . '.csv"');
            echo $csvOutput;
            exit;
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Search Query</title>
    <?php include("../common/head.php"); ?>
</head>
<body>
    <?php include("../common/loading.php"); ?>
    <?php include("../common/top-navbar.php"); ?>
    <div id="wrapper">
        <ul class="breadcrumbs">
            <li>
                <a href="../demerits/#search">Demerits Report</a>
            </li>
            <li>
                <span>Search Query</span>
            </li>
        </ul>
        <h2>Search Query</h2>
        <p>Results based on search query:</p>
        <?php
            if ($result->num_rows > 0) {
                echo '<form method="post" style="margin-bottom: 1rem;">';
                    echo '<button type="submit" name="export_csv">Export to CSV</button>';
                echo '</form>';
                echo '<table class="general-table">';
                    echo '<thead>';
                        echo '<th align="left">Member Name</th>';
                        echo '<th align="left">Date</th>';
                        echo '<th align="left">Demerit</th>';
                        echo '<th align="left">Description</th>';
                        echo '<th>Points</th>';
                    echo '</thead>';
                    echo '<tbody>';
                    while ($row = $result->fetch_assoc()) {
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
                echo '<p>No results found.</p>';
            }
        ?>
    </div>
</body>
</html>