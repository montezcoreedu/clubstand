<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    $columnLabels = $_GET['ColumnName'] ?? [];
    $fieldNames = $_GET['FieldName'] ?? [];

    $errorMessages = [];
    $selectClauses = [];
    $result = false;

    $validFields = [];
    $fieldQuery = mysqli_query($conn, "DESCRIBE members");
    while ($fieldRow = mysqli_fetch_assoc($fieldQuery)) {
        $validFields[] = $fieldRow['Field'];
    }

    if (count($columnLabels) !== count($fieldNames) || count($columnLabels) === 0) {
        $errorMessages[] = "Error: Invalid column setup.";
    } else {
        foreach ($fieldNames as $index => $fieldInput) {
            $label = $columnLabels[$index];
            $fieldInput = trim($fieldInput);

            if ($fieldInput === '') {
                $selectClauses[] = "'' AS `" . mysqli_real_escape_string($conn, $label) . "`";
                continue;
            }

            if (strtolower($fieldInput) === 'total_demerits') {
                $label = $columnLabels[$index];
                $selectClauses[] = "(SELECT COALESCE(SUM(DemeritPoints), 0) FROM demerits WHERE demerits.MemberId = m.MemberId) AS `" . mysqli_real_escape_string($conn, $label) . "`";
                continue;
            }

            if (strtolower($fieldInput) === 'total_hours') {
                $label = $columnLabels[$index];
                $selectClauses[] = "(
                    SELECT 
                        COALESCE(SUM(ServiceHours), 0) 
                        + 
                        COALESCE((
                            SELECT SUM(ServiceHours) 
                            FROM membertransferhours 
                            WHERE membertransferhours.MemberId = m.MemberId
                        ), 0)
                    FROM memberservicehours 
                    WHERE memberservicehours.MemberId = m.MemberId
                ) AS `" . mysqli_real_escape_string($conn, $label) . "`";
                continue;
            }          

            if (stripos($fieldInput, ' IS ') !== false) {
                list($rawField, $mapString) = explode(' IS ', $fieldInput, 2);
                $rawField = trim($rawField);
                $mapPairs = explode(';', $mapString);
                $caseParts = [];

                if (!in_array($rawField, $validFields)) {
                    $suggestion = '';
                    $shortest = -1;
                    foreach ($validFields as $validField) {
                        $lev = levenshtein($rawField, $validField);
                        if ($lev <= 3 && ($lev < $shortest || $shortest < 0)) {
                            $shortest = $lev;
                            $suggestion = $validField;
                        }
                    }
                    $error = "Field '{$rawField}' not found.";
                    if ($suggestion) {
                        $error .= " Did you mean <strong>{$suggestion}</strong>?";
                    }
                    $errorMessages[] = $error;
                    continue;
                }

                foreach ($mapPairs as $pair) {
                    if (strpos($pair, '=') !== false) {
                        list($val, $text) = explode('=', $pair, 2);
                        $val = trim($val);
                        $text = trim($text);
                        $caseParts[] = "WHEN `" . mysqli_real_escape_string($conn, $rawField) . "` = '" . mysqli_real_escape_string($conn, $val) . "' THEN '" . mysqli_real_escape_string($conn, $text) . "'";
                    }
                }

                $caseSql = "CASE " . implode(" ", $caseParts) . " ELSE '' END AS `" . mysqli_real_escape_string($conn, $label) . "`";
                $selectClauses[] = $caseSql;
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
                    if (!in_array($trimmed, $validFields)) {
                        $suggestion = '';
                        $shortest = -1;
                        foreach ($validFields as $validField) {
                            $lev = levenshtein($trimmed, $validField);
                            if ($lev <= 3 && ($lev < $shortest || $shortest < 0)) {
                                $shortest = $lev;
                                $suggestion = $validField;
                            }
                        }
                        $error = "Field '{$trimmed}' not found.";
                        if ($suggestion) {
                            $error .= " Did you mean <strong>{$suggestion}</strong>?";
                        }
                        $errorMessages[] = $error;
                        continue 2;
                    }

                    if ($prevWasField) {
                        $concatParts[] = "' '";
                    }
                    $concatParts[] = "`" . mysqli_real_escape_string($conn, $trimmed) . "`";
                    $prevWasField = true;
                } elseif (preg_match('/\s+/', $token)) {
                    continue;
                } else {
                    $concatParts[] = "'" . mysqli_real_escape_string($conn, $trimmed) . "'";
                    $prevWasField = false;
                }
            }

            $selectClauses[] = "CONCAT(" . implode(", ", $concatParts) . ") AS `" . mysqli_real_escape_string($conn, $label) . "`";
        }

        if (empty($errorMessages)) {
            $adminId = $_SESSION['account_id'];

            $sql = "SELECT " . implode(", ", $selectClauses) . " FROM quick_select q 
                    INNER JOIN members m ON q.MemberId = m.MemberId 
                    WHERE q.AdminId = $adminId 
                    AND q.AddedOn >= NOW() - INTERVAL 1 DAY 
                    ORDER BY m.LastName asc, m.FirstName asc";
            $result = mysqli_query($conn, $sql);
            if (!$result) {
                $errorMessages[] = "SQL Error: " . htmlspecialchars(mysqli_error($conn));
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Export Preview</title>
    <?php include("../common/head.php"); ?>
</head>
<body>
    <?php include("../common/loading.php"); ?>
    <?php include("../common/top-navbar.php"); ?>
    <div id="wrapper">
        <ul class="breadcrumbs">
            <li>
                <a href="../home/">Member Search</a>
            </li>
            <li>
                <a href="../quickselect/">Quick Select</a>
            </li>
            <li>
                <a href="export_members.php">Export Members</a>
            </li>
            <li>
                <span>Export Preview</span>
            </li>
        </ul>
        <h2>Export Preview</h2>
        <?php if (!empty($errorMessages)): ?>
            <div class="message error">
                <?php foreach ($errorMessages as $error): ?>
                    <?= $error ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if (empty($errorMessages)): ?>
            <form action="export_csv.php" method="post" class="csv-export-form">
                <div>
                    <button type="submit">Export CSV File</button>
                </div>
                <table class="data-table" style="margin: 1rem 0;">
                    <thead>
                        <tr>
                            <?php foreach ($columnLabels as $label): ?>
                                <th align="left"><?= htmlspecialchars($label) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <?php foreach ($columnLabels as $label): ?>
                                        <td><?= htmlspecialchars($row[$label] ?? '') ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="<?= count($columnLabels) ?>">No data found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php foreach ($columnLabels as $label): ?>
                    <input type="hidden" name="ColumnName[]" value="<?= htmlspecialchars($label) ?>">
                <?php endforeach; ?>
                <?php foreach ($fieldNames as $field): ?>
                    <input type="hidden" name="FieldName[]" value="<?= htmlspecialchars($field) ?>">
                <?php endforeach; ?>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
