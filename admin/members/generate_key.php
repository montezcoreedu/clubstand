<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/checkmemberurl.php");

    if (!empty($_GET['id']) && $check_url) {
        include("../common/membercommon.php");

        function generateRandomKey($length = 5) {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $key = '';
            for ($i = 0; $i < $length; $i++) {
                $key .= $characters[random_int(0, strlen($characters) - 1)];
            }
            return $key;
        }
        function getUniqueKey($conn, $length = 5) {
            do {
                $key = generateRandomKey($length);
                $checkQuery = $conn->prepare("SELECT COUNT(*) FROM members WHERE RegistrationKey = ?");
                $checkQuery->bind_param("s", $key);
                $checkQuery->execute();
                $count = 0;
                $checkQuery->bind_result($count);
                $checkQuery->fetch();
                $checkQuery->close();
            } while ($count > 0);
            return $key;
        }

        $registrationKey = getUniqueKey($conn);
        $lockAccess = 2;

        $updateQuery = "UPDATE members SET RegistrationKey = ?, LockAccess = ? WHERE MemberId = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("sii", $registrationKey, $lockAccess, $getMemberId);

        if ($stmt->execute()) {
            $_SESSION['successMessage'] = "<div class='message success'>Registration key generated: <strong>$registrationKey</strong></div>";
        } else {
            $_SESSION['errorMessage'] = "<div class='message error'>Failed to generate key. Please try again.</div>";
        }

        $stmt->close();
        header("Location: membership.php?id=$getMemberId");
        exit;
    } else {
        header("HTTP/1.0 404 Not Found");
        exit();
    }