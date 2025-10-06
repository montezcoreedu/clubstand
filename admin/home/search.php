<?php
    include("../../dbconnection.php");
    if (!isset($conn) || !$conn) {
        die("Database connection failed.");
    }
    include("../common/session.php");

    $searchQuery = isset($_GET['query']) ? trim($_GET['query']) : '';
    $conditions = [];
    $memberStatusCondition = "m.MemberStatus IN (1, 2)";

    $baseQuery = "SELECT m.*, 
            COALESCE(d.TotalDemeritPoints, 0) AS TotalDemeritPoints,
            COALESCE(ms.TotalServiceHours, 0) + COALESCE(mt.TotalTransferHours, 0) AS TotalServiceHours
        FROM members m
        LEFT JOIN (
            SELECT MemberId, SUM(DemeritPoints) AS TotalDemeritPoints
            FROM demerits
            GROUP BY MemberId
        ) d ON m.MemberId = d.MemberId
        LEFT JOIN (
            SELECT MemberId, SUM(ServiceHours) AS TotalServiceHours
            FROM memberservicehours
            GROUP BY MemberId
        ) ms ON m.MemberId = ms.MemberId
        LEFT JOIN (
            SELECT MemberId, SUM(ServiceHours) AS TotalTransferHours
            FROM membertransferhours
            GROUP BY MemberId
        ) mt ON m.MemberId = mt.MemberId";

    if (empty($searchQuery)) {
        $whereClause = "WHERE $memberStatusCondition";
    } else {
        $tags = explode(" AND ", $searchQuery);

        foreach ($tags as $tag) {
            $tag = trim($tag);
            if (strpos($tag, 'NameContains=') === 0) {
                $value = substr($tag, strlen('NameContains='));
                $conditions[] = "(m.FirstName LIKE '%$value%' OR m.LastName LIKE '%$value%')";
            } elseif (preg_match('/^TotalDemeritPoints([<>=]{1,2})(\d+)$/', $tag, $matches)) {
                $operator = $matches[1];
                $value = (int)$matches[2];
                $conditions[] = "(COALESCE(d.TotalDemeritPoints, 0) $operator $value)";
            } elseif (preg_match('/^TotalServiceHours([<>=]{1,2})(\d+)$/', $tag, $matches)) {
                $operator = $matches[1];
                $value = (int)$matches[2];
                $conditions[] = "((COALESCE(ms.TotalServiceHours, 0) + COALESCE(mt.TotalTransferHours, 0)) $operator $value)";
            } elseif (strpos($tag, 'MemberStatus=') === 0) {
                $value = (int)substr($tag, strlen('MemberStatus='));
                $conditions[] = "m.MemberStatus = $value";
            } elseif (strpos($tag, 'LIKE=') === 0 || strpos($tag, 'NOT LIKE=') === 0) {
                $not = strpos($tag, 'NOT LIKE=') === 0;
                $likeExpr = substr($tag, $not ? strlen('NOT LIKE=') : strlen('LIKE='));
                
                if (strpos($likeExpr, ',') !== false) {
                    list($likeField, $likeValue) = explode(",", $likeExpr, 2);
                    $likeField = $conn->real_escape_string($likeField);
                    $likeValue = str_replace(["\\", "'"], ["\\\\", "\\'"], $likeValue);
                    $conditions[] = "m.$likeField " . ($not ? "NOT LIKE" : "LIKE") . " '$likeValue'";
                }            
            } elseif (strpos($tag, '=') !== false) {
                list($field, $value) = explode("=", $tag, 2);
                $field = $conn->real_escape_string($field);
                if (in_array($field, ['GradeLevel', 'Ethnicity', 'ShirtSize', 'MembershipYear'])) {
                    $values = explode(",", $value);
                    $escapedValues = [];
                    foreach ($values as $v) {
                        $escapedValues[] = "'" . $conn->real_escape_string($v) . "'";
                    }
                    $conditions[] = "m.$field IN (" . implode(", ", $escapedValues) . ")";
                } else {
                    $value = $conn->real_escape_string($value);
                    $conditions[] = "m.$field = '$value'";
                }
            } else {
                $value = $conn->real_escape_string($tag);
                $conditions[] = "(m.FirstName LIKE '%$value%' OR m.LastName LIKE '%$value%')";
            }
        }

        $hasCustomMemberStatus = false;
        foreach ($conditions as $c) {
            if (strpos($c, 'm.MemberStatus') !== false) {
                $hasCustomMemberStatus = true;
                break;
            }
        }

        if (!$hasCustomMemberStatus) {
            $conditions[] = $memberStatusCondition;
        }
        $whereClause = "WHERE " . implode(" AND ", $conditions);
    }

    $sql = "$baseQuery $whereClause ORDER BY m.LastName ASC, m.FirstName ASC";
    $result = $conn->query($sql);

    $_SESSION['search_results'] = [];
    $response = [];

    if ($result && $result->num_rows > 0) {
        $adminId = $_SESSION['account_id'];

        while ($row = $result->fetch_assoc()) {
            $memberId = $row["MemberId"];
            $selectId = null;

            $qs_sql = "SELECT SelectId FROM quick_select WHERE AdminId = $adminId AND MemberId = $memberId AND AddedOn >= NOW() - INTERVAL 1 DAY LIMIT 1";
            $qs_result = $conn->query($qs_sql);

            if ($qs_row = $qs_result->fetch_assoc()) {
                $selectId = $qs_row['SelectId'];
            }

            $response[] = [
                "MemberId" => $row["MemberId"],
                "LastName" => $row["LastName"],
                "FirstName" => $row["FirstName"],
                "Suffix" => $row["Suffix"],
                "GradeLevel" => $row["GradeLevel"],
                "MembershipTier" => $row["MembershipTier"],
                "EmailAddress" => $row["EmailAddress"],
                "MemberPhoto" => $row["MemberPhoto"],
                "SelectId" => $selectId
            ];
        }
    }

    $conn->close();

    header('Content-Type: application/json');
    echo json_encode($response);