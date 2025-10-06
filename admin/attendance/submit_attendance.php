<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/chapter_settings.php");
    include("../common/permissions.php");

    if (!in_array("Attendance", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    require '../../PHPMailer/src/Exception.php';
    require '../../PHPMailer/src/PHPMailer.php';
    require '../../PHPMailer/src/SMTP.php';

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'montezbhsfbla@gmail.com';
            $mail->Password = 'xswpfxfdlndcloje';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            $mail->setFrom('montezbhsfbla@gmail.com', ''. $chapter['ChapterName'] .' Attendance Relations');
            $mail->addReplyTo('smalleys@bcsdschools.net');
            $mail->addReplyTo('lampkinl@bcsdschools.net');
            $mail->addReplyTo('montezbhsfbla@gmail.com');
            $mail->isHTML(true);

            $MeetingDate = date('Y-m-d', strtotime($_POST['MeetingDate']));
            $MemberIds = $_POST['member_id'];
            $Statuses = $_POST['attendance_status'];
            $SendNotifications = isset($_POST['SendNotifications']) ? $_POST['SendNotifications'] : false;

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

                if (in_array($Status, ['Absent', 'Tardy'])) {
                    $stmt = $conn->prepare("SELECT FirstName, LastName, EmailAddress FROM members WHERE MemberId = ?");
                    $stmt->bind_param("i", $MemberId);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    $EmailMeetingDate = date('n/j/Y', strtotime($_POST['MeetingDate']));
                
                    if ($row = $result->fetch_assoc()) {
                        $FirstName = $row['FirstName'];
                        $LastName = $row['LastName'];
                        $EmailAddress = $row['EmailAddress'];
                
                        if ($SendNotifications) {
                            $demeritDate = $MeetingDate;
                            $issuedBy = $_SESSION['FirstName'] . " " . $_SESSION['LastName'];
                
                            if ($Status === 'Absent') {
                                $demerit = 'Attendance';
                                $demeritDescription = 'Unexcused absence in meeting.';
                                $subject = "$FirstName $LastName - FBLA Unexcused Absence";
                                $emailBody = "
                                    <h2 style='text-align: center;'>Unexcused Absence Notification</h2>
                                    <p>Dear $FirstName $LastName,</p>
                                    <p>You were marked absent for the meeting on <b>$EmailMeetingDate</b>. This resulted in an <b>automatic demerit</b>. 
                                    Please make sure to fill out the <a href='https://docs.google.com/forms/...'>FBLA Absence Form</a> in advance if you can't attend.</p>
                                ";
                            } else {
                                $demerit = 'Attendance';
                                $demeritDescription = 'Unexcused tardy to meeting.';
                                $subject = "$FirstName $LastName - FBLA Tardy Notification";
                                $emailBody = "
                                    <h2 style='text-align: center;'>Tardy Notification</h2>
                                    <p>Dear $FirstName $LastName,</p>
                                    <p>You were marked tardy for the meeting on <b>$EmailMeetingDate</b>. This has resulted in a <b>demerit</b>. 
                                    Please aim to be on time to avoid future demerits.</p>
                                ";
                            }
                
                            $demeritPoints = 1;
                
                            $stmt = $conn->prepare("INSERT INTO demerits (MemberId, IssuedBy, DemeritDate, Demerit, DemeritDescription, DemeritPoints) VALUES (?, ?, ?, ?, ?, ?)");
                            $stmt->bind_param("issssi", $MemberId, $issuedBy, $demeritDate, $demerit, $demeritDescription, $demeritPoints);
                            $stmt->execute();
                
                            $mail->clearAddresses();
                            $mail->addAddress($EmailAddress);
                            $mail->Subject = $subject;
                            $mail->Body = "
                                <table align='center' style='font-family: Times New Roman; font-size: 16px; max-width: 720px;'>
                                    <tr><td style='padding: 20px;'>$emailBody
                                        <hr style='border-top: 1px solid #ccc; margin: 20px 0;'>
                                        <p>If this was in error, please contact <a href='mailto:montezbhsfbla@gmail.com'>montezbhsfbla@gmail.com</a>.</p>
                                        <p>Thanks,<br>" . $chapter['ChapterName'] . "</p>
                                    </td></tr>
                                </table>";
                
                            if (!$mail->send()) {
                                $_SESSION['errorMessage'] = "<div class='message error'>Email failed to send: " . $mail->ErrorInfo . "</div>";
                            }
                        }
                    }
                }
                
            }

            $_SESSION['successMessage'] = "<div class='message success'>Attendance recorded successfully.</div>";
            header("Location: index.php");
            exit();
        } catch (Exception $e) {
            $_SESSION['errorMessage'] = "<div class='message error'>Mailer Error: " . $mail->ErrorInfo . "</div>";
            header("Location: index.php");
            exit();
        }
    }