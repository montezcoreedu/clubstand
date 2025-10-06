<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/checkmemberurl.php");
    include("../common/permissions.php");

    if (!in_array("Member Timeline", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    if (!empty($_GET['id']) && $check_url) {
        include("../common/membercommon.php");

        $sql = "(
            SELECT 
                a.MeetingDate AS event_date,
                'Attendance' AS category,
                a.Status AS title,
                CASE 
                WHEN a.Status = 'Present' THEN 'Attended the meeting.'
                ELSE CONCAT('Marked as ', a.Status, ' for the meeting.')
                END AS description,
                'calendar-fold' AS icon
            FROM attendance a
            WHERE a.MemberId = ?
            )
            UNION ALL
            (
            SELECT 
                cs.ServiceDate AS event_date,
                'Service' AS category,
                cs.ServiceName AS title,
                CONCAT(msh.ServiceHours, ' hour(s) - ', cs.ServiceType) AS description,
                'heart' AS icon
            FROM memberservicehours msh
            JOIN communityservices cs ON msh.ServiceId = cs.ServiceId
            WHERE msh.MemberId = ?
            )
            UNION ALL
            (
            SELECT 
                d.DemeritDate AS event_date,
                'Demerit' AS category,
                d.Demerit AS title,
                COALESCE(d.DemeritDescription, '') AS description,
                'alert-triangle' AS icon
            FROM demerits d
            WHERE d.MemberId = ?
            )
            ORDER BY event_date DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $getMemberId, $getMemberId, $getMemberId);
        $stmt->execute();
        $result = $stmt->get_result();

        $timeline = [];
        while ($row = $result->fetch_assoc()) {
            $timeline[] = $row;
        }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $LastName; ?>, <?php echo $FirstName; ?><?php echo !empty($Suffix) ? ' ' . $Suffix : ''; ?></title>
    <?php include("../common/head.php"); ?>
</head>
<body>
    <?php include("../common/loading.php"); ?>
    <?php include("../common/top-navbar.php"); ?>
    <?php include("../common/memberhead.php"); ?>
    <div id="content">
        <?php include("../common/member-sidebar.php"); ?>
        <div class="toggle-content" id="main-content-wrapper">
            <h2>Historical Timeline</h2>
            <div style="margin: 10px 1rem;">
                <div class="timeline">
                    <?php if (empty($timeline)): ?>
                        <p>No timeline activity available for this member yet.</p>
                    <?php else: ?>
                        <?php foreach ($timeline as $index => $entry): ?>
                        <?php 
                            $formattedDate = date("F j, Y", strtotime($entry['event_date']));
                            $categoryClass = strtolower($entry['category']);
                        ?>
                        <div class="timeline-entry" style="animation-delay: <?= $index * 0.1 ?>s;">
                            <div class="timeline-icon <?= $categoryClass ?>">
                                <i data-lucide="<?= htmlspecialchars($entry['icon']) ?>"></i>
                            </div>
                            <div class="timeline-content">
                                <span class="timeline-date"><?= $formattedDate ?></span>
                                <h4 class="timeline-title"><?= htmlspecialchars($entry['title']) ?></h4>
                                <p class="timeline-description"><?= htmlspecialchars($entry['description']) ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
<?php $conn->close(); ?>
<?php } else {
    header("HTTP/1.0 404 Not Found");
    exit();
} ?>