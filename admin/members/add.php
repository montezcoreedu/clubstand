<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/chapter_settings.php");
    include("../common/permissions.php");
    include("../common/grade_level_entry.php");

    if (!in_array("Add Member", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    $schools = !empty($chapter['Schools']) ? explode(',', $chapter['Schools']) : [];

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $lastName = mysqli_real_escape_string($conn, $_POST['LastName']);
        $firstName = mysqli_real_escape_string($conn, $_POST['FirstName']);
        $suffix = mysqli_real_escape_string($conn, (string)($_POST['Suffix'] ?? ''));
        $emailAddress = mysqli_real_escape_string($conn, $_POST['EmailAddress']);
        $cellPhone = mysqli_real_escape_string($conn, $_POST['CellPhone']);
        $gradeLevel = is_numeric($_POST['GradeLevel']) ? (int)$_POST['GradeLevel'] : NULL;

        $rawBirthdate = trim($_POST['Birthdate'] ?? '');
        $birthdateObj = DateTime::createFromFormat('n/j/Y', $rawBirthdate)
                    ?: DateTime::createFromFormat('m/d/Y', $rawBirthdate)
                    ?: DateTime::createFromFormat('Y-m-d', $rawBirthdate);
        $birthdate = $birthdateObj ? $birthdateObj->format('Y-m-d') : NULL;

        $gender = mysqli_real_escape_string($conn, $_POST['Gender']);
        $ethnicity = mysqli_real_escape_string($conn, $_POST['Ethnicity']);
        $shirtSize = mysqli_real_escape_string($conn, $_POST['ShirtSize']);

        $street = mysqli_real_escape_string($conn, $_POST['Street']);
        $city = mysqli_real_escape_string($conn, $_POST['City']);
        $state = mysqli_real_escape_string($conn, $_POST['State']);
        $zip = mysqli_real_escape_string($conn, $_POST['Zip']);

        $membershipYear = is_numeric($_POST['MembershipYear']) ? (int)$_POST['MembershipYear'] : NULL;
        $membershipTier = mysqli_real_escape_string($conn, $_POST['MembershipTier']);
        $school = mysqli_real_escape_string($conn, $_POST['School']);

        $primaryContactName = mysqli_real_escape_string($conn, $_POST['PrimaryContactName']);
        $primaryContactPhone = mysqli_real_escape_string($conn, $_POST['PrimaryContactPhone']);
        $primaryContactEmail = mysqli_real_escape_string($conn, $_POST['PrimaryContactEmail']);

        $secondaryContactName = mysqli_real_escape_string($conn, $_POST['SecondaryContactName'] ?? '');
        $secondaryContactPhone = mysqli_real_escape_string($conn, $_POST['SecondaryContactPhone'] ?? '');
        $secondaryContactEmail = mysqli_real_escape_string($conn, $_POST['SecondaryContactEmail'] ?? '');

        $sql = "INSERT INTO members (
            LastName, FirstName, Suffix, EmailAddress, CellPhone, GradeLevel, Birthdate, Gender, Ethnicity, ShirtSize,
            Street, City, State, Zip, MembershipYear, MembershipTier, School,
            PrimaryContactName, PrimaryContactPhone, PrimaryContactEmail,
            SecondaryContactName, SecondaryContactPhone, SecondaryContactEmail
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?
        )";
        
        $stmt = $conn->prepare($sql);
        
        $suffix = !empty($suffix) ? $suffix : null;
        $gradeLevel = $gradeLevel !== null ? $gradeLevel : null;
        $birthdate = !empty($birthdate) ? $birthdate : null;
        $membershipYear = $membershipYear !== null ? $membershipYear : null;
        
        $stmt->bind_param(
            "sssssisissssssissssssss",
            $lastName, $firstName, $suffix, $emailAddress, $cellPhone, $gradeLevel, $birthdate, $gender, $ethnicity, $shirtSize,
            $street, $city, $state, $zip, $membershipYear, $membershipTier, $school,
            $primaryContactName, $primaryContactPhone, $primaryContactEmail,
            $secondaryContactName, $secondaryContactPhone, $secondaryContactEmail
        );
        
        if ($stmt->execute()) {
            $memberId = $stmt->insert_id ?: $conn->insert_id;
            header("Location: lookup.php?id=$memberId");
            exit();
        } else {
            echo "<div class='message error'>Error: " . $stmt->error . "</div>";
        }
        
        $stmt->close();        

        if (mysqli_query($conn, $sql)) {
            $memberId = mysqli_insert_id($conn);
            header("Location: lookup.php?id=$memberId");
            exit();
        } else {
            $_SESSION['errorMessage'] = "<div class='message error'>Error: " . mysqli_error($conn) . "</div>";
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Member</title>
    <?php include("../common/head.php"); ?>
    <script>
        $(function() {
            $("#Birthdate_id").datepicker({
                dateFormat: 'mm/dd/yy',
                changeMonth: true,
                changeYear: true,
                yearRange: "2005:2030"
            });
        });
    </script>
</head>
<body>
    <?php include("../common/top-navbar.php"); ?>
    <div id="wrapper">
        <ul class="breadcrumbs">
            <li>
                <a href="../home/index.php">Member Search</a>
            </li>
            <?php if (!empty(array_intersect(['Import Members','Assign Officers','Returning Members Form','Store Membership Data','Transition Members','Clear Records'], $userPermissions))): ?>
            <li>
                <a href="../manage/index.php">Manage Membership</a>
            </li>
            <?php endif; ?>
            <li>
                <span>Add Member</span>
            </li>
        </ul>
        <h2>Add Member</h2>
        <?php
            if (isset($_SESSION['errorMessage'])) {
                echo $_SESSION['errorMessage'];
                unset($_SESSION['errorMessage']);
            }
        ?>
        <form method="post">
            <table class="form-table">
                <thead>
                    <th align="left" colspan="2">Member Information</th>
                </thead>
                <tbody>
                    <tr>
                        <td width="180">
                            <label for="LastName_id"><b>Last Name</b></label>
                        </td>
                        <td><input type="text" name="LastName" id="LastName_id" maxlength="100" required autofocus></td>
                    </tr>
                    <tr>
                        <td width="180">
                            <label for="FirstName_id"><b>First Name</b></label>
                        </td>
                        <td><input type="text" name="FirstName" id="FirstName_id" maxlength="100" required></td>
                    </tr>
                    <tr>
                        <td width="180">
                            <label for="Suffix_id"><b>Suffix</b></label>
                        </td>
                        <td>
                            <select name="Suffix" id="Suffix_id">
                                <option value=""></option>
                                <option value="Jr">Jr</option>
                                <option value="Sr">Sr</option>
                                <option value="II">II</option>
                                <option value="III">III</option>
                                <option value="IV">IV</option>
                                <option value="V">V</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td width="180">
                            <label for="EmailAddress_id"><b>Email Address</b></label>
                        </td>
                        <td><input type="email" name="EmailAddress" id="EmailAddress_id" maxlength="200" required style="width: 100%; max-width: 320px;"></td>
                    </tr>
                    <tr>
                        <td width="180">
                            <label for="CellPhone_id"><b>Cell Phone</b></label>
                        </td>
                        <td><input type="tel" name="CellPhone" id="CellPhone_id"></td>
                    </tr>
                    <tr>
                        <td width="180">
                            <label for="GradeLevel_id"><b>Grade Level</b></label>
                        </td>
                        <td>
                            <select name="GradeLevel" id="GradeLevel_id" required>
                                <option value=""></option>
                                <?php
                                for ($i = (int)$minGrade; $i <= (int)$maxGrade; $i++) {
                                    echo "<option value='$i'>" . getGradeLabel($i) . "</option>";
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td width="180">
                            <label for="Birthdate_id"><b>DOB</b></label>
                        </td>
                        <td><input type="text" name="Birthdate" id="Birthdate_id" required></td>
                    </tr>
                    <tr>
                        <td width="180">
                            <label for="Gender_id"><b>Gender</b></label>
                        </td>
                        <td>
                            <select name="Gender" id="Gender_id" required>
                                <option value=""></option>
                                <option value="Female">Female</option>
                                <option value="Male">Male</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td width="180">
                            <label for="Ethnicity_id"><b>Ethnicity</b></label>
                        </td>
                        <td>
                            <select name="Ethnicity" id="Ethnicity_id">
                                <option value=""></option>
                                <option value="African American">African American</option>
                                <option value="Asian">Asian</option>
                                <option value="Caucasian">Caucasian</option>
                                <option value="Hispanic">Hispanic</option>
                                <option value="Native American">Native American</option>
                                <option value="Other">Other</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td width="180">
                            <label for="ShirtSize_id"><b>T-shirt Size</b></label>
                        </td>
                        <td>
                            <select name="ShirtSize" id="ShirtSize_id">
                                <option value=""></option>
                                <option value="XS">XS</option>
                                <option value="S">S</option>
                                <option value="M">M</option>
                                <option value="L">L</option>
                                <option value="XL">XL</option>
                                <option value="2XL">2XL</option>
                                <option value="3XL">3XL</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td width="180" valign="top">
                            <label for="Street_id"><b>Address</b></label>
                        </td>
                        <td>
                            <input type="text" name="Street" placeholder="Street" id="Street_id" maxlength="100" style="display: block; margin-bottom: 8px;">
                            <input type="text" name="City" placeholder="City" maxlength="100">
                            <input type="text" name="State" placeholder="State" maxlength="100">
                            <input type="text" name="Zip" placeholder="Zip" maxlength="100">
                        </td>
                    </tr>
                </tbody>
                <thead>
                    <th align="left" colspan="2">Membership Information</th>
                </thead>
                <tbody>
                    <tr>
                        <td width="180">
                            <label for="MembershipYear_id"><b>Membership Year</b></label>
                        </td>
                        <td>
                            <select name="MembershipYear" id="MembershipYear_id">
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                                <option value="6">6</option>
                                <option value="7">7</option>
                                <option value="8">8</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td width="180">
                            <label for="MembershipTier_id"><b>Membership Tier</b></label>
                        </td>
                        <td>
                            <select name="MembershipTier" id="MembershipTier_id">
                                <option value="Gold">Gold</option>
                                <option value="Silver">Silver</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td width="180">
                            <label for="School_id"><b>School</b></label>
                        </td>
                        <td>
                            <select name="School" id="School_id" required>
                                <?php foreach ($schools as $school): ?>
                                    <?php $school = trim($school); ?>
                                    <option value="<?php echo htmlspecialchars($school); ?>">
                                        <?php echo htmlspecialchars($school); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </tbody>
                <thead>
                    <th align="left" colspan="2">Parent/Guardian Information</th>
                </thead>
                <tbody>
                    <tr>
                        <td width="180">
                            <label for="PrimaryContactName_id"><b>Primary Contact</b></label>
                        </td>
                        <td><input type="text" name="PrimaryContactName" id="PrimaryContactName_id" placeholder="Name" maxlength="100" required style="width: 100%; max-width: 280px;"></td>
                    </tr>
                    <tr>
                        <td width="180"></td>
                        <td><input type="text" name="PrimaryContactPhone" id="PrimaryContactPhone_id" placeholder="Phone" maxlength="200" required></td>
                    </tr>
                    <tr>
                        <td width="180"></td>
                        <td><input type="email" name="PrimaryContactEmail" id="PrimaryContactEmail_id" placeholder="Email" maxlength="100" required style="width: 100%; max-width: 320px;"></td>
                    </tr>
                    <tr>
                        <td width="180">
                            <label for="SecondaryContactName_id"><b>Secondary Contact</b></label>
                        </td>
                        <td><input type="text" name="SecondaryContactName" id="SecondaryContactName_id" placeholder="Name" maxlength="100" style="width: 100%; max-width: 280px;"></td>
                    </tr>
                    <tr>
                        <td width="180"></td>
                        <td><input type="text" name="SecondaryContactPhone" id="SecondaryContactPhone_id" placeholder="Phone" maxlength="200"></td>
                    </tr>
                    <tr>
                        <td width="180"></td>
                        <td><input type="email" name="SecondaryContactEmail" id="SecondaryContactEmail_id" placeholder="Email" maxlength="100" style="width: 100%; max-width: 320px;"></td>
                    </tr>
                </tbody>
            </table>
            <div>
                <button type="submit">Submit</button>
            </div>
        </form>
    </div>
</body>
</html>