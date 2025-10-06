<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    if (!in_array("Store Membership Data", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["MembershipYear"])) {
        $membershipYear = $_POST["MembershipYear"];
        $storedOn = date("Y-m-d H:i:s");

        $memberQuery = "SELECT MemberId, Position, GradeLevel FROM members WHERE MemberStatus IN (1, 2)";
        $memberResult = $conn->query($memberQuery);

        if ($memberResult->num_rows > 0) {
            $insertStmt = $conn->prepare("
                INSERT INTO membership_history 
                (MembershipYear, MemberId, Position, GradeLevel, Attendance, Demerits, ServiceHours, StoredOn) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            while ($member = $memberResult->fetch_assoc()) {
                $memberId = $member['MemberId'];

                $position = $member['Position'] ?? '';
                $gradeLevel = $member['GradeLevel'] ?? 0;

                $attpercentage_query = "SELECT ROUND((SELECT COUNT(*) FROM attendance WHERE (Status = 'Present' OR Status = 'Excused') AND MemberId = $memberId) * 100 / COUNT(*)) AS AttPercentage FROM attendance WHERE MemberId = $memberId";
                $attpercentage_result = $conn->query($attpercentage_query);
                $attendancePercentage = mysqli_fetch_assoc($attpercentage_result);

                $demeritQuery = "SELECT SUM(DemeritPoints) AS total 
                    FROM demerits 
                    WHERE MemberId = ?";
                $demStmt = $conn->prepare($demeritQuery);
                $demStmt->bind_param("i", $memberId);
                $demStmt->execute();
                $demResult = $demStmt->get_result();
                $totalDemerits = (int)($demResult->fetch_assoc()['total'] ?? 0);

                $internal_progress_sql = "SELECT SUM(ServiceHours) AS ServiceHours FROM memberservicehours WHERE MemberId = $memberId";
                $internal_progress_query = $conn->query($internal_progress_sql);
                $internal_progress = mysqli_fetch_assoc($internal_progress_query);
                $internal_hours = $internal_progress['ServiceHours'] ?? 0;

                $transfer_progress_sql = "SELECT SUM(ServiceHours) AS ServiceHours FROM membertransferhours WHERE MemberId = $memberId";
                $transfer_progress_query = $conn->query($transfer_progress_sql);
                $transfer_progress = mysqli_fetch_assoc($transfer_progress_query);
                $transfer_hours = $transfer_progress['ServiceHours'] ?? 0;

                $totalHours = $internal_hours + $transfer_hours;

                $insertStmt->bind_param(
                    "sisiiids",
                    $membershipYear,
                    $memberId,
                    $member['Position'],
                    $member['GradeLevel'],
                    $attendancePercentage['AttPercentage'],
                    $totalDemerits,
                    $totalHours,
                    $storedOn
                );
                $insertStmt->execute();
            }

            $_SESSION['successMessage'] = "<div class='message success'>Members historical membership recorded successfully!</div>";
        } else {
            $message = "No eligible members found to store.";
            $_SESSION['successMessage'] = "<div class='message success'>No eligible members found to store.</div>";
        }

        header("Location: store_history.php");
        exit;
    }