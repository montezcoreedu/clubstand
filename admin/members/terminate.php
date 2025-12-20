<?php
    require __DIR__ . '/../../vendor/autoload.php';

    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/chapter_settings.php");
    include("../common/checkmemberurl.php");
    include("../common/permissions.php");

    use Mailgun\Mailgun;

    if (!in_array("Member Membership", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    $mg = Mailgun::create(getenv('MAILGUN_API_KEY'));
    $mailgunDomain = 'corecommunication.org';

    if (!empty($_GET['id']) && $check_url) {
        include("../common/membercommon.php");

        $adminName = $_SESSION['FirstName'] . ' ' . $_SESSION['LastName'];

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $terminationDate = date('Y-m-d', strtotime($_POST['TerminationDate']));
            $terminationReason = mysqli_real_escape_string($conn, $_POST['TerminationReason']);
            $sendEmail = isset($_POST['SendEmail']);

            $sql = "INSERT INTO termination (MemberId, IssuedBy, TerminationDate, TerminationReason)
                    VALUES ('$getMemberId', '$adminName', '$terminationDate', '$terminationReason')";

            if (mysqli_query($conn, $sql)) {
                $updateStatus = "UPDATE members SET MemberStatus = 3 WHERE MemberId = '$getMemberId'";
                if (mysqli_query($conn, $updateStatus)) {

                    if ($sendEmail) {
                        $RecordedDate = date('n/j/Y', strtotime($_POST['TerminationDate']));

                        $subject = "$FirstName $LastName - FBLA Termination";
                        $body = '
                            <table align="center" border="0" cellpadding="3" cellspacing="1" style="font-family: Times New Roman, Times, serif; font-size: 16px; width: 100%; max-width: 720px;">
                                <tbody>
                                    <tr>
                                        <td>
                                            <p>
                                                Dear '.$FirstName.' '.$LastName.',
                                                <br><br>
                                                Your membership in the ' . $chapter['ChapterName'] . ' chapter has been officially terminated as of '.$RecordedDate.'. As a result, you are no longer a member of our chapter. Below, you will find the reasons for your termination, and you will receive an official letter outlining this decision in paper view. Should you have any further questions or concerns, please do not hesitate to contact <a href="mailto:SmalleyS@bcsdschools.net">SmalleyS@bcsdschools.net</a>.
                                            </p>
                                            <br>
                                            <b>Termination reason: '.$terminationReason.'</b>
                                            <br>
                                            Thanks,
                                            <br>
                                            ' . $chapter['ChapterName'] . '
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        ';

                        try {
                            $mg->messages()->send($mailgunDomain, [
                                'from'    => $chapter['ChapterName'].' ByLaw Committee <no-reply@corecommunication.org>',
                                'to'      => $EmailAddress,
                                'cc'      => [$PrimaryContactEmail, 'smalleys@bcsdschools.net', 'lampkinl@bcsdschools.net',
                                'anastasiabhsfbla@gmail.com'],
                                'subject' => $subject,
                                'html'    => $body,
                            ]);
                        } catch (\Exception $e) {
                            $_SESSION['errorMessage'] = "<div class='message error'>Termination recorded, but email failed to send. Error: ".$e->getMessage()."</div>";
                            header("Location: ../members/membership.php?id=$getMemberId");
                            exit();
                        }
                    }

                    $_SESSION['successMessage'] = "<div class='message success'>Termination successfully recorded and member status updated.</div>";
                    header("Location: ../members/membership.php?id=$getMemberId");
                    exit();

                } else {
                    $_SESSION['errorMessage'] = "<div class='message error'>Termination recorded, but failed to update member status.</div>";
                    header("Location: ../members/membership.php?id=$getMemberId");
                    exit();
                }

            } else {
                $_SESSION['errorMessage'] = "<div class='message error'>Failed to record termination. Please try again.</div>";
                header("Location: ../members/membership.php?id=$getMemberId");
                exit();
            }
        }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $LastName; ?>, <?php echo $FirstName; ?><?php echo !empty($Suffix) ? ' ' . $Suffix : ''; ?></title>
    <?php include("../common/head.php"); ?>
    <script>
        $( function() {
            $( "#TerminationDate" ).datepicker({
                dateFormat: 'm/d/yy'
            });
            $('#TerminationDate').datepicker('setDate', 'today');
        } );
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
                    <a href="membership.php?id=<?php echo $getMemberId; ?>">Membership</a>
                </li>
                <li>
                    <span>Terminate Membership</span>
                </li>
            </ul>
            <h2>Terminate Membership</h2>
            <form method="post">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <td width="180"><b>Issued by:</b></td>
                            <td><?php echo $adminName; ?></td>
                        </tr>
                        <tr>
                            <td width="180"><b>Last Membership Date:</b></td>
                            <td><input type="text" id="TerminationDate" name="TerminationDate"></td>
                        </tr>
                        <tr>
                            <td width="180" valign="top"><b>Termination Reason:</b></td>
                            <td><textarea name="TerminationReason" cols="40" rows="4" required></textarea></td>
                        </tr>
                        <tr>
                            <td width="180"><b>Send Email:</b></td>
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