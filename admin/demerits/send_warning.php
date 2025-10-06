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

        $mail->addAddress($EmailAddress);
        $mail->AddCC($PrimaryContactEmail);
        $mail->AddCC('smalleys@bcsdschools.net');
        $mail->AddCC('lampkinl@bcsdschools.net');
        $mail->addReplyTo('smalleys@bcsdschools.net');
        $mail->addReplyTo('lampkinl@bcsdschools.net');
        $mail->addReplyTo('montezbhsfbla@gmail.com');
        $mail->isHTML(true);
        $mail->Subject = ''.$FirstName.' '.$LastName.' - FBLA Probation Warning';
        $mail->Body = '
            <table align="center" border="0" cellpadding="3" cellspacing="1" style="font-family: Times New Roman, Times, serif; font-size: 16px; width: 100%; max-width: 720px;">
                <tbody>
                    <tbody>
                        <tr>
                            <td>
                                <p>
                                    Dear '.$FirstName.' '.$LastName.',
                                    <br>
                                    <br>
                                    Our records indicate that you have accumulated at least five demerit points. Please be advised that receiving one or more additional demerit points will result in a 60-day probation period. We encourage you to review the demerit system outlined in your yellow folder.
                                </p>
                                <hr>
                                If you would like a copy of your demerit records, please reach out to <a href="mailto:montezbhsfbla@gmail.com">montezbhsfbla@gmail.com</a>. For any questions or concerns, do not hesitate to contact <a href="mailto:SmalleyS@bcsdschools.net">SmalleyS@bcsdschools.net</a>. Additionally, you can monitor your membership portal for updates as they arise.
                                <br>
                                <br>
                                Thanks,
                                <br>
                                ' . $chapter['ChapterName'] . '
                            </td>
                        </tr>
                    </tbody>
                </tbody>
            </table>
        ';

        if ($mail->send()) {
            $_SESSION['successMessage'] = "<div class='message success'>Message sent successfully!</div>";
        } else {
            $_SESSION['errorMessage'] = "<div class='message error'>Message could not be sent. Error: {$mail->ErrorInfo}</div>";
            var_dump($mail->ErrorInfo);
        }

        header("Location: ../members/demerits.php?id=$getMemberId");
        exit();
    } else {
        header("HTTP/1.0 404 Not Found");
        exit();
    }