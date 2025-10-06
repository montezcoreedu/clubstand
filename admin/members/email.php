<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/chapter_settings.php");
    include("../common/checkmemberurl.php");
    include("../common/permissions.php");

    if (!in_array("Member Communication", $userPermissions)) {
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
    $mail->setFrom('montezbhsfbla@gmail.com', '' . $chapter['ChapterName'] . ' Communications');

    if (!empty($_GET['id']) && $check_url) {
        include("../common/membercommon.php");

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $emails = $_POST['Emails'];
            $emailsCC = isset($_POST['EmailsCC']) ? $_POST['EmailsCC'] : [];
            $subject = mysqli_real_escape_string($conn, $_POST['Subject']);
            $message = $_POST['Message'];
        
            try {
                foreach ($emails as $email) {
                    $mail->addAddress($email);
                }
                foreach ($emailsCC as $emailCC) {
                    $mail->addCC($emailCC);
                }
        
                $mail->addReplyTo('smalleys@bcsdschools.net');
                $mail->addReplyTo('lampkinl@bcsdschools.net');
                $mail->addReplyTo('montezbhsfbla@gmail.com');
        
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $message;
        
                if ($mail->send()) {
                    $_SESSION['successMessage'] = "<div class='message success'>Message sent successfully!</div>";
                } else {
                    $_SESSION['errorMessage'] = "<div class='message error'>Message could not be sent. Error: {$mail->ErrorInfo}</div>";
                    var_dump($mail->ErrorInfo);
                }
            } catch (Exception $e) {
                $_SESSION['errorMessage'] = "<div class='message error'>Message could not be sent. Mailer Error: {$mail->ErrorInfo}</div>";
                var_dump($e->getMessage());
            }
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
                                    <option value="montezbhsfbla@gmail.com">montezbhsfbla@gmail.com</option>
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