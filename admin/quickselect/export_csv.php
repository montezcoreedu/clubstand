<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    $columnLabels = $_POST['ColumnName'] ?? [];
    $fieldNames = $_POST['FieldName'] ?? [];

    if (count($columnLabels) !== count($fieldNames) || count($columnLabels) === 0) {
        die("Invalid export parameters.");
    }

    $selectClauses = [];

    foreach ($fieldNames as $index => $fieldInput) {
        $label = $columnLabels[$index];
        $fieldInput = trim($fieldInput);

        if ($fieldInput === '') {
            $selectClauses[] = "'' AS `" . mysqli_real_escape_string($conn, $label) . "`";
            continue;
        }

        if (strtolower($fieldInput) === 'total_demerits') {
            $selectClauses[] = "(SELECT COALESCE(SUM(DemeritPoints), 0) FROM demerits WHERE demerits.MemberId = m.MemberId) AS `" . mysqli_real_escape_string($conn, $label) . "`";
            continue;
        }

        if (strtolower($fieldInput) === 'total_hours') {
            $selectClauses[] = "(SELECT COALESCE(SUM(ServiceHours), 0) FROM memberservicehours WHERE memberservicehours.MemberId = m.MemberId) AS `" . mysqli_real_escape_string($conn, $label) . "`";
            continue;
        }

        if (stripos($fieldInput, ' IS ') !== false) {
            list($rawField, $mapString) = explode(' IS ', $fieldInput, 2);
            $mapPairs = explode(';', $mapString);
            $caseParts = [];

            foreach ($mapPairs as $pair) {
                if (strpos($pair, '=') !== false) {
                    list($val, $text) = explode('=', $pair, 2);
                    $caseParts[] = "WHEN `" . mysqli_real_escape_string($conn, trim($rawField)) . "` = '" . mysqli_real_escape_string($conn, trim($val)) . "' THEN '" . mysqli_real_escape_string($conn, trim($text)) . "'";
                }
            }

            $selectClauses[] = "CASE " . implode(" ", $caseParts) . " ELSE '' END AS `" . mysqli_real_escape_string($conn, $label) . "`";
            continue;
        }

        $tokens = preg_split('/([\s,]+)/', $fieldInput, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $concatParts = [];
        $prevWasField = false;

        foreach ($tokens as $token) {
            $trimmed = trim($token);
            if ($trimmed === ',') {
                $concatParts[] = "', '";
                $prevWasField = false;
            } elseif ($trimmed === '') {
                continue;
            } elseif (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $trimmed)) {
                $concatParts[] = "`" . mysqli_real_escape_string($conn, $trimmed) . "`";
                $prevWasField = true;
            } else {
                $concatParts[] = "'" . mysqli_real_escape_string($conn, $trimmed) . "'";
                $prevWasField = false;
            }
        }

        $selectClauses[] = "CONCAT(" . implode(", ", $concatParts) . ") AS `" . mysqli_real_escape_string($conn, $label) . "`";
    }

    $adminId = $_SESSION['account_id'];
    $sql = "SELECT " . implode(", ", $selectClauses) . " FROM quick_select q 
            INNER JOIN members m ON q.MemberId = m.MemberId 
            WHERE q.AdminId = $adminId 
            AND q.AddedOn >= NOW() - INTERVAL 1 DAY 
            ORDER BY m.LastName asc, m.FirstName asc";

    $result = mysqli_query($conn, $sql);
    if (!$result) {
        die("SQL Error: " . htmlspecialchars(mysqli_error($conn)));
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Exported Members-'.date('m-d-Y h:i A').'.csv');

    $output = fopen('php://output', 'w');

    fputcsv($output, $columnLabels);

    while ($row = mysqli_fetch_assoc($result)) {
        $line = [];
        foreach ($columnLabels as $label) {
            $line[] = $row[$label] ?? '';
        }
        fputcsv($output, $line);
    }

    fclose($output);
    exit;