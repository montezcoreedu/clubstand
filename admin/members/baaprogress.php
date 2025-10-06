<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/checkmemberurl.php");
    include("../common/permissions.php");

    if (!in_array("Member BAA Progress", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    if (!empty($_GET['id']) && $check_url) {
        include("../common/membercommon.php");

        if (isset($_POST['save_baas'])) {
            if (isset($_POST['BAA_Contributor'])) {
                $BAA_Contributor = "1";
            } else {
                $BAA_Contributor = "2";
            }
            if (isset($_POST['BAA_Leader'])) {
                $BAA_Leader = "1";
            } else {
                $BAA_Leader = "2";
            }
            if (isset($_POST['BAA_Advocate'])) {
                $BAA_Advocate = "1";
            } else {
                $BAA_Advocate = "2";
            }
            if (isset($_POST['BAA_Capstone'])) {
                $BAA_Capstone = "1";
            } else {
                $BAA_Capstone = "2";
            }
    
            do {
                $baas_sql = 'UPDATE members SET ';
    
                if ($BAA_Contributor != '') $baas_sql .= '`BAA_Contributor`="'.$BAA_Contributor.'", ';
                if ($BAA_Leader != '') $baas_sql .= '`BAA_Leader`="'.$BAA_Leader.'", ';
                if ($BAA_Advocate != '') $baas_sql .= '`BAA_Advocate`="'.$BAA_Advocate.'", ';
                if ($BAA_Capstone != '') $baas_sql .= '`BAA_Capstone`="'.$BAA_Capstone.'", ';
    
                $baas_sql .= "`memberid` = $getMemberId WHERE `members`.`memberid` = $getMemberId";
                $baas_query = $conn->query($baas_sql);
    
                if ($baas_query) {
                    $_SESSION['successMessage'] = "<div class='message success'>BAA progress successfully saved!</div>";
                    header("Location: baaprogress.php?id=$getMemberId");
                    exit;
                } else {
                    $_SESSION['errorMessage'] = "<div class='message error'>Something went wrong.</div>";
                    header("Location: baaprogress.php?id=$getMemberId");
                    exit;
                }
            } while (false);
        }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $LastName; ?>, <?php echo $FirstName; ?><?php echo !empty($Suffix) ? ' ' . $Suffix : ''; ?></title>
    <?php include("../common/head.php"); ?>
</head>
<body>
    <?php include("../common/loading.php"); ?>
    <?php include("../common/top-navbar.php"); ?>
    <?php include("../common/memberhead.php"); ?>
    <div id="content">
        <?php include("../common/member-sidebar.php"); ?>
        <div id="main-content-wrapper">
            <h2>BAA Progress</h2>
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
            <?php
                $completionLevels = [
                    '1-2-2-2' => ['1/4', '25'],
                    '1-1-2-2' => ['2/4', '50'],
                    '1-1-1-2' => ['3/4', '75'],
                    '1-1-1-1' => ['4/4', '100'],
                ];
                $key = implode('-', [$BAA_Contributor, $BAA_Leader, $BAA_Advocate, $BAA_Capstone]);
                $completion = $completionLevels[$key] ?? ['0/0', '0'];
                list($courseCompletion, $percentage) = $completion;
                $isAdvanced = ($MembershipYear >= 2 && $BAA_Contributor == 2) ||
                            ($MembershipYear >= 3 && $BAA_Leader == 2) ||
                            ($MembershipYear >= 4 && $BAA_Advocate == 2);
                $progressColor = $isAdvanced ? 'rgb(144, 26, 7)' : 'rgb(40, 152, 5)';
                $progressWidths = [
                    '25' => '25%',
                    '50' => '50%',
                    '75' => '75%',
                    '100' => '100%',
                ];
                $progressWidth = $progressWidths[$percentage] ?? '0%';
            ?>
            <div class="progress-container" style="margin-bottom: 1.5rem;">
                <div class="progress-data" style="margin-bottom: 0.5rem;">
                    <span><?= $courseCompletion ?> Course Completion</span>
                    <span><?= $percentage ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="data" role="progressbar" 
                        data-aos="slide-right" 
                        data-aos-delay="200" 
                        data-aos-duration="1000" 
                        data-aos-easing="ease-in-out" 
                        style="width: <?= $progressWidth ?>; background-color: <?= $progressColor ?>;">
                    </div>
                </div>
            </div>
            <form method="post">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <td width="40" align="center"><input type="checkbox" name="BAA_Contributor" <?php echo ($BAA_Contributor == "1")?"checked":"" ?>></td>
                            <td>Contributor Level</td>
                        </tr>
                        <tr>
                            <td width="40" align="center"><input type="checkbox" name="BAA_Leader" <?php echo ($BAA_Leader == "1")?"checked":"" ?>></td>
                            <td>Leader Level</td>
                        </tr>
                        <tr>
                            <td width="40" align="center"><input type="checkbox" name="BAA_Advocate" <?php echo ($BAA_Advocate == "1")?"checked":"" ?>></td>
                            <td>Advocate Level: Understanding Ethics</td>
                        </tr>
                        <tr>
                            <td width="40" align="center"><input type="checkbox" name="BAA_Capstone" <?php echo ($BAA_Capstone == "1")?"checked":"" ?>></td>
                            <td>Capstone Project</td>
                        </tr>
                        <tr>
                            <td colspan="2"><button type="submit" name="save_baas">Save changes</button></td>
                        </tr>
                    </tbody>
                </table>
            </form>
        </div>
    </div>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init();
    </script>
</body>
</html>
<?php } else {
    header("HTTP/1.0 404 Not Found");
    exit();
} ?>