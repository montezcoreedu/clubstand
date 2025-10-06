<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/chapter_settings.php");
    include("../common/permissions.php");

    if (!in_array("Chapter Settings", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Chapter Settings</title>
    <?php include("../common/head.php"); ?>
</head>
<body>
    <?php include("../common/loading.php"); ?>
    <?php include("../common/top-navbar.php"); ?>
    <div id="wrapper">
        <ul class="breadcrumbs">
            <li>
                <a href="../home/">Member Search</a>
            </li>
            <li>
                <span>Chapter Settings</span>
            </li>
        </ul>
        <h2>Chapter Settings</h2>
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
        <form action="save_settings.php" method="post">
            <table class="form-table">
                <colgroup>
                    <col style="width: 30%;">
                    <col style="width: 70%;">
                </colgroup>
                <tbody>
                    <tr>
                        <th align="left" colspan="2">Chapter Information</th>
                    </tr>
                    <tr>
                        <td>Chapter Name:</td>
                        <td><input type="text" name="ChapterName" value="<?= $chapter['ChapterName']; ?>" required style="width: 30%;"></td>
                    </tr>
                    <tr>
                        <td>Schools:<br><small>Comma separated for multiple</small></td>
                        <td><input type="text" name="Schools" value="<?= $chapter['Schools']; ?>" required style="width: 40%;"></td>
                    </tr>
                    <tr>
                        <td>Advisor Email(s):<br><small>Comma separated for multiple</small></td>
                        <td><input type="email" name="AdvisorEmail" value="<?= $chapter['AdvisorEmail']; ?>" required style="width: 40%;"></td>
                    </tr>
                    <tr>
                        <td>General Officer Email:</td>
                        <td><input type="email" name="OfficerEmail" value="<?= $chapter['OfficerEmail']; ?>" style="width: 40%;"></td>
                    </tr>
                    <tr>
                        <td>Website:</td>
                        <td><input type="text" name="Website" value="<?= $chapter['Website']; ?>" style="width: 40%;"></td>
                    </tr>
                    <tr>
                        <th align="left" colspan="2">Reporting Parameters</th>
                    </tr>
                    <tr>
                        <td>Minimum Grade Level:<br><small>(e.g., -2 for K3, -1 for K4, 0 for K)</small></td>
                        <td>
                            <input type="number" name="MinGradeLevel" value="<?= $chapter['MinGradeLevel']; ?>" min="-2" max="99" required style="width: 20%;">
                        </td>
                    </tr>
                    <tr>
                        <td>Maximum Grade Level:<br><small>(e.g., 5, 8, 12)</small></td>
                        <td>
                            <input type="number" name="MaxGradeLevel" value="<?= $chapter['MaxGradeLevel']; ?>" min="-2" max="99" required style="width: 20%;">
                        </td>
                    </tr>
                    <tr>
                        <td>Maximum Meeting Unexcused Absences:</td>
                        <td>
                            <input type="number" name="MaxUnexcusedAbsence" value="<?= $chapter['MaxUnexcusedAbsence']; ?>" style="width: 20%;">
                        </td>
                    </tr>
                    <tr>
                        <td>Maximum Meeting Unexcused Tardies:</td>
                        <td>
                            <input type="number" name="MaxUnexcusedTardy" value="<?= $chapter['MaxUnexcusedTardy']; ?>" style="width: 20%;">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Maximum Demerit Points:<br>
                            <small>Define how many points until a probation period</small>
                        <td>
                            <input type="number" name="MaxDemerits" value="<?= $chapter['MaxDemerits']; ?>" style="width: 20%;">
                        </td>
                    </tr>
                    <tr>
                        <td>Maximum Required Service Hours:</td>
                        <td>
                            <input type="number" name="MaxServiceHours" value="<?= $chapter['MaxServiceHours']; ?>" min="1" style="width: 20%;">
                        </td>
                    </tr>
                    <tr>
                        <td>Maximum Excused Absence Requests:</td>
                        <td>
                            <input type="number" name="MaxExcuseRequests" value="<?= $chapter['MaxExcuseRequests']; ?>" style="width: 20%;">
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2"><button type="submit">Save changes</button></td>
                    </tr>
                </tbody>
            </table>
        </form>
    </div>
</body>
</html>