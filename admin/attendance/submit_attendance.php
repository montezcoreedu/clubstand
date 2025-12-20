<?php
    require __DIR__ . '/../../vendor/autoload.php';

    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/chapter_settings.php");
    include("../common/permissions.php");

    use Mailgun\Mailgun;

    if (!in_array("Attendance", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    $mg = Mailgun::create(getenv('MAILGUN_API_KEY'));
    $mailgunDomain = 'corecommunication.org';

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        try {
            $MeetingDate = date('Y-m-d', strtotime($_POST['MeetingDate']));
            $MemberIds = $_POST['member_id'];
            $Statuses = $_POST['attendance_status'];
            $SendNotifications = isset($_POST['SendNotifications']);

            foreach ($MemberIds as $index => $MemberId) {

                $Status = $Statuses[$index] ?: 'Present';

                $stmt = $conn->prepare("
                    SELECT AttendanceId 
                    FROM attendance 
                    WHERE MemberId = ? AND MeetingDate = ?
                ");
                $stmt->bind_param("is", $MemberId, $MeetingDate);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $stmt = $conn->prepare("
                        UPDATE attendance 
                        SET Status = ? 
                        WHERE MemberId = ? AND MeetingDate = ?
                    ");
                    $stmt->bind_param("sis", $Status, $MemberId, $MeetingDate);
                } else {
                    $stmt = $conn->prepare("
                        INSERT INTO attendance (MemberId, MeetingDate, Status) 
                        VALUES (?, ?, ?)
                    ");
                    $stmt->bind_param("iss", $MemberId, $MeetingDate, $Status);
                }
                $stmt->execute();

                if (in_array($Status, ['Absent', 'Tardy'])) {
                    $stmt = $conn->prepare("
                        SELECT FirstName, LastName, EmailAddress 
                        FROM members 
                        WHERE MemberId = ?
                    ");
                    $stmt->bind_param("i", $MemberId);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($row = $result->fetch_assoc()) {
                        $FirstName = $row['FirstName'];
                        $LastName = $row['LastName'];
                        $EmailAddress = $row['EmailAddress'];
                        $EmailMeetingDate = date('n/j/Y', strtotime($MeetingDate));

                        if ($SendNotifications) {
                            $demeritDate = $MeetingDate;
                            $issuedBy = $_SESSION['FirstName'] . " " . $_SESSION['LastName'];
                            $demerit = 'Attendance';
                            $demeritPoints = 1;

                            if ($Status === 'Absent') {
                                $demeritDescription = 'Unexcused absence in meeting.';
                                $subject = "$FirstName $LastName - FBLA Unexcused Absence";
                                $emailBody = "
                                    <h2 style='text-align:center;'>Unexcused Absence Notification</h2>
                                    <p>Dear $FirstName $LastName,</p>
                                    <p>
                                        You were marked <b>absent</b> for the meeting on 
                                        <b>$EmailMeetingDate</b>. This resulted in an 
                                        <b>automatic demerit</b>.
                                    </p>
                                    <p>
                                        Please complete the 
                                        <a href='https://docs.google.com/forms/...'>
                                            FBLA Absence Form
                                        </a> if applicable.
                                    </p>
                                ";
                            } else {
                                $demeritDescription = 'Unexcused tardy to meeting.';
                                $subject = "$FirstName $LastName - FBLA Tardy Notification";
                                $emailBody = "
                                    <h2 style='text-align:center;'>Tardy Notification</h2>
                                    <p>Dear $FirstName $LastName,</p>
                                    <p>
                                        You were marked <b>tardy</b> for the meeting on 
                                        <b>$EmailMeetingDate</b>. This resulted in a 
                                        <b>demerit</b>.
                                    </p>
                                    <p>Please arrive on time to avoid future demerits.</p>
                                ";
                            }

                            $stmt = $conn->prepare("
                                INSERT INTO demerits 
                                (MemberId, IssuedBy, DemeritDate, Demerit, DemeritDescription, DemeritPoints) 
                                VALUES (?, ?, ?, ?, ?, ?)
                            ");
                            $stmt->bind_param(
                                "issssi",
                                $MemberId,
                                $issuedBy,
                                $demeritDate,
                                $demerit,
                                $demeritDescription,
                                $demeritPoints
                            );
                            $stmt->execute();

                            $mg->messages()->send($mailgunDomain, [
                                'from'    => $chapter['ChapterName'] . " Attendance <no-reply@corecommunication.org>",
                                'to'      => $EmailAddress,
                                'reply-to'=> 'anastasiabhsfbla@gmail.com',
                                'subject' => $subject,
                                'html'    => "
                                    <table align='center' style='font-family: Times New Roman; font-size: 16px; max-width: 720px;'>
                                        <tr>
                                            <td style='padding:20px;'>
                                                $emailBody
                                                <hr style='margin:20px 0;'>
                                                <p>
                                                    If this was in error, contact 
                                                    <a href='mailto:anastasiabhsfbla@gmail.com'>
                                                        anastasiabhsfbla@gmail.com
                                                    </a>.
                                                </p>
                                                <p>
                                                    Thanks,<br>
                                                    {$chapter['ChapterName']}
                                                </p>
                                            </td>
                                        </tr>
                                    </table>
                                "
                            ]);
                        }
                    }
                }
            }

            $_SESSION['successMessage'] =
                "<div class='message success'>Attendance recorded successfully.</div>";
            header("Location: index.php");
            exit();

        } catch (Exception $e) {
            $_SESSION['errorMessage'] =
                "<div class='message error'>Error: {$e->getMessage()}</div>";
            header("Location: index.php");
            exit();
        }
    }