<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/chapter_settings.php");
    include("../common/checkmemberurl.php");
    include("../common/permissions.php");

    if (!in_array("Demerits", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    require '../../PHPMailer/src/Exception.php';
    require '../../PHPMailer/src/PHPMailer.php';
    require '../../PHPMailer/src/SMTP.php';

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'montezbhsfbla@gmail.com';
    $mail->Password = 'xswpfxfdlndcloje';
    $mail->SMTPSecure = 'ssl';
    $mail->Port = 465;
    $mail->setFrom('montezbhsfbla@gmail.com', '' . $chapter['ChapterName'] . ' ByLaw Committee');

    if (!empty($_GET['id']) && $check_url) {
        include("../common/membercommon.php");

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $issuedBy = $_SESSION['FirstName'] . " " . $_SESSION['LastName'];
            $probationLevel = $_POST['ProbationLevel'];
            $startDate = date('Y-m-d', strtotime($_POST['StartDate']));
            $endDate = date('Y-m-d', strtotime($_POST['EndDate']));
            $probationReason = $_POST['ProbationReason'];
            $conditionsForRemoval = isset($_POST['ProbationCondition']) ? json_encode($_POST['ProbationCondition']) : "[]";
        
            $sql = "INSERT INTO probation (
                        MemberId, IssuedBy, ProbationLevel, StartDate, EndDate, 
                        ProbationReason, ConditionsForRemoval, ConditionsMet
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, '')";
        
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("issssss", $getMemberId, $issuedBy, $probationLevel, $startDate, $endDate, $probationReason, $conditionsForRemoval);
        
                if ($stmt->execute()) {
                    $updateStatusSql = "UPDATE members SET MemberStatus = 2 WHERE MemberId = ?";
                    if ($updateStmt = $conn->prepare($updateStatusSql)) {
                        $updateStmt->bind_param("i", $getMemberId);
                        $updateStmt->execute();
                        $updateStmt->close();
                    }
        
                    $_SESSION['successMessage'] = "<div class='message success'>Probation successfully recorded!</div>";
        
                    $sendEmail = isset($_POST['SendEmail']) ? true : false;
                    if ($sendEmail) {
                        $ProbationStart = date('n/j/Y', strtotime($_POST['StartDate']));
                        $ProbationEnd = date('n/j/Y', strtotime($_POST['EndDate']));
        
                        $conditionsList = "";
                        $conditionsArray = $_POST['ProbationCondition'] ?? [];
                        if (is_array($conditionsArray) && count(array_filter($conditionsArray))) {
                            $conditionsList = "<ul>";
                            foreach ($conditionsArray as $condition) {
                                $conditionsList .= "<li>" . htmlspecialchars($condition) . "</li>";
                            }
                            $conditionsList .= "</ul>";
                        } else {
                            $conditionsList = "<p>No conditions set for removal.</p>";
                        }
        
                        $mail->addAddress($EmailAddress);
                        $mail->AddCC($PrimaryContactEmail);
                        $mail->AddCC('smalleys@bcsdschools.net');
                        $mail->AddCC('lampkinl@bcsdschools.net');
                        $mail->addReplyTo('smalleys@bcsdschools.net');
                        $mail->addReplyTo('lampkinl@bcsdschools.net');
                        $mail->addReplyTo('montezbhsfbla@gmail.com');
                        $mail->isHTML(true);
                        $mail->Subject = $FirstName . ' ' . $LastName . ' - FBLA Probation Report';
                        $mail->Body = '
                            <table align="center" border="0" cellpadding="3" cellspacing="1" style="font-family: Times New Roman, Times, serif; font-size: 16px; width: 100%; max-width: 720px;">
                                <tbody>
                                    <tr>
                                        <td>
                                            <p>
                                                Dear ' . $FirstName . ' ' . $LastName . ',
                                                <br><br>
                                                You have been placed on a 60-day probationary period due to the accumulation of six demerits. 
                                                This probation period will commence on ' . $ProbationStart . ' and conclude on ' . $ProbationEnd . '. 
                                                As a member, you are still required to attend chapter meetings and engage in community service and fundraising activities; 
                                                however, please be advised that you will not be permitted to participate in any activities or celebrations during this time. 
                                                The probation reason and conditions for removal are as follows:
                                            </p>
                                            <b>Probation reason:</b> ' . $probationReason . '
                                            <br>
                                            ' . $conditionsList . '
                                            <hr>
                                            For additional inquiries, you may contact <a href="mailto:SmalleyS@bcsdschools.net">SmalleyS@bcsdschools.net</a>. Additionally, you can monitor your membership portal for updates as they arise.
                                            <br>
                                            <br>
                                            Thanks,
                                            <br>
                                            ' . $chapter['ChapterName'] . '
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        ';
                        $mail->send();
                    }
                } else {
                    $_SESSION['errorMessage'] = "<div class='message error'>Something went wrong. Please try again.</div>";
                }
                $stmt->close();
            } else {
                $_SESSION['errorMessage'] = "<div class='message error'>Something went wrong. Please try again.</div>";
            }
        
            header("Location: ../members/demerits.php?id=$getMemberId");
            exit();
        }        
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $LastName; ?>, <?php echo $FirstName; ?><?php echo !empty($Suffix) ? ' ' . $Suffix : ''; ?> - Assign Probation</title>
    <?php include("../common/head.php"); ?>
    <script>
        $(document).ready(function() {
            $(".ProbationDate").datepicker({
                dateFormat: 'm/d/yy'
            });

            $("input[name='StartDate']").on('change', function() {
                var startDate = new Date($(this).val());
                var endDate = new Date(startDate);
                endDate.setDate(startDate.getDate() + 60);

                var month = endDate.getMonth() + 1;
                var day = endDate.getDate();
                var year = endDate.getFullYear();
                var formattedEndDate = month + '/' + day + '/' + year;

                $("input[name='EndDate']").val(formattedEndDate);
            });
        });
    </script>
</head>
<body>
    <?php include("../common/loading.php"); ?>
    <?php include("../common/top-navbar.php"); ?>
    <?php include("../common/memberhead.php"); ?>
    <div id="content">
        <?php include("../common/member-sidebar.php"); ?>
        <div id="main-content-wrapper">
            <ul class="breadcrumbs">
                <li>
                    <a href="../members/demerits.php?id=<?php echo $getMemberId; ?>">Demerits</a>
                </li>
                <li>
                    <span>Assign Probation</span>
                </li>
            </ul>
            <h2>Assign Probation</h2>
            <form method="post">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <td width="220"><b>Issued by:</b></td>
                            <td><?php echo $_SESSION['FirstName'] . " " . $_SESSION['LastName']; ?></td>
                        </tr>
                        <tr>
                            <td width="220"><b>Level:</b></td>
                            <td>
                                <select name="ProbationLevel">
                                    <option value="Warning">Warning</option>
                                    <option value="Strict">Strict</option>
                                    <option value="Final">Final</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td width="220"><b>Start Date:</b></td>
                            <td><input type="text" class="ProbationDate" name="StartDate" value="<?php echo date('n/j/Y'); ?>"></td>
                        </tr>
                        <tr>
                            <td width="220"><b>End Date:</b></td>
                            <td><input type="text" class="ProbationDate" name="EndDate" value="<?php echo date('n/j/Y', strtotime(date('Y-m-d'). ' + 60 days')); ?>"></td>
                        </tr>
                        <tr>
                            <td width="220" valign="top"><b>Reason:</b></td>
                            <td><textarea name="ProbationReason" cols="40" rows="3" required></textarea></td>
                        </tr>
                        <tr>
                            <td width="220" valign="top"><b>Conditions for Removal:</b></td>
                            <td>
                                <label><input type="checkbox" name="ProbationCondition[]" value="Attend 80% of meetings"> Attend 80% of meetings</label><br>
                                <label><input type="checkbox" name="ProbationCondition[]" value="Complete at least 3 service hours"> Complete at least 3 service hours</label><br>
                                <label><input type="checkbox" name="ProbationCondition[]" value="No new demerits"> No new demerits</label><br>
                                <label><input type="checkbox" name="ProbationCondition[]" value="End of term grade check approval"> End of term grade check approval</label><br>
                            </td>
                        </tr>
                        <tr>
                            <td width="220"><b>Send Email:</b></td>
                            <td><input type="checkbox" name="SendEmail" checked></td>
                        </tr>
                        <tr>
                            <td colspan="2"><button type="submit">Submit</button></td>
                        </tr>
                    </tbody>
                </table>
            </form>
        </div>
    </div>
</body>
</html>
<?php } else {
    header("HTTP/1.0 404 Not Found");
    exit();
} ?>