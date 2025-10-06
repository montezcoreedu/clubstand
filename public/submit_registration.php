<?php
    include("../dbconnection.php");
    include("common/session.php");

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $memberId = $_SESSION['account_id'];

        $FirstName = trim($_POST['FirstName']);
        $LastName = trim($_POST['LastName']);
        $Suffix = trim($_POST['Suffix']);
        $EmailAddress = trim($_POST['EmailAddress']);
        $CellPhone = trim($_POST['CellPhone']);
        $Birthdate = date('Y-m-d', strtotime(trim($_POST['Birthdate'])));
        $Gender = trim($_POST['Gender']);
        $Ethnicity = trim($_POST['Ethnicity']);
        $Street = trim($_POST['Street']);
        $City = trim($_POST['City']);
        $State = trim($_POST['State']);
        $Zip = trim($_POST['Zip']);
        $PrimaryContactName = trim($_POST['PrimaryContactName']);
        $PrimaryContactPhone = trim($_POST['PrimaryContactPhone']);
        $PrimaryContactEmail = trim($_POST['PrimaryContactEmail']);
        $SecondaryContactName = trim($_POST['SecondaryContactName']);
        $SecondaryContactPhone = trim($_POST['SecondaryContactPhone']);
        $SecondaryContactEmail = trim($_POST['SecondaryContactEmail']);
        $GradeLevel = trim($_POST['GradeLevel']);
        $ShirtSize = trim($_POST['ShirtSize']);
        $MembershipYear = trim($_POST['MembershipYear']);
        $MembershipTier = trim($_POST['MembershipTier']);
        $School = trim($_POST['School']);

        $sql = "UPDATE members SET 
                    FirstName = ?,
                    LastName = ?,
                    Suffix = ?,
                    EmailAddress = ?,
                    CellPhone = ?,
                    Birthdate = ?,
                    Gender = ?,
                    Ethnicity = ?,
                    Street = ?,
                    City = ?,
                    State = ?,
                    Zip = ?,
                    PrimaryContactName = ?,
                    PrimaryContactPhone = ?,
                    PrimaryContactEmail = ?,
                    SecondaryContactName = ?,
                    SecondaryContactPhone = ?,
                    SecondaryContactEmail = ?,
                    GradeLevel = ?,
                    ShirtSize = ?,
                    MembershipYear = ?,
                    MembershipTier = ?,
                    School = ?,
                    MemberStatus = 1
                WHERE MemberId = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssssssssssssssssssi",
            $FirstName, $LastName, $Suffix, $EmailAddress, $CellPhone, $Birthdate,
            $Gender, $Ethnicity, $Street, $City, $State, $Zip,
            $PrimaryContactName, $PrimaryContactPhone, $PrimaryContactEmail,
            $SecondaryContactName, $SecondaryContactPhone, $SecondaryContactEmail,
            $GradeLevel, $ShirtSize, $MembershipYear, $MembershipTier, $School, $memberId
        );

        if ($stmt->execute()) {
            $_SESSION['successMessage'] = '<div class="message success">Welcome back to our chapter! We are pleased to inform you that your registration has been successfully recorded. We kindly ask that you make sure to join our BAND, Schoology, and Rooms for any updates regarding the chapter. Thank you!</div>';
            header('Location: home.php');
            exit();
        } else {
            $_SESSION['errorMessage'] = '<div class="message error">Registration failed. Please contact your membership coordinator for assistance.</div>';
            header('Location: home.php');
            exit();
        }
    } else {
        header('Location: home.php');
        exit();
    }