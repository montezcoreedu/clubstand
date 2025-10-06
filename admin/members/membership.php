<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/chapter_settings.php");
    include("../common/checkmemberurl.php");
    include("../common/permissions.php");

    if (!in_array("Member Membership", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    $schools = !empty($chapter['Schools']) ? explode(',', $chapter['Schools']) : [];

    $max_absences = (!empty($chapter['MaxUnexcusedAbsence'])) ? (int)$chapter['MaxUnexcusedAbsence'] : 0;
    $max_tardies = (!empty($chapter['MaxUnexcusedTardy'])) ? (int)$chapter['MaxUnexcusedTardy'] : 0;
    $max_demerits = (!empty($chapter['MaxDemerits'])) ? (int)$chapter['MaxDemerits'] : 0;
    $required_services = (!empty($chapter['MaxServiceHours'])) ? (int)$chapter['MaxServiceHours'] : 0;

    if (!empty($_GET['id']) && $check_url) {
        include("../common/membercommon.php");

        // Current Status
        $internal_progress_sql = "SELECT SUM(ServiceHours) AS ServiceHours FROM memberservicehours WHERE MemberId = $getMemberId AND Archived = 0";
        $internal_progress_query = $conn->query($internal_progress_sql);
        $internal_progress = mysqli_fetch_assoc($internal_progress_query);
        $internal_hours = $internal_progress['ServiceHours'] ?? 0;

        $transfer_progress_sql = "SELECT SUM(ServiceHours) AS ServiceHours FROM membertransferhours WHERE MemberId = $getMemberId AND Archived = 0";
        $transfer_progress_query = $conn->query($transfer_progress_sql);
        $transfer_progress = mysqli_fetch_assoc($transfer_progress_query);
        $transfer_hours = $transfer_progress['ServiceHours'] ?? 0;

        $service_hours = $internal_hours + $transfer_hours;

        $demerits_query = "SELECT SUM(DemeritPoints) AS total_demerits FROM demerits WHERE MemberId = $getMemberId AND Archived = 0";
        $demerits_result = $conn->query($demerits_query);
        $demerits = ($demerits_result && $row = $demerits_result->fetch_assoc()) ? (int)$row['total_demerits'] : 0;

        $attendance_query = "SELECT 
                                SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) AS total_absences,
                                SUM(CASE WHEN status = 'Tardy' THEN 1 ELSE 0 END) AS total_tardies
                            FROM attendance
                            WHERE MemberId = $getMemberId AND Archived = 0";
        $attendance_result = $conn->query($attendance_query);
        $attendance = ($attendance_result && $row = $attendance_result->fetch_assoc()) ? $row : ['total_absences' => 0, 'total_tardies' => 0];

        $currentStatusNote = [];

        if ($service_hours <= $required_services) {
            $currentStatusNote[] = "Low service hours (≤ $required_services)";
        }

        if ($demerits >= 7) {
            $currentStatusNote[] = "High demerits (≥ $max_demerits)";
        }

        if ($attendance['total_absences'] > $max_absences || $attendance['total_tardies'] > $max_tardies) {
            $currentStatusNote[] = "Too many absences (> $max_absences) or tardies (> $max_tardies)";
        }

        $currentStatusString = !empty($currentStatusNote) ? implode("<br> ", $currentStatusNote) : "In good standing";

        // Termination DB
        $termination_query = "SELECT * FROM termination WHERE MemberId = $getMemberId AND TerminationStatus = 1 ORDER BY TerminationDate desc LIMIT 1";
        $termination_result = $conn->query($termination_query);
        $termination = mysqli_fetch_assoc($termination_result);

        $terminationStatus = $termination_result->num_rows > 0;
        if ($terminationStatus) {
            $terminationId = $termination['TerminationId'];
            $terminationIssued = $termination['IssuedBy'];
            $terminationDate = $termination['TerminationDate'];
            $terminationReason = $termination['TerminationReason'];
        }

        // Historical Records DB
        $history_query = "SELECT MembershipYear, Position, GradeLevel, Attendance, Demerits, ServiceHours, StoredOn FROM membership_history WHERE MemberId = $getMemberId ORDER BY StoredOn desc";
        $history_result = $conn->query($history_query);

        if (isset($_POST['save_member'])) {
            $memberStatus = mysqli_real_escape_string($conn, $_POST['MemberStatus']);
            $membershipYear = mysqli_real_escape_string($conn, $_POST['MembershipYear']);
            $membershipTier = mysqli_real_escape_string($conn, $_POST['MembershipTier']);
            $school = mysqli_real_escape_string($conn, $_POST['School']);
            if (isset($_POST['LockAccess'])) {
                $lockAccess = "1";
            } else {
                $lockAccess = "2";
            }
            if (isset($_POST['RegistrationCompleted'])) {
                $registrationCompleted = "1";
            } else {
                $registrationCompleted = "0";
            }
            $username = mysqli_real_escape_string($conn, $_POST['Username']);
            $password = !empty($_POST['Password']) ? password_hash($_POST['Password'], PASSWORD_DEFAULT) : '';
            $nextMembership = mysqli_real_escape_string($conn, $_POST['NextMembership']);

            do {
                $member_sql = 'UPDATE members SET ';

                if ($memberStatus != '') $member_sql .= '`MemberStatus`="'.$memberStatus.'", ';
                if ($membershipYear != '') $member_sql .= '`MembershipYear`="'.$membershipYear.'", ';
                if ($membershipTier != '') $member_sql .= '`MembershipTier`="'.$membershipTier.'", ';
                if ($school != '') $member_sql .= '`School`="'.$school.'", ';
                if ($lockAccess != '') $member_sql .= '`LockAccess`="'.$lockAccess.'", ';
                if ($registrationCompleted != '') $member_sql .= '`RegistrationCompleted`="'.$registrationCompleted.'", ';
                if ($username != '') $member_sql .= '`Username`="'.$username.'", ';
                if (!empty($_POST['Password'])) $member_sql .= '`Password`="'.$password.'", ';
                if ($nextMembership != '') $member_sql .= '`NextMembership`="'.$nextMembership.'", ';
                if ($nextMembership == '') $member_sql .= '`NextMembership`=NULL, ';

                $member_sql .= "`memberid` = $getMemberId WHERE `members`.`memberid` = $getMemberId";
                $member_query = $conn->query($member_sql);

                if ($member_query) {
                    $_SESSION['successMessage'] = "<div class='message success'>Membership settings successfully saved!</div>";
                    header("Location: membership.php?id=$getMemberId");
                    exit;
                } else {
                    $_SESSION['errorMessage'] = "<div class='message error'>Something went wrong.</div>";
                    header("Location: membership.php?id=$getMemberId");
                    exit;
                }
            } while (false);
        }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $LastName; ?>, <?php echo $FirstName; ?><?php echo !empty($Suffix) ? ' ' . $Suffix : ''; ?></title>
    <?php include("../common/head.php"); ?>
    <script>
        $( function() {
          $( "#membership" ).tabs();
        } );

        $( function() {
            var icons = {
                header: "iconClosed",
                activeHeader: "iconOpen"
            };
            $( "#accordion" ).accordion({
                icons: icons,
                collapsible: true,
                heightStyle: "content"
            });
        } );

        function generateKey() {
            if (confirm("Are you sure you want to generate this member a access key?")) {
                window.location.href='generate_key.php?id=<?php echo $getMemberId; ?>';
                return true;
            }
        }

        <?php if ($terminationStatus) { ?>
        function restoreMembership(TerminationId) {
            if (confirm("Restore <?php echo $FirstName; ?> <?php echo $LastName; ?>'s membership?")) {
                window.location.href='restore.php?tid='+TerminationId+'&id=<?php echo $getMemberId; ?>';
                return true;
            }
        }
        <?php } ?>
    </script>
</head>
<body>
    <?php include("../common/loading.php"); ?>
    <?php include("../common/top-navbar.php"); ?>
    <?php include("../common/memberhead.php"); ?>
    <div id="content">
        <?php include("../common/member-sidebar.php"); ?>
        <div id="main-content-wrapper">
            <h2>Membership</h2>
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
            <div id="membership">
                <ul class="subtabs">
                    <li>
                        <a href="#settings">Settings</a>
                    </li>
                    <li>
                        <a href="#historical">Historical Records</a>
                    </li>
                </ul>
                <?php
                    if ($terminationStatus) {
                        echo "<div id='accordion' style='margin-bottom: 1rem;'>
                                <h3>Termination Status</h3>
                                <div>
                                    <table class='data-table'>
                                        <tbody>
                                            <tr>
                                                <td width='280' valign='top'><b>Issued by:</b></td>
                                                <td>" . $terminationIssued . "</td>
                                            </tr>
                                            <tr>
                                                <td width='280'><b>Terminated Date:</b></td>
                                                <td>" . date('F j, Y', strtotime($terminationDate)) . "</td>
                                            </tr>
                                            <tr>
                                                <td width='280' valign='top'><b>Explained Reason:</b></td>
                                                <td>" . $terminationReason . "</td>
                                            </tr>
                                    </table>
                                </div>
                            </div>";
                    }
                ?>
                <div id="settings">
                    <form method="post">
                        <table class="form-table">
                            <thead>
                                <th align="left" colspan="2">Member Settings</th>
                            </thead>
                            <tbody>
                                <tr>
                                    <td width="180"><b>Member Status:</b></td>
                                    <td>
                                        <select name="MemberStatus">
                                            <option value="1" <?php if ($MemberStatus == '1') { echo 'selected'; } ?>>Active</option>
                                            <option value="2" <?php if ($MemberStatus == '2') { echo 'selected'; } ?>>Probation</option>
                                            <option value="3" <?php if ($MemberStatus == '3') { echo 'selected'; } ?>>Terminated</option>
                                            <option value="4" <?php if ($MemberStatus == '4') { echo 'selected'; } ?>>Dropped</option>
                                            <option value="5" <?php if ($MemberStatus == '5') { echo 'selected'; } ?>>Alumni</option>
                                            <option value="6" <?php if ($MemberStatus == '6') { echo 'selected'; } ?>>Pre-Registered</option>
                                        </select>
                                        <?php if ($MemberStatus != 3) { ?>
                                            <a href="terminate.php?id=<?php echo $getMemberId; ?>" style="float: right;">Terminate Membership</a>
                                        <?php } else { ?>
                                            <?php if ($terminationStatus) { ?>
                                                <a onclick="restoreMembership('<?php echo $terminationId; ?>')" style="float: right;">Restore Membership</a>
                                            <?php } ?>
                                        <?php } ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="180"><b>Membership Year:</b></td>
                                    <td>
                                        <select name="MembershipYear">
                                            <option value="1" <?php if ($MembershipYear == '1') { echo 'selected'; } ?>>1</option>
                                            <option value="2" <?php if ($MembershipYear == '2') { echo 'selected'; } ?>>2</option>
                                            <option value="3" <?php if ($MembershipYear == '3') { echo 'selected'; } ?>>3</option>
                                            <option value="4" <?php if ($MembershipYear == '4') { echo 'selected'; } ?>>4</option>
                                            <option value="5" <?php if ($MembershipYear == '5') { echo 'selected'; } ?>>5</option>
                                            <option value="6" <?php if ($MembershipYear == '6') { echo 'selected'; } ?>>6</option>
                                            <option value="7" <?php if ($MembershipYear == '7') { echo 'selected'; } ?>>7</option>
                                            <option value="8" <?php if ($MembershipYear == '8') { echo 'selected'; } ?>>8</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="180"><b>Membership Tier:</b></td>
                                    <td>
                                        <input list="tierList" name="MembershipTier" value="<?php echo $MembershipTier; ?>" placeholder="Type or select tier" maxlength="100" style="width: 30%;">
                                        <datalist id="tierList">
                                        <?php
                                            $query = "SELECT DISTINCT MembershipTier FROM members ORDER BY MembershipTier asc";
                                            $res = $conn->query($query);
                                            while ($row = $res->fetch_assoc()) {
                                                echo "<option value='" . htmlspecialchars($row['MembershipTier']) . "'>";
                                            }
                                        ?>
                                        </datalist>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="180"><b>School:</b></td>
                                    <td>
                                        <select name="School" required>
                                            <?php foreach ($schools as $school): ?>
                                                <?php $school = trim($school); ?>
                                                <option value="<?php echo htmlspecialchars($school); ?>"
                                                    <?php if (!empty($School) && $School === $school) echo 'selected'; ?>>
                                                    <?php echo htmlspecialchars($school); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                            </tbody>
                            <thead>
                                <th align="left" colspan="2">Portal Account</th>
                            </thead>
                            <tbody>
                                <tr>
                                    <td width="200"><b>Lock Access:</b></td>
                                    <td><input type="checkbox" name="LockAccess" <?php echo ($LockAccess == "1")?"checked":"" ?>></td>
                                </tr>
                                <tr>
                                    <td width="200"><b>Registration Completed:</b></td>
                                    <td><input type="checkbox" name="RegistrationCompleted" <?php echo ($RegistrationCompleted == "1")?"checked":"" ?>></td>
                                </tr>
                                <tr>
                                    <td width="180"><b>Registration Key:</b></td>
                                    <td>
                                        <input type="text" name="RegistrationKey" value="<?php echo $RegistrationKey; ?>" disabled style="width: 100%; max-width: 120px;">
                                        <?php if (empty($RegistrationKey)) { ?>
                                        &nbsp;&nbsp;
                                        <a onclick='generateKey()' style="display: inline-flex; align-items: center;"><img src="../images/dot.gif" class="icon14 key-icon" alt="Generate Key icon">&nbsp;&nbsp;Generate Key</a>
                                        <?php } ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="180"><b>Username:</b></td>
                                    <td><input type="text" name="Username" value="<?php echo $Username; ?>"></td>
                                </tr>
                                <tr>
                                    <td width="180"><b>Password:</b></td>
                                    <td>
                                        <input type="password" name="Password" placeholder="Leave blank to keep current password" style="width: 100%; max-width: 280px;">
                                        <?php if (!empty($Password)) { ?>
                                            <span style="font-size: 14px;">&nbsp;Password is set.</span>
                                        <?php } else { ?>
                                            <span style="font-size: 14px;"> &nbsp;No password set.</span>
                                        <?php } ?>
                                    </td>
                                </tr>
                            </tbody>
                            <thead>
                                <th align="left" colspan="2">Next Membership Settings</th>
                            </thead>
                            <tbody>
                                <tr>
                                    <td width="180" valign="top"><b>Current Status:</b></td>
                                    <td><?php echo $currentStatusString; ?></td>
                                </tr>
                                <tr>
                                    <td width="180"><b>Next Membership:</b></td>
                                    <td>
                                        <select name="NextMembership">
                                            <option value="" <?php if ($NextMembership == '') { echo 'selected'; } ?>></option>
                                            <option value="1" <?php if ($NextMembership == '1') { echo 'selected'; } ?>>Pre-Register</option>
                                            <option value="2" <?php if ($NextMembership == '2') { echo 'selected'; } ?>>Graduate</option>
                                            <option value="3" <?php if ($NextMembership == '3') { echo 'selected'; } ?>>Drop Immediately</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2"><button type="submit" name="save_member">Save changes</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </form>
                </div>
                <div id="historical">
                    <?php
                        if ($history_result->num_rows) {
                            echo '<table class="general-table" style="margin: 2rem 0;">';
                                echo '<thead>';
                                    echo '<th align="left">Membership Year</th>';
                                    echo '<th align="left">Grade Level</th>';
                                    echo '<th align="left">Attendance %</th>';
                                    echo '<th align="left">Demerits</th>';
                                    echo '<th align="left">Service Hours</th>';
                                    echo '<th align="left">Stored On</th>';
                                echo '</thead>';
                                echo '<tbody>';
                                while ($row = $history_result->fetch_assoc()) {
                                    $storedOn = date("n/j/Y h:i A", strtotime($row['StoredOn']));
                                    
                                    echo "<tr>
                                        <td>{$row['MembershipYear']}</td>
                                        <td>{$row['GradeLevel']}</td>
                                        <td>{$row['Attendance']}%</td>
                                        <td>{$row['Demerits']}</td>
                                        <td>{$row['ServiceHours']} hour(s)</td>
                                        <td>{$storedOn}</td>
                                    </tr>";
                                }
                                echo '</tbody>';
                            echo '</table>';
                        } else {
                            echo "<p>No historical records found.</p>";
                        }
                    ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php } else {
    header("HTTP/1.0 404 Not Found");
    exit();
} ?>