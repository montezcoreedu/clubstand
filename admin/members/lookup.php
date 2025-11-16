<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/chapter_settings.php");
    include("../common/checkmemberurl.php");
    include("../common/permissions.php");
    include("../common/grade_level_entry.php");

    if (!empty($_GET['id']) && $check_url) {
        include("../common/membercommon.php");

        if (isset($_POST['save_member'])) {
            $img_name = $_FILES['MemberPhoto']['name'];
            $img_size = $_FILES['MemberPhoto']['size'];
            $tmp_name = $_FILES['MemberPhoto']['tmp_name'];
            $error = $_FILES['MemberPhoto']['error'];
            $lastName = mysqli_real_escape_string($conn, $_POST['LastName']);
            $position = mysqli_real_escape_string($conn, $_POST['Position']);
            $firstName = mysqli_real_escape_string($conn, $_POST['FirstName']);
            $suffix = mysqli_real_escape_string($conn, $_POST['Suffix']);
            $emailAddress = mysqli_real_escape_string($conn, $_POST['EmailAddress']);
            $cellPhone = mysqli_real_escape_string($conn, $_POST['CellPhone']);
            $gradeLevel = $_POST['GradeLevel'];
            $birthdate = date('Y-m-d', strtotime($_POST['Birthdate']));
            $gender = $_POST['Gender'];
            $ethnicity = $_POST['Ethnicity'];
            $shirtSize = $_POST['ShirtSize'];
            $street = mysqli_real_escape_string($conn, $_POST['Street']);
            $city = mysqli_real_escape_string($conn, $_POST['City']);
            $state = mysqli_real_escape_string($conn, $_POST['State']);
            $zip = mysqli_real_escape_string($conn, $_POST['Zip']);

            $member_sql = 'UPDATE members SET ';
            if ($lastName != '') $member_sql .= '`LastName`="' . $lastName . '", ';
            if ($firstName != '') $member_sql .= '`FirstName`="' . $firstName . '", ';
            if ($suffix != '') $member_sql .= '`Suffix`="' . $suffix . '", ';
            if ($suffix == '') $member_sql .= '`Suffix`="", ';
            if ($position != '') $member_sql .= '`Position`="' . $position . '", ';
            if ($position == '') $member_sql .= '`Position`=NULL, ';
            if ($emailAddress != '') $member_sql .= '`EmailAddress`="' . $emailAddress . '", ';
            if ($cellPhone != '') $member_sql .= '`CellPhone`="' . $cellPhone . '", ';
            if ($gradeLevel != '') $member_sql .= '`GradeLevel`="' . $gradeLevel . '", ';
            if ($birthdate != '') $member_sql .= '`Birthdate`="' . $birthdate . '", ';
            if ($gender != '') $member_sql .= '`Gender`="' . $gender . '", ';
            if ($ethnicity != '') $member_sql .= '`Ethnicity`="' . $ethnicity . '", ';
            if ($shirtSize != '') $member_sql .= '`ShirtSize`="' . $shirtSize . '", ';
            if ($street != '') $member_sql .= '`Street`="' . $street . '", ';
            if ($city != '') $member_sql .= '`City`="' . $city . '", ';
            if ($state != '') $member_sql .= '`State`="' . $state . '", ';
            if ($zip != '') $member_sql .= '`Zip`="' . $zip . '", ';

            $new_img_name = '';
            if ($img_name) {
                $img_ex = pathinfo($img_name, PATHINFO_EXTENSION);
                $img_ex_lc = strtolower($img_ex);
                $allowed_exs = array("png", "jpg", "jpeg");

                if (in_array($img_ex_lc, $allowed_exs)) {
                    $new_img_name = uniqid("Member-", true) . '.' . $img_ex_lc;
                    $img_upload_path = '../../MemberPhotos/' . $new_img_name;

                    if ($img_ex_lc == 'jpg' || $img_ex_lc == 'jpeg') {
                        $src_image = imagecreatefromjpeg($tmp_name);
                    } elseif ($img_ex_lc == 'png') {
                        $src_image = imagecreatefrompng($tmp_name);
                    }

                    if ($src_image) {
                        $orig_width = imagesx($src_image);
                        $orig_height = imagesy($src_image);

                        $max_size = 220;

                        if ($orig_width > $orig_height) {
                            $new_width = $max_size;
                            $new_height = intval(($orig_height / $orig_width) * $max_size);
                        } else {
                            $new_height = $max_size;
                            $new_width = intval(($orig_width / $orig_height) * $max_size);
                        }

                        $dst_image = imagecreatetruecolor($new_width, $new_height);

                        if ($img_ex_lc == 'png') {
                            imagealphablending($dst_image, false);
                            imagesavealpha($dst_image, true);
                            $transparent = imagecolorallocatealpha($dst_image, 255, 255, 255, 127);
                            imagefilledrectangle($dst_image, 0, 0, $new_width, $new_height, $transparent);
                        }

                        imagecopyresampled($dst_image, $src_image, 0, 0, 0, 0, $new_width, $new_height, $orig_width, $orig_height);

                        if ($img_ex_lc == 'jpg' || $img_ex_lc == 'jpeg') {
                            imagejpeg($dst_image, $img_upload_path, 90);
                        } elseif ($img_ex_lc == 'png') {
                            imagepng($dst_image, $img_upload_path, 4);
                        }

                        imagedestroy($src_image);
                        imagedestroy($dst_image);
                    }
                }
            }

            if ($new_img_name != '') {
                $member_sql .= '`MemberPhoto`="' . $new_img_name . '", ';
            }

            $member_sql = rtrim($member_sql, ', ');
            $member_sql .= " WHERE `members`.`memberid` = $getMemberId";
            $member_query = $conn->query($member_sql);

            if ($member_query) {
                $_SESSION['successMessage'] = "<div class='message success'>Profile demographics successfully saved!</div>";
                header("Location: lookup.php?id=$getMemberId");
                exit;
            } else {
                $_SESSION['errorMessage'] = "<div class='message error'>Something went wrong.</div>";
                header("Location: lookup.php?id=$getMemberId");
                exit;
            }
        }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $LastName; ?>, <?php echo $FirstName; ?><?php echo !empty($Suffix) ? ' ' . $Suffix : ''; ?></title>
    <?php include("../common/head.php"); ?>
    <script>
        $(document).ready(function () {
            $('#Birthdate').datepicker({
                dateFormat: 'm/d/yy'
            });
        });
    </script>
</head>
<body>
    <?php include("../common/loading.php"); ?>
    <?php include("../common/top-navbar.php"); ?>
    <?php include("../common/memberhead.php"); ?>
    <div id="content">
        <?php include("../common/member-sidebar.php"); ?>
        <div id="main-content-wrapper">
            <h2>Member Lookup</h2>
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
            <form method="post" enctype="multipart/form-data">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <td width="180"><b>Photo:</b></td>
                            <td><input type="file" name="MemberPhoto"></td>
                        </tr>
                        <tr>
                            <td width="180"><b>Member Name:</b></td>
                            <td>
                                <input type="text" name="LastName" value="<?php echo $LastName; ?>" maxlength="100" required>
                                <input type="text" name="FirstName" value="<?php echo $FirstName; ?>" maxlength="100" required>
                                <select name="Suffix">
                                    <option value="" <?php if ($Suffix == '') { echo 'selected'; } ?>></option>
                                    <option value="Jr" <?php if ($Suffix == 'Jr') { echo 'selected'; } ?>>Jr</option>
                                    <option value="Sr" <?php if ($Suffix == 'Sr') { echo 'selected'; } ?>>Sr</option>
                                    <option value="II" <?php if ($Suffix == 'II') { echo 'selected'; } ?>>II</option>
                                    <option value="III" <?php if ($Suffix == 'III') { echo 'selected'; } ?>>III</option>
                                    <option value="IV" <?php if ($Suffix == 'IV') { echo 'selected'; } ?>>IV</option>
                                    <option value="V" <?php if ($Suffix == 'V') { echo 'selected'; } ?>>V</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td width="180"><b>Position:</b></td>
                            <td>
                                <select name="Position">
                                    <option value=""></option>
                                    <?php
                                    $positions = $conn->query("SELECT PositionName FROM officer_positions ORDER BY Sort asc");
                                    while ($row = $positions->fetch_assoc()) {
                                        $pos = htmlspecialchars($row['PositionName']);
                                        $selected = ($pos === $Position) ? "selected" : "";
                                        echo "<option value='{$pos}' {$selected}>{$pos}</option>";
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td width="180"><b>Email Address:</b></td>
                            <td>
                                <input type="text" name="EmailAddress" value="<?php echo $EmailAddress; ?>" maxlength="200" required style="width: 100%; max-width: 320px;">
                            </td>
                        </tr>
                        <tr>
                            <td width="180"><b>Cell Phone #:</b></td>
                            <td>
                                <input type="tel" name="CellPhone" value="<?php echo $CellPhone; ?>" required>
                            </td>
                        </tr>
                        <tr>
                            <td width="180"><b>Grade Level:</b></td>
                            <td>
                                <select name="GradeLevel">
                                    <?php
                                    for ($i = (int)$minGrade; $i <= (int)$maxGrade; $i++) {
                                        $selected = ($GradeLevel == $i) ? "selected" : "";
                                        echo "<option value='$i' $selected>" . getGradeLabel($i) . "</option>";
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td width="180" valign="top"><b>DOB:</b></td>
                            <td>
                                <input type="text" name="Birthdate" id="Birthdate" value="<?php echo $Birthdate; ?>" required style="display: block; margin-bottom: 4px;">
                                Age:
                                <?php
                                    $Birthday = new DateTime($Birthdate_SQL);
                                    $MemberAge = $Birthday->diff(new DateTime);
                                    echo $MemberAge->y;
                                    echo ' years ';
                                    echo $MemberAge->m;
                                    echo ' months';
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td width="180"><b>Gender:</b></td>
                            <td>
                                <select name="Gender">
                                    <option value="Female" <?php if ($Gender == 'Female') { echo 'selected'; } ?>>Female</option>
                                    <option value="Male" <?php if ($Gender == 'Male') { echo 'selected'; } ?>>Male</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td width="180"><b>Race or Ethnicity:</b></td>
                            <td>
                                <input list="raceList" name="Ethnicity" value="<?php echo $Ethnicity; ?>" placeholder="Type or select race or ethnicity" maxlength="100" style="width: 60%;">
                                <datalist id="raceList">
                                <?php
                                    $query = "SELECT DISTINCT Ethnicity FROM members ORDER BY Ethnicity asc";
                                    $res = $conn->query($query);
                                    while ($row = $res->fetch_assoc()) {
                                        echo "<option value='" . htmlspecialchars($row['Ethnicity']) . "'>";
                                    }
                                ?>
                                </datalist>
                            </td>
                        </tr>
                        <tr>
                            <td width="180"><b>T-shirt size:</b></td>
                            <td>
                                <select name="ShirtSize">
                                    <option value="XS" <?php if ($ShirtSize == 'XS') { echo 'selected'; } ?>>XS</option>
                                    <option value="S" <?php if ($ShirtSize == 'S') { echo 'selected'; } ?>>S</option>
                                    <option value="M" <?php if ($ShirtSize == 'M') { echo 'selected'; } ?>>M</option>
                                    <option value="L" <?php if ($ShirtSize == 'L') { echo 'selected'; } ?>>L</option>
                                    <option value="XL" <?php if ($ShirtSize == 'XL') { echo 'selected'; } ?>>XL</option>
                                    <option value="2XL" <?php if ($ShirtSize == '2XL') { echo 'selected'; } ?>>2XL</option>
                                    <option value="3XL" <?php if ($ShirtSize == '3XL') { echo 'selected'; } ?>>3XL</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td width="180" valign="top"><b>Address:</b></td>
                            <td>
                                <input type="text" name="Street" value="<?php echo $Street; ?>" maxlength="100" required style="display: block; width: 100%; max-width: 380px; margin-bottom: 8px;">
                                <input type="text" name="City" value="<?php echo $City; ?>" maxlength="100" required>
                                <input type="text" name="State" value="<?php echo $State; ?>" value="South Carolina" maxlength="100" required>
                                <input type="text" name="Zip" value="<?php echo $Zip; ?>" maxlength="100" required>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2"><button type="submit" name="save_member">Save changes</button></td>
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