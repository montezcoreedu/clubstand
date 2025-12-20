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

    if (!empty($_GET['pid']) && !empty($_GET['id']) && $check_url) {
        include("../common/membercommon.php");

        $probationId = (int) $_GET['pid'];

        $stmt = $conn->prepare("SELECT * FROM probation WHERE ProbationId = ?");
        $stmt->bind_param("i", $probationId);
        $stmt->execute();
        $probationResult = $stmt->get_result();
        $probationData = $probationResult->fetch_assoc();
        $stmt->close();

        if (!$probationData) {
            $_SESSION['errorMessage'] =
                "<div class='message error'>Probation record not found.</div>";
            header("Location: ../members/demerits.php?id=$getMemberId");
            exit();
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $extendedEndDate = date('Y-m-d', strtotime($_POST['ExtendedDate']));

            $stmt = $conn->prepare(
                "UPDATE probation SET EndDate = ? WHERE ProbationId = ?"
            );
            $stmt->bind_param("si", $extendedEndDate, $probationId);

            if ($stmt->execute()) {
                $_SESSION['successMessage'] =
                    "<div class='message success'>Probation period extended successfully recorded!</div>";

                $sendEmail = isset($_POST['SendEmail']);

                if ($sendEmail) {
                    $ExtendedDateFormatted =
                        date('n/j/Y', strtotime($extendedEndDate));

                    $emailHtml = "
                        <table align='center' style='font-family: Times New Roman; font-size: 16px; max-width: 720px;'>
                            <tr>
                                <td style='padding:20px;'>
                                    <p>
                                        Dear {$FirstName} {$LastName},<br><br>
                                        Your <b>60-day probation period</b>
                                        has been extended to
                                        <b>{$ExtendedDateFormatted}</b>.
                                    </p>

                                    <hr>

                                    <p>
                                        For additional inquiries, please contact
                                        <a href='mailto:SmalleyS@bcsdschools.net'>
                                            SmalleyS@bcsdschools.net
                                        </a>.
                                    </p>

                                    <p>
                                        You may also monitor your membership
                                        portal for updates as they arise.
                                    </p>

                                    <p>
                                        Thanks,<br>
                                        {$chapter['ChapterName']}
                                    </p>
                                </td>
                            </tr>
                        </table>
                    ";

                    try {
                        $mg->messages()->send($mailgunDomain, [
                            'from' =>
                                "{$chapter['ChapterName']} ByLaw Committee <no-reply@corecommunication.org>",
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
                            'subject' =>
                                "{$FirstName} {$LastName} - FBLA Probation Update",
                            'html' => $emailHtml
                        ]);
                    } catch (Exception $e) {
                        $_SESSION['errorMessage'] =
                            "<div class='message error'>Probation updated, but email could not be sent.</div>";
                    }
                }

            } else {
                $_SESSION['errorMessage'] =
                    "<div class='message error'>Something went wrong. Please try again.</div>";
            }

            $stmt->close();
            header("Location: ../members/demerits.php?id=$getMemberId");
            exit();
        }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $LastName; ?>, <?php echo $FirstName; ?><?php echo !empty($Suffix) ? ' ' . $Suffix : ''; ?> - Extend Probation</title>
    <?php include("../common/head.php"); ?>
    <script>
        $( function() {
            $( "#ExtendedDate" ).datepicker({
                dateFormat: 'm/d/yy'
            });
            $('#ExtendedDate').datepicker('setDate', 'today');
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
                    <a href="../members/demerits.php?id=<?php echo $getMemberId; ?>">Demerits</a>
                </li>
                <li>
                    <span>Extend Current Probation</span>
                </li>
            </ul>
            <h2>Extend Current Probation</h2>
            <form method="post">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <td width="220"><b>Original End Date:</b></td>
                            <td><?php echo $EndDate = date("n/j/Y", strtotime($probationData['EndDate'])); ?></td>
                        </tr>
                        <tr>
                            <td width="220"><b>Extended End Date:</b></td>
                            <td><input type="text" id="ExtendedDate" name="ExtendedDate"></td>
                        </tr>
                        <tr>
                            <td width="220"><b>Send Email:</b></td>
                            <td><input type="checkbox" name="SendEmail" checked></td>
                        </tr>
                        <tr>
                            <td colspan="2"><button type="submit">Save changes</button></td>
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