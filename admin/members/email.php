<?php
    require __DIR__ . '/../../vendor/autoload.php';

    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/chapter_settings.php");
    include("../common/checkmemberurl.php");
    include("../common/permissions.php");

    use Mailgun\Mailgun;

    if (!in_array("Member Communication", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    $mg = Mailgun::create(getenv('MAILGUN_API_KEY'));
    $mailgunDomain = 'corecommunication.org';

    if (!empty($_GET['id']) && $check_url) {
        include("../common/membercommon.php");

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $emails   = $_POST['Emails'] ?? [];
            $emailsCC = $_POST['EmailsCC'] ?? [];
            $subject  = trim($_POST['Subject']);
            $message  = $_POST['Message'];

            if (empty($emails)) {
                $_SESSION['errorMessage'] =
                    "<div class='message error'>No recipients selected.</div>";
                header("Location: index.php?id=$getMemberId");
                exit();
            }

            try {
                $mg->messages()->send($mailgunDomain, [
                    'from' =>
                        "{$chapter['ChapterName']} Communications <no-reply@corecommunication.org>",
                    'to' => $emails,
                    'cc' => $emailsCC,
                    'reply-to' => [
                        'smalleys@bcsdschools.net',
                        'lampkinl@bcsdschools.net',
                        'anastasiabhsfbla@gmail.com'
                    ],
                    'subject' => $subject,
                    'html' => "
                        <table align='center'
                            style='font-family: Times New Roman; font-size: 16px; max-width: 720px;'>
                            <tr>
                                <td style='padding:20px;'>
                                    {$message}
                                    <hr>
                                    <p>
                                        Thanks,<br>
                                        {$chapter['ChapterName']}
                                    </p>
                                </td>
                            </tr>
                        </table>
                    "
                ]);

                $_SESSION['successMessage'] =
                    "<div class='message success'>Message sent successfully!</div>";
            } catch (Exception $e) {
                $_SESSION['errorMessage'] =
                    "<div class='message error'>Message could not be sent.</div>";
            }

            header("Location: index.php?id=$getMemberId");
            exit();
        }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $LastName; ?>, <?php echo $FirstName; ?><?php echo !empty($Suffix) ? ' ' . $Suffix : ''; ?></title>
    <?php include("../common/head.php"); ?>
    <style>
        .selectEmails {
            width: 100%;
        }
    </style>
</head>
<body>
    <?php include("../common/loading.php"); ?>
    <?php include("../common/top-navbar.php"); ?>
    <?php include("../common/memberhead.php"); ?>
    <div id="content">
        <?php include("../common/member-sidebar.php"); ?>
        <div id="main-content-wrapper">
            <h2>Email Communication</h2>
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
            <form method="post">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <td width="140"><b>To:</b></td>
                            <td>
                                <select name="Emails[]" class="selectEmails" multiple required>
                                    <option value="<?php echo $EmailAddress; ?>" selected><?php echo $EmailAddress; ?></option>
                                    <option value="<?php echo $PrimaryContactEmail; ?>"><?php echo $PrimaryContactEmail; ?></option>
                                    <?php if (!empty($SecondaryContactEmail)) { ?>
                                        <option value="<?php echo $SecondaryContactEmail; ?>"><?php echo $SecondaryContactEmail; ?></option>
                                    <?php } ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td width="140"><b>Cc:</b></td>
                            <td>
                                <select name="EmailsCC[]" class="selectEmails" multiple>
                                    <option value="anastasiabhsfbla@gmail.com">anastasiabhsfbla@gmail.com</option>
                                    <option value="smalleys@bcsdschools.net">smalleys@bcsdschools.net</option>
                                    <option value="lampkinl@bcsdschools.net">lampkinl@bcsdschools.net</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td width="140"><b>Subject:</b></td>
                            <td><input type="text" name="Subject" required style="width: 100%; max-width: 280px;"></td>
                        </tr>
                        <tr>
                            <td width="140" valign="top"><b>Message:</b></td>
                            <td><textarea name="Message" id="Message"></textarea></td>
                        </tr>
                        <tr>
                            <td colspan="2"><button type="submit">Send Message</button></td>
                        </tr>
                    </tbody>
                </table>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="../../tinymce/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        $(".selectEmails").select2({});

        tinymce.init({
            selector: 'textarea[name="Message"]', 
            menubar: false,  
            plugins: 'advlist autolink lists link image charmap print preview anchor',
            toolbar: 'undo redo | bold italic | bullist numlist outdent indent | link',
            height: 300
        });
    </script>
</body>
</html>
<?php } else {
    header("HTTP/1.0 404 Not Found");
    exit();
} ?>