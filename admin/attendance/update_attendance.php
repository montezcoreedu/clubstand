<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    if (!in_array("Attendance", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $MeetingDate = date('Y-m-d', strtotime($_POST['MeetingDate']));
        $MemberIds = $_POST['member_id'];
        $Statuses = $_POST['attendance_status'];

        foreach ($MemberIds as $index => $MemberId) {
            $Status = $Statuses[$index] ?: 'Present';

            $stmt = $conn->prepare("SELECT AttendanceId FROM attendance WHERE MemberId = ? AND MeetingDate = ?");
            $stmt->bind_param("is", $MemberId, $MeetingDate);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $stmt = $conn->prepare("UPDATE attendance SET Status = ? WHERE MemberId = ? AND MeetingDate = ?");
                $stmt->bind_param("sis", $Status, $MemberId, $MeetingDate);
            } else {
                $stmt = $conn->prepare("INSERT INTO attendance (MemberId, MeetingDate, Status) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $MemberId, $MeetingDate, $Status);
            }
            $stmt->execute();
        }

        $_SESSION['successMessage'] = "<div class='message success'>Attendance updated successfully.</div>";
        header("Location: index.php");
        exit();
    }