<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    if (!in_array("Progress Report", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    $export_query = "SELECT m.MemberId, m.LastName, m.FirstName, m.Suffix, m.GradeLevel, m.MemberPhoto, m.MembershipYear, m.BAA_Contributor, m.BAA_Leader, m.BAA_Advocate, m.BAA_Capstone,
                        COALESCE(sh.TotalServiceHours, 0) AS ServiceHours,
                        COALESCE(FLOOR(100.0 * a.TotalPresent / NULLIF(a.TotalAttendance, 0)), 0) AS AttendancePercentage,
                        COALESCE(d.TotalDemerits, 0) AS Demerits
                    FROM members m
                    LEFT JOIN (
                        SELECT MemberId, SUM(ServiceHours) AS TotalServiceHours
                        FROM (
                            SELECT MemberId, ServiceHours FROM memberservicehours
                            WHERE Archived = 0
                            UNION ALL
                            SELECT MemberId, ServiceHours FROM membertransferhours
                            WHERE Archived = 0
                        ) combined_hours
                        GROUP BY MemberId
                    ) sh ON m.MemberId = sh.MemberId
                    LEFT JOIN (
                        SELECT 
                            MemberId,
                            COUNT(*) AS TotalAttendance,
                            SUM(CASE WHEN Status = 'Present' THEN 1 ELSE 0 END) AS TotalPresent
                        FROM attendance
                        WHERE Archived = 0
                        GROUP BY MemberId
                    ) a ON m.MemberId = a.MemberId
                    LEFT JOIN (
                        SELECT MemberId, SUM(DemeritPoints) AS TotalDemerits
                        FROM demerits
                        WHERE Archived = 0
                        GROUP BY MemberId
                    ) d ON m.MemberId = d.MemberId
                    WHERE m.MemberStatus IN (1, 2)
                    ORDER BY m.LastName asc, m.FirstName asc";
    $result = $conn->query($export_query);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=Chapter Progress Report-'.date('m-d-Y h:i A').'.csv');

    $output = fopen('php://output', 'w');

    fputcsv($output, ['Member Name', 'Grade Level', 'Attendance %', 'Demerits', 'Service Hours', 'BAA Contributor', 'BAA Leader', 'BAA Advocate', 'BAA Capstone']);

    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['LastName'] . ', ' . $row['FirstName'] . ' ' . $row['Suffix'],
            $row['GradeLevel'],
            $row['AttendancePercentage'],
            $row['Demerits'],
            $row['ServiceHours'],
            $baa_contributor = $row['BAA_Contributor'] == 1 ? 'Completed' : 'Not Started',
            $baa_leader      = $row['BAA_Leader'] == 1 ? 'Completed' : 'Not Started',
            $baa_advocate    = $row['BAA_Advocate'] == 1 ? 'Completed' : 'Not Started',
            $baa_capstone    = $row['BAA_Capstone'] == 1 ? 'Completed' : 'Not Started'
        ]);
    }

    fclose($output);
    exit;