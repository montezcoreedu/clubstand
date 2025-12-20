<?php
    require __DIR__ . '/../../vendor/autoload.php';

    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/chapter_settings.php");
    include("../common/checkmemberurl.php");
    include("../common/permissions.php");

    use Mailgun\Mailgun;

    if (!in_array("Demerits", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    $mg = Mailgun::create(getenv('MAILGUN_API_KEY'));
    $mailgunDomain = 'corecommunication.org';

    if (!empty($_GET['id']) && $check_url) {
        include("../common/membercommon.php");

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $issuedBy = $_SESSION['FirstName'] . " " . $_SESSION['LastName'];
            $probationLevel = $_POST['ProbationLevel'];
            $startDate = date('Y-m-d', strtotime($_POST['StartDate']));
            $endDate = date('Y-m-d', strtotime($_POST['EndDate']));
            $probationReason = $_POST['ProbationReason'];
            $conditionsForRemoval = isset($_POST['ProbationCondition'])
                ? json_encode($_POST['ProbationCondition'])
                : "[]";

            $sql = "
                INSERT INTO probation (
                    MemberId, IssuedBy, ProbationLevel, StartDate, EndDate,
                    ProbationReason, ConditionsForRemoval, ConditionsMet
                ) VALUES (?, ?, ?, ?, ?, ?, ?, '')
            ";

            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param(
                    "issssss",
                    $getMemberId,
                    $issuedBy,
                    $probationLevel,
                    $startDate,
                    $endDate,
                    $probationReason,
                    $conditionsForRemoval
                );

                if ($stmt->execute()) {
                    $updateStmt = $conn->prepare("
                        UPDATE members 
                        SET MemberStatus = 2 
                        WHERE MemberId = ?
                    ");
                    $updateStmt->bind_param("i", $getMemberId);
                    $updateStmt->execute();
                    $updateStmt->close();

                    $_SESSION['successMessage'] =
                        "<div class='message success'>Probation successfully recorded!</div>";

                    if (isset($_POST['SendEmail'])) {
                        $ProbationStart = date('n/j/Y', strtotime($_POST['StartDate']));
                        $ProbationEnd = date('n/j/Y', strtotime($_POST['EndDate']));

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

                        $emailHtml = "
                            <table align='center' style='font-family: Times New Roman; font-size: 16px; max-width: 720px;'>
                                <tr>
                                    <td style='padding:20px;'>
                                        <p>
                                            Dear {$FirstName} {$LastName},<br><br>
                                            You have been placed on a <b>60-day probationary period</b>
                                            due to the accumulation of six demerits.
                                            This probation period will begin on
                                            <b>{$ProbationStart}</b> and conclude on
                                            <b>{$ProbationEnd}</b>.
                                        </p>

                                        <p>
                                            During this time, you are still required to attend meetings
                                            and participate in service and fundraising activities.
                                            However, you may not participate in chapter activities or celebrations.
                                        </p>

                                        <p><b>Probation reason:</b> {$probationReason}</p>

                                        {$conditionsList}

                                        <hr>

                                        <p>
                                            For questions, contact
                                            <a href='mailto:SmalleyS@bcsdschools.net'>
                                                SmalleyS@bcsdschools.net
                                            </a>.
                                            You may also monitor your membership portal for updates.
                                        </p>

                                        <p>
                                            Thanks,<br>
                                            {$chapter['ChapterName']}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        ";

                        $mg->messages()->send($mailgunDomain, [
                            'from' => "{$chapter['ChapterName']} ByLaw Committee <no-reply@corecommunication.org>",
                            'to' => $EmailAddress,
                            'cc' => [
                                $PrimaryContactEmail,
                                'smalleys@bcsdschools.net',
                                'lampkinl@bcsdschools.net'
                            ],
                            'reply-to' => [
                                'smalleys@bcsdschools.net',
                                'lampkinl@bcsdschools.net',
                                'anastasiabhsfbla@gmail.com'
                            ],
                            'subject' => "{$FirstName} {$LastName} - FBLA Probation Report",
                            'html' => $emailHtml
                        ]);
                    }

                } else {
                    $_SESSION['errorMessage'] =
                        "<div class='message error'>Something went wrong. Please try again.</div>";
                }

                $stmt->close();
            } else {
                $_SESSION['errorMessage'] =
                    "<div class='message error'>Something went wrong. Please try again.</div>";
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