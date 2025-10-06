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

    if (!empty($_GET['pid']) && !empty($_GET['id']) && $check_url) {
        include("../common/membercommon.php");

        $probationId = isset($_GET['pid']) ? $_GET['pid'] : 0;

        if (!empty($probationId)) {
            $stmt = $conn->prepare("SELECT * FROM probation WHERE ProbationId = ?");
            $stmt->bind_param("i", $probationId);
            $stmt->execute();
            $probationResult = $stmt->get_result();
            $probationData = $probationResult->fetch_assoc();
            $stmt->close();

            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                $extendedEndDate = date('Y-m-d', strtotime($_POST['ExtendedDate']));
            
                $stmt = $conn->prepare("UPDATE probation SET EndDate = ? WHERE ProbationId = ?");
    
                if ($stmt) {
                    $stmt->bind_param("si", $extendedEndDate, $probationId);
            
                    if ($stmt->execute()) {
                        $_SESSION['successMessage'] = "<div class='message success'>Probation period extended successfully recorded!</div>";
            
                        $sendEmail = isset($_POST['SendEmail']) ? true : false;
                        if ($sendEmail) {
                            $ExtendedDate = date('n/j/Y', strtotime($_POST['ExtendedDate']));
            
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
                                                    Your 60-day probation period has been extended to ' . $ExtendedDate . '.
                                                </p>
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