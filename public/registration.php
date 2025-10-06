<?php
    include("../dbconnection.php");
    include("common/session.php");

    $memberId = $_SESSION['account_id'];

    $query = "SELECT * FROM members WHERE MemberId = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $memberId);
    $stmt->execute();
    $result = $stmt->get_result();
    $member = $result->fetch_assoc();

    $Birthdate_SQL = $member['Birthdate'];
    $Birthdate = date('n/j/Y', strtotime($Birthdate_SQL));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Returning Registration</title>
    <?php include("common/head.php"); ?>
    <script>
        $( function() {
            $( "#Birthdate" ).datepicker({
                dateFormat: 'm/d/yy'
            });
        } );
    </script>
</head>
<body>
    <div id="wrapper">
        <div style="float: right;">
            <ul>
                <li>
                    <?php echo $_SESSION['FirstName'] . " " . $_SESSION['LastName']; ?>
                    <a href="logout.php" style="font-size: 12px; margin-left: 5px; text-decoration: none;">Logout</a>
                </li>
            </ul>
        </div>
        <a href="home.php">< Go back to home</a>
        <div id="container">
            <div class="sidebar">
                <h2>Returning Registration</h2>
                <ul>
                    <li class="step-tab active" onclick="showStep(0)">Profile</li>
                    <li class="step-tab" onclick="showStep(1)">Emergency Contacts</li>
                    <li class="step-tab" onclick="showStep(2)">Membership</li>
                    <li class="step-tab" onclick="showStep(3)">Review</li>
                </ul>
            </div>
            <div class="content">
                <form method="post" action="submit_registration.php">
                    <!-- Step 1 -->
                    <div class="step active">
                        <h3>Profile Information</h3>
                        <p>Enter your personal information.</p>
                        <label>First Name</label><br>
                        <input type="text" name="FirstName" value="<?= htmlspecialchars($member['FirstName']) ?>" required><br><br>
                        <label>Last Name</label><br>
                        <input type="text" name="LastName" value="<?= htmlspecialchars($member['LastName']) ?>" required><br><br>
                        <label>Suffix</label><br>
                        <select name="Suffix">
                            <option value=""></option>
                            <option value="Jr" <?= $member['Suffix'] === 'Jr' ? 'selected' : '' ?>>Jr</option>
                            <option value="Sr" <?= $member['Suffix'] === 'Sr' ? 'selected' : '' ?>>Sr</option>
                            <option value="II" <?= $member['Suffix'] === 'II' ? 'selected' : '' ?>>II</option>
                            <option value="III" <?= $member['Suffix'] === 'III' ? 'selected' : '' ?>>III</option>
                            <option value="IV" <?= $member['Suffix'] === 'IV' ? 'selected' : '' ?>>IV</option>
                            <option value="V" <?= $member['Suffix'] === 'V' ? 'selected' : '' ?>>V</option>
                        </select><br><br>
                        <label>Email Address</label><br>
                        <input type="email" name="EmailAddress" value="<?= htmlspecialchars($member['EmailAddress']) ?>" required style="width: 100%; max-width: 280px;"><br><br>
                        <label>Cell Phone #</label><br>
                        <input type="tel" name="CellPhone" value="<?= htmlspecialchars($member['CellPhone']) ?>" required><br><br>
                        <label>Birthdate</label><br>
                        <input type="text" id="Birthdate" name="Birthdate" value="<?= $Birthdate ?>" required><br><br>
                        <label>Gender</label><br>
                        <select name="Gender">
                            <option value="Female" <?= $member['Gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                            <option value="Male" <?= $member['Gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                        </select><br><br>
                        <label>Ethnicity</label><br>
                        <select name="Ethnicity">
                            <option value="African American" <?= $member['Ethnicity'] === 'African American' ? 'selected' : '' ?>>African American</option>
                            <option value="Asian" <?= $member['Ethnicity'] === 'Asian' ? 'selected' : '' ?>>Asian</option>
                            <option value="Caucasian" <?= $member['Ethnicity'] === 'Caucasian' ? 'selected' : '' ?>>Caucasian</option>
                            <option value="Hispanic" <?= $member['Ethnicity'] === 'Hispanic' ? 'selected' : '' ?>>Hispanic</option>
                            <option value="Native American" <?= $member['Ethnicity'] === 'Native American' ? 'selected' : '' ?>>Native American</option>
                            <option value="Other" <?= $member['Ethnicity'] === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select><br><br>
                        <label>Street</label><br>
                        <input type="text" name="Street" value="<?= htmlspecialchars($member['Street']) ?>" required style="width: 100%; max-width: 280px;"><br><br>
                        <label>City</label><br>
                        <input type="text" name="City" value="<?= htmlspecialchars($member['City']) ?>" required><br><br>
                        <label>State</label><br>
                        <input type="text" name="State" value="<?= htmlspecialchars($member['State']) ?>" required><br><br>
                        <label>Zip</label><br>
                        <input type="text" name="Zip" value="<?= htmlspecialchars($member['Zip']) ?>" required><br><br>
                        <button type="button" onclick="nextStep()">Next</button>
                    </div>

                    <!-- Step 2 -->
                    <div class="step">
                        <h3>Emergency Contacts</h3>
                        <label>Primary Contact Name</label><br>
                        <input type="text" name="PrimaryContactName" value="<?= htmlspecialchars($member['PrimaryContactName']) ?>" required style="width: 100%; max-width: 220px;"><br><br>
                        <label>Primary Contact Phone</label><br>
                        <input type="tel" name="PrimaryContactPhone" value="<?= htmlspecialchars($member['PrimaryContactPhone']) ?>" required><br><br>
                        <label>Primary Contact Email</label><br>
                        <input type="email" name="PrimaryContactEmail" value="<?= htmlspecialchars($member['PrimaryContactEmail']) ?>" required style="width: 100%; max-width: 280px;"><br><br>
                        <label>Secondary Contact Name</label><br>
                        <input type="text" name="SecondaryContactName" value="<?= htmlspecialchars($member['SecondaryContactName']) ?>" required style="width: 100%; max-width: 220px;"><br><br>
                        <label>Secondary Contact Phone</label><br>
                        <input type="tel" name="SecondaryContactPhone" value="<?= htmlspecialchars($member['SecondaryContactPhone']) ?>" required><br><br>
                        <label>Secondary Contact Email</label><br>
                        <input type="email" name="SecondaryContactEmail" value="<?= htmlspecialchars($member['SecondaryContactEmail']) ?>" required style="width: 100%; max-width: 280px;"><br><br>
                        <button type="button" onclick="prevStep()">Back</button>
                        <button type="button" onclick="nextStep()">Next</button>
                    </div>

                    <!-- Step 3 -->
                    <div class="step">
                        <h3>Membership Information</h3>
                        <label>Updated Grade Level</label><br>
                        <input type="hidden" name="CurrentGradeLevel" value="<?= $member['GradeLevel'] ?>">
                        <select name="GradeLevel">
                            <option value="9" <?= $member['GradeLevel'] == '9' ? 'selected' : '' ?>>9th Grade</option>
                            <option value="10" <?= $member['GradeLevel'] == '10' ? 'selected' : '' ?>>10th Grade</option>
                            <option value="11" <?= $member['GradeLevel'] == '11' ? 'selected' : '' ?>>11th Grade</option>
                            <option value="12" <?= $member['GradeLevel'] == '12' ? 'selected' : '' ?>>12th Grade</option>
                        </select><br><br>
                        <label>T-shirt Size</label><br>
                        <select name="ShirtSize">
                            <option value="S" <?= $member['ShirtSize'] == 'S' ? 'selected' : '' ?>>S</option>
                            <option value="M" <?= $member['ShirtSize'] == 'M' ? 'selected' : '' ?>>M</option>
                            <option value="L" <?= $member['ShirtSize'] == 'L' ? 'selected' : '' ?>>L</option>
                            <option value="XL" <?= $member['ShirtSize'] == 'XL' ? 'selected' : '' ?>>XL</option>
                            <option value="2XL" <?= $member['ShirtSize'] == '2XL' ? 'selected' : '' ?>>2XL</option>
                            <option value="3XL" <?= $member['ShirtSize'] == '3XL' ? 'selected' : '' ?>>3XL</option>
                        </select><br><br>
                        <label>Updated Membership Year</label><br>
                        <input type="hidden" name="CurrentMembershipYear" value="<?= $member['MembershipYear'] ?>">
                        <select name="MembershipYear">
                            <option value="1" <?= $member['MembershipYear'] == '1' ? 'selected' : '' ?>>1</option>
                            <option value="2" <?= $member['MembershipYear'] == '2' ? 'selected' : '' ?>>2</option>
                            <option value="3" <?= $member['MembershipYear'] == '3' ? 'selected' : '' ?>>3</option>
                            <option value="4" <?= $member['MembershipYear'] == '4' ? 'selected' : '' ?>>4</option>
                            <option value="5"> <?= $member['MembershipYear'] == '5' ? 'selected' : '' ?>5</option>
                            <option value="6" <?= $member['MembershipYear'] == '6' ? 'selected' : '' ?>>6</option>
                        </select><br><br>
                        <label>Desired Membership Tier</label><br>
                        <select name="MembershipTier">
                            <option value="Gold" <?= $member['MembershipTier'] === 'Gold' ? 'selected' : '' ?>>Gold ($50)</option>
                            <option value="Silver" <?= $member['MembershipTier'] === 'Silver' ? 'selected' : '' ?>>Silver ($35)</option>
                        </select><br><br>
                        <label>Current School</label><br>
                        <select name="School">
                            <option value="Berkeley High School" <?= $member['School'] === 'Berkeley High School' ? 'selected' : '' ?>>Berkeley High School</option>
                            <option value="Berkeley Middle College" <?= $member['School'] === 'Berkeley Middle College' ? 'selected' : '' ?>>Berkeley Middle College</option>
                        </select><br><br>
                        <button type="button" onclick="prevStep()">Back</button>
                        <button type="button" onclick="nextStep()">Next</button>
                    </div>

                    <!-- Step 4 -->
                    <div class="step">
                        <h3>Review & Submit</h3>
                        <p>Before submitting, please ensure all sections are completed:</p>
                        <ul style="list-style: none; padding-left: 0;">
                            <li><label><input type="checkbox" required> I have confirmed my personal info is correct</label></li>
                            <li><label><input type="checkbox" required> Emergency contact information is up to date</label></li>
                            <li><label><input type="checkbox" required> My membership information is accurate</label></li>
                            <li><label><input type="checkbox" required> I understand I am not a full active member until payment is received</label></li>
                        </ul>
                        <br>
                        <button type="button" onclick="prevStep()">Back</button>
                        <button type="submit">Submit Registration</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <footer>
        <div class="container">
            <a href="https://www.berkeleyhighscfbla.net/" target="_blank" rel="noopener noreferrer">
                © 2025 Berkeley High FBLA
            </a>
        </div>
    </footer>
    <script>
        let currentStep = 0;
        const steps = document.querySelectorAll('.step');
        const tabs = document.querySelectorAll('.step-tab');

        function showStep(index) {
            steps.forEach((step, i) => {
                step.classList.toggle('active', i === index);
                tabs[i].classList.toggle('active', i === index);
            });
            currentStep = index;
        }

        function nextStep() {
            if (currentStep < steps.length - 1) {
                showStep(currentStep + 1);
            }
        }

        function prevStep() {
            if (currentStep > 0) {
                showStep(currentStep - 1);
            }
        }

        document.querySelector('form').addEventListener('submit', function(e) {
            const currentGrade = parseInt(document.querySelector('[name="CurrentGradeLevel"]').value);
            const newGrade = parseInt(document.querySelector('[name="GradeLevel"]').value);

            const currentYear = parseInt(document.querySelector('[name="CurrentMembershipYear"]').value);
            const newYear = parseInt(document.querySelector('[name="MembershipYear"]').value);

            if (newGrade <= currentGrade || newYear <= currentYear) {
                alert("Please update your grade level and membership year before submitting.");
                e.preventDefault();
            }
        });

        document.querySelector("form").addEventListener("submit", function(e) {
            const checklist = document.querySelectorAll(".step:last-of-type input[type='checkbox']");
            for (const box of checklist) {
                if (!box.checked) {
                    alert("Please complete all review confirmations before submitting.");
                    e.preventDefault();
                    return;
                }
            }
        });
    </script>
</body>
</html>