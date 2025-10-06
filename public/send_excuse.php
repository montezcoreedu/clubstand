<?php
    include("../dbconnection.php");
    include("common/session.php");

    $memberId = $_SESSION['account_id'];

    $meetingDate = isset($_POST['MeetingDate']) ? trim($_POST['MeetingDate']) : '';
    $reason = mysqli_real_escape_string($conn, $_POST['Reason']);
    $otherExplained = mysqli_real_escape_string($conn, $_POST['OtherExplained']);

    $dateParts = explode('/', $meetingDate);
    if (count($dateParts) !== 3) {
        $_SESSION['errorMessage'] = "<div class='error-message'>Invalid date format. Please use mm/dd/yyyy.</div>";
        header("Location: home.php");
        exit();
    }
    $formattedDate = $dateParts[2] . '-' . $dateParts[0] . '-' . $dateParts[1];

    $insertQuery = "INSERT INTO excuse_requests (MemberId, MeetingDate, Reason, OtherExplained) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("isss", $memberId, $formattedDate, $reason, $otherExplained);

    if ($stmt->execute()) {
        $_SESSION['successMessage'] = "<div class='message success'>Your excuse has been submitted successfully.</div>";
    } else {
        $_SESSION['errorMessage'] = "<div class='message error'>There was a problem submitting your excuse. Please try again.</div>";
    }

    $stmt->close();

    header("Location: home.php");
    exit();