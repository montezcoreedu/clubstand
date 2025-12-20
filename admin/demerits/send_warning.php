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

        $emailHtml = "
            <table align='center' style='font-family: Times New Roman; font-size: 16px; max-width: 720px;'>
                <tr>
                    <td style='padding:20px;'>
                        <p>
                            Dear {$FirstName} {$LastName},<br><br>
                            Our records indicate that you have accumulated
                            <b>at least five demerit points</b>.
                            Please be advised that receiving one or more
                            additional demerit points will result in a
                            <b>60-day probation period</b>.
                        </p>

                        <p>
                            We encourage you to review the demerit system
                            outlined in your yellow folder.
                        </p>

                        <hr>

                        <p>
                            If you would like a copy of your demerit records,
                            please contact
                            <a href='mailto:anastasiabhsfbla@gmail.com'>
                                anastasiabhsfbla@gmail.com
                            </a>.
                        </p>

                        <p>
                            For questions or concerns, contact
                            <a href='mailto:SmalleyS@bcsdschools.net'>
                                SmalleyS@bcsdschools.net
                            </a>.
                            You may also monitor your membership portal
                            for updates.
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
                'subject' => "{$FirstName} {$LastName} - FBLA Probation Warning",
                'html' => $emailHtml
            ]);

            $_SESSION['successMessage'] =
                "<div class='message success'>Message sent successfully!</div>";

        } catch (Exception $e) {
            $_SESSION['errorMessage'] =
                "<div class='message error'>Message could not be sent.</div>";
        }

        header("Location: ../members/demerits.php?id=$getMemberId");
        exit();
    } else {
        header("HTTP/1.0 404 Not Found");
        exit();
    }