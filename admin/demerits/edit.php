<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/checkmemberurl.php");
    include("../common/permissions.php");

    if (!in_array("Demerits", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    if (!empty($_GET['did']) && !empty($_GET['id']) && $check_url) {
        include("../common/membercommon.php");

        $demeritId = isset($_GET['did']) ? $_GET['did'] : 0;

        if (!empty($demeritId)) {
            $stmt = $conn->prepare("SELECT * FROM demerits WHERE DemeritId = ?");
            $stmt->bind_param("i", $demeritId);
            $stmt->execute();
            $demeritResult = $stmt->get_result();
            $demeritData = $demeritResult->fetch_assoc();
            $stmt->close();

            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                $demeritDate = date('Y-m-d', strtotime($_POST['DemeritDate']));
                $demerit = trim($_POST['Demerit']);
                $demeritDescription = trim($_POST['DemeritDescription']);
                $demeritPoints = mysqli_real_escape_string($conn, $_POST['DemeritPoints']);
            
                $stmt = $conn->prepare("UPDATE demerits SET DemeritDate = ?, Demerit = ?, DemeritDescription = ?, DemeritPoints = ? WHERE DemeritId = ?");
                $stmt->bind_param("sssii", $demeritDate, $demerit, $demeritDescription, $demeritPoints, $demeritId);
    
                if ($stmt->execute()) {
                    $_SESSION['successMessage'] = "<div class='message success'>Demerit updated successfully!</div>";
                } else {
                    $_SESSION['errorMessage'] = "<div class='message error'>Something went wrong. Please try again.</div>";
                }
    
                $stmt->close();
                header("Location: ../members/demerits.php?id=" . urlencode($getMemberId) . "");
                exit();
            }
        }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $LastName; ?>, <?php echo $FirstName; ?><?php echo !empty($Suffix) ? ' ' . $Suffix : ''; ?> - Edit Demerit</title>
    <?php include("../common/head.php"); ?>
    <script>
        $( function() {
            $( "#DemeritDate" ).datepicker({
                dateFormat: 'm/d/yy'
            });
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
                    <span>Edit Demerit</span>
                </li>
            </ul>
            <h2>Edit Demerit</h2>
            <form method="post">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <td width="180"><b>Issued by:</b></td>
                            <td><?php echo $demeritData['IssuedBy']; ?></td>
                        </tr>
                        <tr>
                            <td width="180"><b>Date:</b></td>
                            <td><input type="text" id="DemeritDate" name="DemeritDate" value="<?php echo $DemeritDate = date("n/j/Y", strtotime($demeritData['DemeritDate'])); ?>"></td>
                        </tr>
                        <tr>
                            <td width="180"><b>Demerit:</b></td>
                            <td>
                                <select name="Demerit">
                                    <option value="Academic" <?php if ($demeritData['Demerit'] == 'Academic') echo 'selected'; ?>>Academic</option>
                                    <option value="Attendance" <?php if ($demeritData['Demerit'] == 'Attendance') echo 'selected'; ?>>Attendance</option>
                                    <option value="Discipline" <?php if ($demeritData['Demerit'] == 'Discipline') echo 'selected'; ?>>Discipline</option>
                                    <option value="Miscellaneous" <?php if ($demeritData['Demerit'] == 'Miscellaneous') echo 'selected'; ?>>Miscellaneous</option>
                                    <option value="Officer Incident" <?php if ($demeritData['Demerit'] == 'Officer Incident') echo 'selected'; ?>>Officer Incident</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td width="180"><b>Description:</b></td>
                            <td>
                                <select name="DemeritDescription">
                                    <optgroup label="MEMBERS ONLY">
                                        <optgroup label="Academic">
                                            <option value="Academic integrity." <?php if ($demeritData['DemeritDescription'] == 'Academic integrity.') echo 'selected'; ?>>Academic integrity.</option>
                                            <option value="Failing academic course." <?php if ($demeritData['DemeritDescription'] == 'Failing academic course.') echo 'selected'; ?>>Failing academic course.</option>
                                            <option value="Failing academic course due to FA." <?php if ($demeritData['DemeritDescription'] == 'Failing academic course due to FA.') echo 'selected'; ?>>Failing academic course due to FA.</option>
                                            <option value="No grade submission." <?php if ($demeritData['DemeritDescription'] == 'No grade submission.') echo 'selected'; ?>>No grade submission.</option>
                                        </optgroup>
                                        <optgroup label="Attendance">
                                            <option value="Competition practice absence." <?php if ($demeritData['DemeritDescription'] == 'Competition practice absence.') echo 'selected'; ?>>Competition practice absence.</option>
                                            <option value="Unexcused absence in event." <?php if ($demeritData['DemeritDescription'] == 'Unexcused absence in event.') echo 'selected'; ?>>Unexcused absence in event.</option>
                                            <option value="Unexcused absence in meeting." <?php if ($demeritData['DemeritDescription'] == 'Unexcused absence in meeting.') echo 'selected'; ?>>Unexcused absence in meeting.</option>
                                            <option value="Unexcused tardy meeting." <?php if ($demeritData['DemeritDescription'] == 'Unexcused tardy meeting.') echo 'selected'; ?>>Unexcused tardy meeting.</option>
                                        </optgroup>
                                        <optgroup label="Discipline">
                                            <option value="ISS assigned." <?php if ($demeritData['DemeritDescription'] == 'ISS assigned.') echo 'selected'; ?>>ISS assigned.</option>
                                            <option value="OSS assigned." <?php if ($demeritData['DemeritDescription'] == 'OSS assigned.') echo 'selected'; ?>>OSS assigned.</option>
                                        </optgroup>
                                        <optgroup label="Miscellaneous">
                                            <option value="Failure to uphold responsibilities." <?php if ($demeritData['DemeritDescription'] == 'Failure to uphold responsibilities.') echo 'selected'; ?>>Failure to uphold responsibilities.</option>
                                            <option value="Lack of accountability." <?php if ($demeritData['DemeritDescription'] == 'Lack of accountability.') echo 'selected'; ?>>Lack of accountability.</option>
                                            <option value="No participation in dress up day." <?php if ($demeritData['DemeritDescription'] == 'No participation in dress up day.') echo 'selected'; ?>>No participation in dress up day.</option>
                                        </optgroup>
                                    </optgroup>
                                    <optgroup label="OFFICERS ONLY">
                                        <option value="Failure to complete task." <?php if ($demeritData['DemeritDescription'] == 'Failure to complete task.') echo 'selected'; ?>>Failure to complete task.</option>
                                    </optgroup>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td width="180"><b>Points:</b></td>
                            <td><input type="number" name="DemeritPoints" min="0" max="5" value="<?php echo $demeritData['DemeritPoints']; ?>"></td>
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