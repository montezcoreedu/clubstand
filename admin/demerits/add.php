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

    $adminName = $_SESSION['FirstName'] . ' ' . $_SESSION['LastName'];

    if (!empty($_GET['id']) && $check_url) {
        include("../common/membercommon.php");
        
        $points_sql = "SELECT SUM(DemeritPoints) AS CumulativePoints FROM demerits WHERE MemberId = $getMemberId";
        $points_query = $conn->query($points_sql);
        $points = mysqli_fetch_assoc($points_query);

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $demeritDate = date('Y-m-d', strtotime($_POST['DemeritDate']));
            $demerit = $_POST['Demerit'];
            $demeritDescription = $_POST['DemeritDescription'];
            $demeritPoints = $_POST['DemeritPoints'];
            $sendEmail = isset($_POST['SendEmail']);
        
            $stmt = $conn->prepare("INSERT INTO demerits (
                MemberId, IssuedBy, DemeritDate, Demerit, DemeritDescription, DemeritPoints
            ) VALUES (?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param("issssi", $getMemberId, $adminName, $demeritDate, $demerit, $demeritDescription, $demeritPoints);
        
            if ($stmt->execute()) {
                $_SESSION['success'] = "Demerit successfully issued.";
        
                if ($sendEmail) {
                    $ReceivedDate = date('n/j/Y', strtotime($_POST['DemeritDate']));
                    $PointsAdded = $points['CumulativePoints'] + $demeritPoints;
        
                    $mail->addAddress($EmailAddress);
                    $mail->AddCC($PrimaryContactEmail);
                    $mail->AddCC('smalleys@bcsdschools.net');
                    $mail->AddCC('lampkinl@bcsdschools.net');
                    $mail->addReplyTo('smalleys@bcsdschools.net');
                    $mail->addReplyTo('lampkinl@bcsdschools.net');
                    $mail->addReplyTo('montezbhsfbla@gmail.com');
                    $mail->isHTML(true);
                    $mail->Subject = "$FirstName $LastName - FBLA Demerit Issued";
                    $mail->Body = '
                        <table align="center" border="0" cellpadding="3" cellspacing="1" style="font-family: Times New Roman, Times, serif; font-size: 16px; width: 100%; max-width: 720px;">
                            <tbody>
                                <tr>
                                    <td>
                                        <p>
                                            Dear '.$FirstName.' '.$LastName.',
                                            <br><br>
                                            You have recently received a demerit. Below, you will find detailed information regarding this demerit. To maintain your active membership, please ensure that you are adhering to our regulations. As it stands, this demerit has been recorded as <b style="color: rgb(186, 18, 18);">'.$PointsAdded.' demerit point(s)</b>. Please be aware that accumulating a total of 6 demerit points may result in a 60-day suspension from FBLA events and activities.
                                        </p>
                                        <hr><br>
                                        <table border="1" cellpadding="2" cellspacing="1" style="width: 100%;">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Demerit</th>
                                                    <th align="left">Description</th>
                                                    <th>Points</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td align="center">'.$ReceivedDate.'</td>
                                                    <td align="center">'.$demerit.'</td>
                                                    <td>'.$demeritDescription.'</td>
                                                    <td align="center">'.$demeritPoints.'</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <br><hr>
                                        If you identify any errors or have any questions or concerns, please reach out to <a href="mailto:montezbhsfbla@gmail.com">montezbhsfbla@gmail.com</a>. For additional inquiries, you may contact <a href="mailto:SmalleyS@bcsdschools.net">SmalleyS@bcsdschools.net</a>. Additionally, you can monitor your membership portal for updates as they arise.
                                        <br><br>
                                        Thanks,<br>
                                        ' . $chapter['ChapterName'] . '
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    ';
        
                    if ($mail->send()) {
                        $_SESSION['successMessage'] .= "<div class='message success'>Demerit recorded and email successfully sent!</div>";
                    } else {
                        $_SESSION['errorMessage'] = "<div class='message error'>Demerit saved, but email could not be sent.</div>";
                    }
                }
        
                header("Location: ../members/demerits.php?id=$getMemberId");
                exit();
            } else {
                $_SESSION['errorMessage'] = "<div class='message error'>Error issuing demerit: " . $stmt->error .'</div>';
                header("Location: ../members/demerits.php?id=$getMemberId");
                exit();
            }
        }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $LastName; ?>, <?php echo $FirstName; ?><?php echo !empty($Suffix) ? ' ' . $Suffix : ''; ?> - Add Demerit</title>
    <?php include("../common/head.php"); ?>
    <script>
        $( function() {
            $( "#DemeritDate" ).datepicker({
                dateFormat: 'm/d/yy'
            });
            $('#DemeritDate').datepicker('setDate', 'today');
        } );

        function loadDescriptions(categoryName) {
            const descSelect = document.getElementById("DescriptionSelect");
            descSelect.innerHTML = "<option value=''>Loading...</option>";

            if (!categoryName) {
                descSelect.innerHTML = "<option value=''></option>";
                return;
            }

            fetch("get_descriptions.php?CategoryName=" + categoryName)
                .then(res => res.json())
                .then(data => {
                    descSelect.innerHTML = "<option value=''></option>";
                    data.forEach(item => {
                        const opt = document.createElement("option");
                        opt.value = item.Description;
                        opt.textContent = item.Description;
                        descSelect.appendChild(opt);
                    });
                })
                .catch(() => {
                    descSelect.innerHTML = "<option value=''>Error loading</option>";
                });
        }
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
                    <span>Add Demerit</span>
                </li>
            </ul>
            <h2>Add Demerit</h2>
            <form method="post">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <td width="180"><b>Issued by:</b></td>
                            <td><?php echo $adminName; ?></td>
                        </tr>
                        <tr>
                            <td width="180"><b>Date:</b></td>
                            <td><input type="text" id="DemeritDate" name="DemeritDate"></td>
                        </tr>
                        <tr>
                            <td width="180"><b>Demerit:</b></td>
                            <td>
                                <select name="Demerit" id="DemeritSelect" onchange="loadDescriptions(this.value)" required>
                                <option value=""></option>
                                <?php
                                $cats = $conn->query("SELECT CategoryId, CategoryName FROM demerit_categories ORDER BY CategoryName asc");
                                while ($row = $cats->fetch_assoc()) {
                                    echo "<option value='{$row['CategoryName']}'>{$row['CategoryName']}</option>";
                                }
                                ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td width="180"><b>Description:</b></td>
                            <td>
                                <select name="DemeritDescription" id="DescriptionSelect" required>
                                    <option value=""></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td width="180"><b>Points:</b></td>
                            <td><input type="number" name="DemeritPoints" min="0" value="1"></td>
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