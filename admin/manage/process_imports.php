<?php
    session_start();
    include("../../dbconnection.php");
    include("../common/permissions.php");

    if (!in_array("Import Members", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_FILES['csv_file'])) {
            $fileError = $_FILES['csv_file']['error'];

            if ($fileError === UPLOAD_ERR_OK) {
                $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
                if (!$file) {
                    $_SESSION['errorMessage'] = "<div class='message error'>Unable to open uploaded file.</div>";
                    header("Location: import_members.php");
                    exit;
                }

                $headers = fgetcsv($file);
                if (!$headers) {
                    $_SESSION['errorMessage'] = "<div class='message error'>CSV file is empty or invalid.</div>";
                    fclose($file);
                    header("Location: import_members.php");
                    exit;
                }

                $imported = 0;
                $skipped = 0;
                $rowCount = 0;

                while (($data = fgetcsv($file)) !== FALSE) {
                    $rowCount++;
                    if (count($data) != count($headers)) {
                        $skipped++;
                        continue;
                    }

                    $row = array_combine($headers, $data);

                    // Escape and validate fields
                    $LastName = mysqli_real_escape_string($conn, $row['LastName'] ?? '');
                    $FirstName = mysqli_real_escape_string($conn, $row['FirstName'] ?? '');
                    $Suffix = mysqli_real_escape_string($conn, $row['Suffix'] ?? '');
                    $Position = !empty($row['Position']) ? mysqli_real_escape_string($conn, $row['Position']) : NULL;
                    $EmailAddress = mysqli_real_escape_string($conn, $row['EmailAddress'] ?? '');
                    $GradeLevel = is_numeric(trim($row['GradeLevel'] ?? '')) ? (int)trim($row['GradeLevel']) : "NULL";
                    $MembershipYear = is_numeric(trim($row['MembershipYear'] ?? '')) ? (int)trim($row['MembershipYear']) : "NULL";

                    $rawBirthdate = trim($row['Birthdate'] ?? '');
                    $Birthdate = NULL;
                    if ($rawBirthdate) {
                        $formats = ['n/j/Y','m/d/Y','n-j-Y','m-d-Y','Y-m-d','Y/n/j','Y/m/d','F j, Y','M j, Y','j F Y','j M Y','m-d-y','m/d/y'];
                        foreach ($formats as $format) {
                            $dt = DateTime::createFromFormat($format, $rawBirthdate);
                            if ($dt && $dt->format($format) === $rawBirthdate) {
                                $Birthdate = $dt->format('Y-m-d');
                                break;
                            }
                        }
                        if (!$Birthdate) {
                            $ts = strtotime($rawBirthdate);
                            if ($ts !== false) $Birthdate = date('Y-m-d', $ts);
                        }
                    }

                    $Gender = mysqli_real_escape_string($conn, $row['Gender'] ?? '');
                    $Ethnicity = mysqli_real_escape_string($conn, $row['Ethnicity'] ?? '');
                    $ShirtSize = mysqli_real_escape_string($conn, $row['ShirtSize'] ?? '');
                    $Street = mysqli_real_escape_string($conn, $row['Street'] ?? '');
                    $City = mysqli_real_escape_string($conn, $row['City'] ?? '');
                    $State = mysqli_real_escape_string($conn, $row['State'] ?? '');
                    $Zip = mysqli_real_escape_string($conn, $row['Zip'] ?? '');
                    $MembershipTier = mysqli_real_escape_string($conn, $row['MembershipTier'] ?? '');
                    $School = mysqli_real_escape_string($conn, $row['School'] ?? '');
                    $PrimaryContactName = mysqli_real_escape_string($conn, $row['PrimaryContactName'] ?? '');
                    $PrimaryContactPhone = mysqli_real_escape_string($conn, $row['PrimaryContactPhone'] ?? '');
                    $PrimaryContactEmail = mysqli_real_escape_string($conn, $row['PrimaryContactEmail'] ?? '');
                    $SecondaryContactName = mysqli_real_escape_string($conn, $row['SecondaryContactName'] ?? '');
                    $SecondaryContactPhone = mysqli_real_escape_string($conn, $row['SecondaryContactPhone'] ?? '');
                    $SecondaryContactEmail = mysqli_real_escape_string($conn, $row['SecondaryContactEmail'] ?? '');

                    // Skip duplicates
                    $check = $conn->query("SELECT 1 FROM members WHERE EmailAddress = '$EmailAddress' LIMIT 1");
                    if ($check && $check->num_rows > 0) {
                        $skipped++;
                        continue;
                    }

                    $sql = "INSERT INTO members (
                                LastName, FirstName, Suffix, Position, EmailAddress, GradeLevel, Birthdate, Gender, Ethnicity, ShirtSize,
                                Street, City, State, Zip, MembershipYear, MembershipTier, School, PrimaryContactName, PrimaryContactPhone,
                                PrimaryContactEmail, SecondaryContactName, SecondaryContactPhone, SecondaryContactEmail
                            ) VALUES (
                                '$LastName', '$FirstName', '$Suffix', " . ($Position ? "'$Position'" : "NULL") . ", '$EmailAddress', $GradeLevel, " .
                                ($Birthdate ? "'$Birthdate'" : "NULL") . ", '$Gender', '$Ethnicity', '$ShirtSize', '$Street', '$City', '$State', '$Zip', $MembershipYear, '$MembershipTier', '$School', '$PrimaryContactName', '$PrimaryContactPhone', '$PrimaryContactEmail', '$SecondaryContactName', '$SecondaryContactPhone', '$SecondaryContactEmail'
                            )";

                    if ($conn->query($sql)) {
                        $imported++;
                    } else {
                        $skipped++;
                        error_log("SQL Error (row $rowCount): " . $conn->error);
                    }
                }

                fclose($file);

                $_SESSION['successMessage'] = "<div class='message success'>$imported members imported successfully. $skipped skipped (duplicates or errors).</div>";
                header("Location: import_members.php");
                exit;
            } else {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => "The uploaded file exceeds the upload_max_filesize directive in php.ini.",
                    UPLOAD_ERR_FORM_SIZE => "The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form.",
                    UPLOAD_ERR_PARTIAL => "The file was only partially uploaded.",
                    UPLOAD_ERR_NO_FILE => "No file was uploaded.",
                    UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder.",
                    UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk.",
                    UPLOAD_ERR_EXTENSION => "A PHP extension stopped the file upload."
                ];

                $errorMessage = $errorMessages[$fileError] ?? "Unknown error uploading file.";
                $_SESSION['errorMessage'] = "<div class='message error'>$errorMessage</div>";
                header("Location: import_members.php");
                exit;
            }
        }
    }