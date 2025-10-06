<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    if (!in_array("Community Services", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    if (isset($_POST['update_service'])) {
        $serviceId = $_POST['ServiceId'];
        $serviceName = $_POST['ServiceName'];
        $serviceDate = date('Y-m-d', strtotime($_POST['ServiceDate']));
        $serviceType = $_POST['ServiceType'];
        $serviceHours = $_POST['ServiceHours'];

        $stmt = $conn->prepare("UPDATE communityservices SET ServiceName = ?, ServiceDate = ?, ServiceType = ? WHERE ServiceId = ?");
        $stmt->bind_param("sssi", $serviceName, $serviceDate, $serviceType, $serviceId);

        if ($stmt->execute()) {
            $stmt2 = $conn->prepare("UPDATE memberservicehours SET ServiceHours = ? WHERE MemberId = ? AND ServiceId = ?");

            foreach ($serviceHours as $memberId => $hours) {
                $stmt2->bind_param("dii", $hours, $memberId, $serviceId);
                $stmt2->execute();
            }

            $_SESSION['successMessage'] = "<div class='message success'>Service records updated successfully!</div>";
            header("Location: view.php?id={$serviceId}");
            exit();
        } else {
            $_SESSION['errorMessage'] = "<div class='message error'>Failed to update service record. Please try again.</div>";
            header("Location: view.php?id={$serviceId}");
            exit();
        }
    }