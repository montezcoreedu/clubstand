<?php
    include("../../dbconnection.php");
    include("../common/session.php");
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

                $headers = array_map(function($h) {
                    return trim(str_replace(["\u{FEFF}", "\r", "\n"], '', $h));
                }, fgetcsv($file));

                $previewData = [];
                $imported = 0;
                $skipped = 0;

                while (($data = fgetcsv($file)) !== FALSE) {
                    if (count($data) < count($headers)) {
                        $data = array_pad($data, count($headers), null);
                    } elseif (count($data) > count($headers)) {
                        $data = array_slice($data, 0, count($headers));
                    }

                    $row = array_combine($headers, $data);

                    if (!$row) {
                        $skipped++;
                        continue;
                    }

                    $previewData[] = $row;
                }

                fclose($file);

                $_SESSION['previewData'] = $previewData;

                header("Location: preview_import.php");
                exit;
            } else {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => "The uploaded file exceeds the upload_max_filesize directive.",
                    UPLOAD_ERR_FORM_SIZE => "The uploaded file exceeds MAX_FILE_SIZE.",
                    UPLOAD_ERR_PARTIAL => "The file was only partially uploaded.",
                    UPLOAD_ERR_NO_FILE => "No file was uploaded.",
                    UPLOAD_ERR_NO_TMP_DIR => "Missing temporary folder.",
                    UPLOAD_ERR_CANT_WRITE => "Failed to write file.",
                    UPLOAD_ERR_EXTENSION => "A PHP extension stopped the upload."
                ];

                $errorMessage = $errorMessages[$fileError] ?? "Unknown file upload error.";
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
            $cleanPhone = function($p) {
                return preg_replace('/[^0-9]/', '', $p ?? '');
            };

            $cleanZip = function($z) {
                return preg_replace('/[^0-9]/', '', $z ?? '');
            };

            $rawBirthdate = trim($row['Birthdate'] ?? '');
            $Birthdate = NULL;

            if ($rawBirthdate !== "" && is_numeric($rawBirthdate) && $rawBirthdate > 30000) {
                $Birthdate = date('Y-m-d', ($rawBirthdate - 25569) * 86400);
            } else {
                $formats = [
                    'n/j/Y','m/d/Y','n-j-Y','m-d-Y',
                    'Y-m-d','Y/n/j','Y/m/d',
                    'F j, Y','M j, Y','j F Y','j M Y',
                    'm-d-y','m/d/y'
                ];
                foreach ($formats as $format) {
                    $birthObj = DateTime::createFromFormat($format, $rawBirthdate);
                    if ($birthObj && $birthObj->format($format) === $rawBirthdate) {
                        $Birthdate = $birthObj->format('Y-m-d');
                        break;
                    }
                }
                if (!$Birthdate && strtotime($rawBirthdate)) {
                    $Birthdate = date('Y-m-d', strtotime($rawBirthdate));
                }
            }

            $LastName = mysqli_real_escape_string($conn, trim($row['LastName'] ?? ''));
            $FirstName = mysqli_real_escape_string($conn, trim($row['FirstName'] ?? ''));
            $Suffix = mysqli_real_escape_string($conn, trim($row['Suffix'] ?? ''));
            $Position = mysqli_real_escape_string($conn, trim($row['Position'] ?? ''));
            $EmailAddress = mysqli_real_escape_string($conn, trim($row['EmailAddress'] ?? ''));

            $Phone = $cleanPhone($row['CellPhone'] ?? '');
            $PrimaryContactPhone = $cleanPhone($row['PrimaryContactPhone'] ?? '');
            $SecondaryContactPhone = $cleanPhone($row['SecondaryContactPhone'] ?? '');
            $Zip = $cleanZip($row['Zip'] ?? '');

            $GradeLevel = is_numeric(trim($row['GradeLevel'] ?? '')) ? (int)$row['GradeLevel'] : "NULL";
            $MembershipYear = is_numeric(trim($row['MembershipYear'] ?? '')) ? (int)$row['MembershipYear'] : "NULL";

            $check = $conn->query("SELECT 1 FROM members WHERE EmailAddress = '$EmailAddress' LIMIT 1");
            if ($check && $check->num_rows > 0) {
                $skipped++;
                continue;
            }

            $sql = "INSERT INTO members (
                LastName, FirstName, Suffix, Position, EmailAddress, 
                CellPhone, GradeLevel, Birthdate, Gender, Ethnicity, ShirtSize,
                Street, City, State, Zip, MembershipYear, MembershipTier, School,
                PrimaryContactName, PrimaryContactPhone, PrimaryContactEmail,
                SecondaryContactName, SecondaryContactPhone, SecondaryContactEmail
            ) VALUES (
                '$LastName', '$FirstName', '$Suffix', " . ($Position ? "'$Position'" : "NULL") . ",
                '$EmailAddress', '$Phone', $GradeLevel, " . ($Birthdate ? "'$Birthdate'" : "NULL") . ",
                '" . mysqli_real_escape_string($conn, $row['Gender']) . "',
                '" . mysqli_real_escape_string($conn, $row['Ethnicity']) . "',
                '" . mysqli_real_escape_string($conn, $row['ShirtSize']) . "',
                '" . mysqli_real_escape_string($conn, $row['Street']) . "',
                '" . mysqli_real_escape_string($conn, $row['City']) . "',
                '" . mysqli_real_escape_string($conn, $row['State']) . "',
                '$Zip', $MembershipYear,
                '" . mysqli_real_escape_string($conn, $row['MembershipTier']) . "',
                '" . mysqli_real_escape_string($conn, $row['School']) . "',
                '" . mysqli_real_escape_string($conn, $row['PrimaryContactName']) . "',
                '$PrimaryContactPhone',
                '" . mysqli_real_escape_string($conn, $row['PrimaryContactEmail']) . "',
                '" . mysqli_real_escape_string($conn, $row['SecondaryContactName']) . "',
                '$SecondaryContactPhone',
                '" . mysqli_real_escape_string($conn, $row['SecondaryContactEmail']) . "'
            )";

            if ($conn->query($sql)) {
                $imported++;
            } else {
                $skipped++;
                error_log("SQL Error: " . $conn->error);
            }
        }

        unset($_SESSION['previewData']);
        $_SESSION['successMessage'] = "<div class='message success'>$imported members imported successfully. $skipped skipped.</div>";
        header("Location: import_members.php");
        exit;
    }