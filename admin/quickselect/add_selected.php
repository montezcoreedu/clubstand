<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['selected_ids'])) {
        $selectedIds = array_map('intval', $_POST['selected_ids']);
        $adminId = $_SESSION['account_id'];
        $addedCount = 0;
        $skippedCount = 0;

        foreach ($selectedIds as $memberId) {
            $checkQuery = "SELECT q.SelectId, m.MemberId
                        FROM quick_select q 
                        INNER JOIN members m ON q.MemberId = m.MemberId 
                        WHERE q.AdminId = $adminId 
                        AND q.AddedOn >= NOW() - INTERVAL 1 DAY AND q.MemberId = $memberId";
            $result = $conn->query($checkQuery);

            if ($result && $result->num_rows === 0) {
                $insertQuery = "INSERT INTO quick_select (AdminId, MemberId, AddedOn) VALUES ($adminId, $memberId, CURRENT_TIMESTAMP())";
                if ($conn->query($insertQuery)) {
                    $addedCount++;
                } else {
                    $skippedCount++;
                }
            } else {
                $skippedCount++;
            }
        }

        if ($addedCount > 0) {
            $_SESSION['successMessage'] = "<div class='message success'>$addedCount member(s) added to Quick Select!</div>";
        } else {
            $_SESSION['errorMessage'] = "<div class='message error'>No new members were added (all were already selected).</div>";
        }
    } else {
        $_SESSION['errorMessage'] = "<div class='message error'>No members selected for adding.</div>";
    }

    header("Location: ../quickselect/");
    exit;