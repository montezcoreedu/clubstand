<?php
    include("../dbconnection.php");
    include("common/session.php");
    include("common/chapter_settings.php");

    $memberId = $_SESSION['account_id'];

    $required_hours = isset($chapter['MaxServiceHours']) ? $chapter['MaxServiceHours'] : '';

    // Member Account DB
    $query = "SELECT * FROM members WHERE MemberId = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $memberId);
    $stmt->execute();
    $result = $stmt->get_result();
    $member = $result->fetch_assoc();

    // Attendance DB
    $attendance_query = "SELECT MeetingDate, Status FROM attendance WHERE MemberId = $memberId ORDER BY MeetingDate desc";
    $attendance_result = $conn->query($attendance_query);

    // Attendance Percentage
    $attpercentage_query = "SELECT ROUND((SELECT COUNT(*) FROM attendance WHERE (Status = 'Present' OR Status = 'Excused') AND MemberId = $memberId) * 100 / COUNT(*)) AS AttPercentage FROM attendance WHERE MemberId = $memberId";
    $attpercentage_result = $conn->query($attpercentage_query);
    $att_percent = mysqli_fetch_assoc($attpercentage_result);

    // Demerits DB
    $demerits_query = "SELECT DemeritDate, Demerit, DemeritDescription, DemeritPoints FROM demerits WHERE MemberId = $memberId ORDER BY DemeritDate desc";
    $demerits_result = $conn->query($demerits_query);

    // Cumulative Demerit Points DB
    $pointsdemerits_sql = "SELECT COALESCE(SUM(DemeritPoints), 0) AS CumulativePoints FROM demerits WHERE MemberId = $memberId";
    $pointsdemerits_query = $conn->query($pointsdemerits_sql);
    $demerit_count = mysqli_fetch_assoc($pointsdemerits_query);

    // Community Services DB
    $services_query = "SELECT m.ServiceHours, c.ServiceName, c.ServiceDate, c.ServiceType FROM memberservicehours m INNER JOIN communityservices c ON m.ServiceId = c.ServiceId WHERE MemberId = $memberId ORDER BY c.ServiceDate desc";
    $services_result = $conn->query($services_query);

    // Cumulative Service Hours DB
    $internal_progress_sql = "SELECT SUM(ServiceHours) AS ServiceHours FROM memberservicehours WHERE MemberId = $memberId";
    $internal_progress_query = $conn->query($internal_progress_sql);
    $internal_progress = mysqli_fetch_assoc($internal_progress_query);
    $internal_hours = $internal_progress['ServiceHours'] ?? 0;

    $transfer_progress_sql = "SELECT SUM(ServiceHours) AS ServiceHours FROM membertransferhours WHERE MemberId = $memberId";
    $transfer_progress_query = $conn->query($transfer_progress_sql);
    $transfer_progress = mysqli_fetch_assoc($transfer_progress_query);
    $transfer_hours = $transfer_progress['ServiceHours'] ?? 0;

    $current_hours = $internal_hours + $transfer_hours;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Berkeley FBLA Membership Cloud</title>
    <?php include("common/head.php"); ?>
    <script>
        $( function() {
          $( "#content" ).tabs();
        } );

        $(document).ready(function() {
            $("#meeting_date").datepicker({
                defaultDate: new Date(),
                dateFormat: "mm/dd/yy"
            }).datepicker("setDate", new Date());
        });
    </script>
</head>
<body>
    <div id="wrapper">
        <div style="float: right;">
            <ul>
                <li>
                    <?php echo $_SESSION['FirstName'] . " " . $_SESSION['LastName']; ?>
                    <a href="logout.php" style="font-size: 12px; margin-left: 5px; text-decoration: none;">Logout</a>
                </li>
            </ul>
        </div>
        <img id="bannerImg" src="images/MembershipPortalBanner1.png" alt="Random Image" style="width: 100%; margin-top: 18px;">
        <?php if ($registration_open == 1 && $member['MemberStatus'] == 6) { ?>
            <div class="message success" style="margin-top: 1rem;">
                Welcome back, <?php echo $_SESSION['FirstName']; ?>! We are pleased to announce that the returning members form for the new membership year is now open. <a href="registration.php">Click here to begin!</a>
            </div>
        <?php } ?>
        <h2>Welcome to Berkeley's FBLA Membership Portal!</h2>
        <p>Thank you for being apart of the Berkeley FBLA chapter where communities are created. Your next steps start here! Here you can view your attendance, demerits, service hours, BAA progress, and check for recent chapter updates.</p>
        <?php
            if (isset($_SESSION['successMessage'])) {
                echo $_SESSION['successMessage'];
                unset($_SESSION['successMessage']);
            }

            if (isset($_SESSION['errorMessage'])) {
                echo $_SESSION['errorMessage'];
                unset($_SESSION['errorMessage']);
            }
        ?>
        <div id="content">
            <ul class="subtabs">
                <li>
                    <a href="#attendance">Attendance</a>
                </li>
                <li>
                    <a href="#demerits">Demerits</a>
                </li>
                <li>
                    <a href="#services">Community Services</a>
                </li>
            </ul>
            <div id="attendance">
                <div class="responsive-container">
                    <div class="left-column">
                        <div class="report-pills">
                            <div class="report-pill">
                                <span class="data"><?php echo $att_percent['AttPercentage']; ?>%</span>
                                <span class="title">Attendance Summary</span>
                            </div>
                        </div>
                        <?php
                            if ($attendance_result->num_rows) {
                                echo '<table>';
                                    echo '<thead>';
                                        echo '<tr>';
                                            echo '<th align="left" width="180">Meeting Date</th>';
                                            echo '<th align="left">Status</th>';
                                        echo '</tr>';
                                    echo '</thead>';
                                    echo '<tbody>';
                                    while ($attendance = $attendance_result->fetch_assoc()) {
                                        $MeetingDate = date("m/d/Y", strtotime($attendance['MeetingDate']));

                                        if ($attendance['Status'] == 'Present') {
                                            $attendanceStatus = '<span style="color: rgb(40, 152, 5);">Present</span>';
                                        } elseif ($attendance['Status'] == 'Absent') {
                                            $attendanceStatus = '<span style="color: rgb(144, 26, 7);">Absent</span>';
                                        } elseif ($attendance['Status'] == 'Tardy') {
                                            $attendanceStatus = '<span style="color: rgb(144, 26, 7);">Tardy</span>';
                                        } elseif ($attendance['Status'] == 'Excused') {
                                            $attendanceStatus = '<span style="color: rgb(152, 120, 5);">Excused</span>';
                                        }

                                        echo "<tr>
                                                <td>{$MeetingDate}</td>
                                                <td>{$attendanceStatus}</td>
                                            </tr>";
                                    }
                                    echo '</tbody>';
                                echo '</table>';
                            } else {
                                echo '<p>No attendance records yet.</p>';
                            }
                        ?>
                    </div>
                    <div class="right-column">
                        <img src="images/AbsenceExcuseForumBanner.png" alt="Absence Excuse Forum Banner photo" style="width: 100%; margin-bottom: 10px;">
                        <form action="send_excuse.php" method="post">
                            <table>
                                <colgroup>
                                    <col style="width: 280px;">
                                    <col>
                                </colgroup>
                                <tbody>
                                    <tr>
                                        <td>Date of FBLA meeting or event</td>
                                        <td><input type="text" id="meeting_date" name="MeetingDate"></td>
                                    </tr>
                                    <tr>
                                        <td>Please select the reason for not being able to attend. If reason is not listed, please explain.</td>
                                        <td valign="top">
                                            <select name="Reason">
                                                <option value="Feeling Sick">Feeling Sick</option>
                                                <option value="Medical Appointments">Medical Appointments</option>
                                                <option value="Other Extracurricular Activities">Other Extracurricular Activities</option>
                                                <option value="Sports">Sports</option>
                                                <option value="Other (explain below)">Other (explain below)</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td valign="top">Other (please explain)</td>
                                        <td><textarea name="OtherExplained" rows="4" style="width: 100%;"></textarea></td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="actions">
                                <button type="submit">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div id="demerits">
                <div class="message comment" style="margin-bottom: 18px;">
                    A 60-day probationary period may be implemented upon accumulating six demerit points. The termination process will occur immediately if seven or more points are reached.
                </div>
                <?php
                    if ($demerits_result->num_rows) {
                        echo '<table>';
                            echo '<thead>';
                                echo '<th align="left">Date</th>';
                                echo '<th align="left">Demerit</th>';
                                echo '<th align="left">Description</th>';
                                echo '<th>Points</th>';
                            echo '</thead>';
                            echo '<tbody>';
                            while ($demerit = $demerits_result->fetch_assoc()) {
                                $DemeritDate = date("m/d/Y", strtotime($demerit['DemeritDate']));

                                echo "<tr>
                                        <td>{$DemeritDate}</td>
                                        <td>{$demerit['Demerit']}</td>
                                        <td>{$demerit['DemeritDescription']}</td>
                                        <td align='center'>{$demerit['DemeritPoints']}</td>
                                    </tr>";
                            }
                            echo '</tbody>';
                            echo '<tfoot>';
                                echo '<td align="right" colspan="3"><b>Total Points:</b></td>';
                                echo '<td align="center"><b>'.$demerit_count['CumulativePoints'].'</b></td>';
                            echo '</tfoot>';
                        echo '</table>';
                    } else {
                        echo '<p>No issued demerits recorded.</p>';
                    }
                ?>
            </div>
            <div id="services">
                <div class="message comment" style="margin-bottom: 18px;">
                    As a member you are required to earn at least <?= $required_hours; ?> service hours per membership year. Please be aware that any transfer service hours you may have will be included in the total service hours, if applicable.
                </div>
                <?php
                    if ($services_result->num_rows) {
                        echo '<table>';
                            echo '<thead>';
                                echo '<th align="left">Date</th>';
                                echo '<th align="left">Service Name</th>';
                                echo '<th align="left">Type</th>';
                                echo '<th>Credit Hours</th>';
                            echo '</thead>';
                            echo '<tbody>';
                            while ($service = $services_result->fetch_assoc()) {
                                $ServiceDate = date("m/d/Y", strtotime($service['ServiceDate']));

                                echo "<tr>
                                        <td>{$ServiceDate}</td>
                                        <td>{$service['ServiceName']}</td>
                                        <td>{$service['ServiceType']}</td>
                                        <td align='center'>{$service['ServiceHours']}</td>
                                    </tr>";
                            }
                            echo '</tbody>';
                            echo '<tfoot>';
                                echo '<td align="right" colspan="3"><b>Total Service Hours:</b></td>';
                                echo '<td align="center"><b>'.$current_hours.'</b></td>';
                            echo '</tfoot>';
                        echo '</table>';
                    } else {
                        echo '<p>No community services recorded yet.</p>';
                    }
                ?>
            </div>
        </div>
        <div class="column_box_light_gray">
            <h2>Have questions or need help?</h2>
            <h3>Connect with your membership coordinator by sending an email to</h3>
            <a href="mailto:anastasiabhsfbla@gmail.com">anastasiabhsfbla@gmail.com</a>
        </div>
    </div>
    <footer>
        <div class="container">
            <span>© Core Education, ClubStand</span>
        </div>
    </footer>
    <script>
        var images = [
            "images/MembershipPortalBanner1.png",
            "images/MembershipPortalBanner2.png",
            "images/MembershipPortalBanner3.png",
            "images/MembershipPortalBanner4.png",
        ];

        var randomIndex = Math.floor(Math.random() * images.length);
        var selectedImage = images[randomIndex];
        var imgElement = document.getElementById("bannerImg");

        imgElement.src = selectedImage;
        imgElement.onload = function () {
            imgElement.style.opacity = 1;
        };
    </script>
</body>
</html>