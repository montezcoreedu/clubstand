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
    
            if ($fileError == UPLOAD_ERR_OK) {
                $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
        
                $headers = fgetcsv($file);
        
                $previewData = [];
                $imported = 0;
                $skipped = 0;
        
                while (($data = fgetcsv($file)) !== FALSE) {
                    if (count($data) != count($headers)) {
                        $skipped++;
                        continue;
                    }

                    // Prevent memory explosion
                    if (memory_get_usage() > 200 * 1024 * 1024) {
                        break;
                    }

                    $previewData[] = array_combine($headers, $data);
                }
        
                fclose($file);
        
                $_SESSION['previewData'] = $previewData;
        
                header("Location: preview_import.php");
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
            }
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'confirm') {
        if (!isset($_SESSION['previewData'])) {
            $_SESSION['errorMessage'] = "<div class='message error'>No data to import. Please upload the CSV first.</div>";
            header("Location: import_members.php");
            exit;
        }

        $previewData = $_SESSION['previewData'];
        $imported = 0;
        $skipped = 0;

        foreach ($previewData as $row) {
            $LastName = mysqli_real_escape_string($conn, $row['LastName'] ?? NULL);
            $FirstName = mysqli_real_escape_string($conn, $row['FirstName'] ?? NULL);
            $Suffix = mysqli_real_escape_string($conn, $row['Suffix'] ?? NULL);
            $Position = !empty($row['Position']) ? mysqli_real_escape_string($conn, $row['Position']) : NULL;
            $EmailAddress = mysqli_real_escape_string($conn, $row['EmailAddress'] ?? NULL);
            $GradeLevel = is_numeric(trim($row['GradeLevel'] ?? '')) ? (int)trim($row['GradeLevel']) : NULL;
            $MembershipYear = is_numeric(trim($row['MembershipYear'] ?? '')) ? (int)trim($row['MembershipYear']) : NULL;
            $rawBirthdate = trim($row['Birthdate'] ?? '');
            if ($rawBirthdate) {
                $birthdateObj = DateTime::createFromFormat('n/j/Y', $rawBirthdate)
                            ?: DateTime::createFromFormat('m/d/Y', $rawBirthdate);
                $Birthdate = $birthdateObj ? $birthdateObj->format('Y-m-d') : NULL;
            } else {
                $Birthdate = NULL;
            }
            $Gender = mysqli_real_escape_string($conn, $row['Gender'] ?? NULL);
            $Ethnicity = mysqli_real_escape_string($conn, $row['Ethnicity'] ?? NULL);
            $ShirtSize = mysqli_real_escape_string($conn, $row['ShirtSize'] ?? NULL);
            $Street = mysqli_real_escape_string($conn, $row['Street'] ?? NULL);
            $City = mysqli_real_escape_string($conn, $row['City'] ?? NULL);
            $State = mysqli_real_escape_string($conn, $row['State'] ?? NULL);
            $Zip = mysqli_real_escape_string($conn, $row['Zip'] ?? NULL);
            $MembershipTier = mysqli_real_escape_string($conn, $row['MembershipTier'] ?? NULL);
            $School = mysqli_real_escape_string($conn, $row['School'] ?? NULL);
            $PrimaryContactName = mysqli_real_escape_string($conn, $row['PrimaryContactName'] ?? NULL);
            $PrimaryContactPhone = mysqli_real_escape_string($conn, $row['PrimaryContactPhone'] ?? NULL);
            $PrimaryContactEmail = mysqli_real_escape_string($conn, $row['PrimaryContactEmail'] ?? NULL);
            $SecondaryContactName = mysqli_real_escape_string($conn, $row['SecondaryContactName'] ?? NULL);
            $SecondaryContactPhone = mysqli_real_escape_string($conn, $row['SecondaryContactPhone'] ?? NULL);
            $SecondaryContactEmail = mysqli_real_escape_string($conn, $row['SecondaryContactEmail'] ?? NULL);

            $check = $conn->query("SELECT * FROM members WHERE EmailAddress = '$EmailAddress'");
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
                        ($Birthdate ? "'$Birthdate'" : "NULL") . ", '$Gender', '$Ethnicity', 
                        '$ShirtSize', '$Street', '$City', '$State', '$Zip', $MembershipYear, '$MembershipTier', '$School', 
                        '$PrimaryContactName', '$PrimaryContactPhone', '$PrimaryContactEmail', '$SecondaryContactName', 
                        '$SecondaryContactPhone', '$SecondaryContactEmail'
                    )";

            if ($conn->query($sql)) {
                $imported++;
            } else {
                $skipped++;
                error_log("SQL Error: " . $conn->error);
                $_SESSION['errorMessage'] = "<div class='message error'>Error importing data: " . $conn->error . "</div>";
            }
        }

        unset($_SESSION['previewData']);

        $_SESSION['successMessage'] = "<div class='message success'>$imported members imported successfully. $skipped skipped (duplicates or errors).</div>";
        header("Location: import_members.php");
        exit;
    }